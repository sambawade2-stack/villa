<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Enums\CommissionStatus;
use App\Enums\ComplianceItem;
use App\Enums\ComplianceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PropertyStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Commission;
use App\Models\Destination;
use App\Models\Favorite;
use App\Models\Payment;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyOwner;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Services\Compliance\ComplianceService;
use App\Support\BookingReference;
use App\Support\Money;
use Database\Seeders\Support\DemoContent;
use Database\Seeders\Support\DemoImageFactory;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Jeu de démonstration.
 *
 * Toutes les personnes, coordonnées et réservations sont fictives.
 * Les destinations et leurs coordonnées, elles, sont réelles.
 */
class DemoSeeder extends Seeder
{
    private DemoImageFactory $images;

    public function run(): void
    {
        $this->images = new DemoImageFactory;

        $admin = $this->createAdmin();
        $customers = $this->createCustomers();
        $owners = $this->createOwners();
        $properties = $this->createProperties($owners);

        $this->createBookings($properties, $customers);
        $this->createManualBlocks($properties, $admin);
        $this->createFavorites($properties, $customers);
    }

    // ------------------------------------------------------------------ comptes

    private function createAdmin(): User
    {
        // Identifiants lus dans la configuration : un reseeding ne doit pas
        // réécraser le compte réel de l'exploitant.
        $admin = User::updateOrCreate(
            ['email' => config('platform.admin.email')],
            [
                'first_name' => config('platform.admin.first_name'),
                'last_name' => config('platform.admin.last_name'),
                'password' => config('platform.admin.password'),
                'phone' => '+221 77 000 00 01',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        $this->note('  administrateur : '.config('platform.admin.email'));

        return $admin;
    }

    /** @return Collection<int, User> */
    private function createCustomers(): Collection
    {
        $demo = User::updateOrCreate(
            ['email' => config('platform.customer_demo.email')],
            [
                'first_name' => 'Moussa',
                'last_name' => 'Sarr',
                'password' => config('platform.customer_demo.password'),
                'phone' => '+221 77 000 00 02',
                'role' => UserRole::Customer,
                'email_verified_at' => now(),
            ]
        );

        $this->note('  client         : '.config('platform.customer_demo.email'));

        return User::factory()->count(11)->create()->prepend($demo);
    }

    /** @return Collection<int, PropertyOwner> */
    private function createOwners(): Collection
    {
        $named = [
            ['Mamadou', 'Fall', 'Dakar'],
            ['Fatou', 'Ndiaye', 'Mbour'],
            ['Ousmane', 'Diop', 'Saly'],
            ['Awa', 'Sow', 'Thiès'],
        ];

        $owners = collect($named)->map(fn (array $o) => PropertyOwner::factory()->create([
            'first_name' => $o[0],
            'last_name' => $o[1],
            'city' => $o[2],
            'internal_notes' => 'Contact fictif créé pour la démonstration.',
        ]));

        return $owners->concat(PropertyOwner::factory()->count(4)->create());
    }

    // ------------------------------------------------------------------ villas

    /**
     * @param  Collection<int, PropertyOwner>  $owners
     * @return Collection<int, Property>
     */
    private function createProperties(Collection $owners): Collection
    {
        $destinations = Destination::query()->orderBy('position')->get();
        $amenities = Amenity::query()->get();

        // 14 publiées, 2 brouillons, 1 dépubliée, 1 suspendue.
        $plan = array_merge(
            array_fill(0, 14, PropertyStatus::Published),
            [PropertyStatus::Draft, PropertyStatus::Draft, PropertyStatus::Unpublished, PropertyStatus::Suspended],
        );

        $properties = collect();

        foreach ($plan as $i => $status) {
            $destination = $destinations[$i % $destinations->count()];

            $name = DemoContent::NAMES[$i];
            $quarters = DemoContent::NEIGHBOURHOODS[$destination->slug];
            $neighbourhood = $quarters[$i % count($quarters)];
            $city = (string) $destination->name->get('fr');

            $factory = Property::factory()->state(fn (array $attributes) => [
                'property_owner_id' => $owners[$i % $owners->count()]->id,
                'destination_id' => $destination->id,
                'is_featured' => $i < 4,
                'name' => $name,
                'slug' => Str::slug($name),
                'neighborhood' => $neighbourhood,
                'description' => DemoContent::description(
                    $name, $city, $neighbourhood,
                    (int) $attributes['bedrooms'], (int) $attributes['capacity'],
                ),
                'short_description' => DemoContent::shortDescription($city, $neighbourhood, (int) $attributes['bedrooms']),
            ]);

            $property = $status === PropertyStatus::Published
                ? $factory->published()->create()
                : $factory->create(['status' => $status]);

            $property->update([
                'meta_title' => "{$property->name} — location de villa à {$city}",
                'meta_description' => Str::limit((string) $property->short_description->get('fr'), 155),
            ]);

            $this->attachImages($property, $status === PropertyStatus::Draft ? 2 : 4);
            $this->attachAmenities($property, $amenities);
            $this->attachSeasonalPricing($property);
            $this->buildComplianceFile($property, $i, $status);

            $properties->push($property);
        }

        $this->note("  {$properties->count()} villas (14 publiées)");

        return $properties;
    }

    /**
     * Constitue un dossier de conformité de démonstration.
     *
     * Aucun fichier n'est déposé : les documents de conformité sont des pièces
     * d'identité et des titres de propriété, il n'y a rien de crédible à
     * fabriquer ici. Seuls les statuts sont renseignés, ce qui suffit à
     * exercer la mécanique du badge « Villa vérifiée ».
     */
    private function buildComplianceFile(Property $property, int $index, PropertyStatus $status): void
    {
        $compliance = app(ComplianceService::class);
        $compliance->ensureChecklist($property);

        // Un brouillon n'a pas encore de dossier : on le laisse vide.
        if ($status === PropertyStatus::Draft) {
            return;
        }

        foreach ($property->complianceChecks()->get() as $check) {
            $itemStatus = match (true) {
                // Deux villas sur dix gardent une pièce en attente, une autre
                // une pièce refusée : le tableau de bord a ainsi de quoi alerter.
                $index % 7 === 3 && $check->item === ComplianceItem::BusinessRegistration => ComplianceStatus::Provided,
                $index % 9 === 5 && $check->item === ComplianceItem::OwnershipProof => ComplianceStatus::Rejected,
                $check->item->isConditional() && $index % 3 === 0 => ComplianceStatus::NotApplicable,
                default => ComplianceStatus::Verified,
            };

            $check->forceFill([
                'status' => $itemStatus,
                'reference' => $check->item->requiresDocument() ? 'DEMO-'.Str::upper(Str::random(8)) : null,
                'issued_on' => $check->item->canExpire() ? now()->subMonths(random_int(6, 30)) : null,
                'expires_on' => $check->item->canExpire() ? now()->addMonths(random_int(4, 36)) : null,
                'verified_at' => $itemStatus === ComplianceStatus::Verified ? now()->subDays(random_int(5, 200)) : null,
                'notes' => $itemStatus === ComplianceStatus::Rejected
                    ? 'Document illisible, à redemander au propriétaire.'
                    : null,
            ])->save();
        }

        // Le badge public découle du dossier, jamais d'un réglage manuel.
        $compliance->syncPropertyVerification($property);
    }

    private function attachImages(Property $property, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $generated = $this->images->make($property->slug, $i);

            PropertyImage::create([
                'property_id' => $property->id,
                'path' => $generated['path'],
                'disk' => 'properties',
                'alt' => [
                    'fr' => "{$property->name} — visuel de démonstration ".($i + 1),
                    'en' => "{$property->name} — demo visual ".($i + 1),
                ],
                'position' => $i,
                'is_primary' => $i === 0,
                'width' => $generated['width'],
                'height' => $generated['height'],
                'size' => $generated['size'],
                'conversions' => $generated['conversions'],
                'converted_at' => now(),
            ]);
        }
    }

    /** @param  Collection<int, Amenity>  $amenities */
    private function attachAmenities(Property $property, Collection $amenities): void
    {
        // Toute villa de ce standing a ces trois-là ; le reste varie.
        $core = $amenities->whereIn('slug', ['wifi', 'climatisation', 'parking'])->pluck('id');
        $extra = $amenities->whereNotIn('slug', ['wifi', 'climatisation', 'parking'])
            ->random(random_int(4, 9))->pluck('id');

        $property->amenities()->sync($core->concat($extra)->all());
    }

    private function attachSeasonalPricing(Property $property): void
    {
        $base = $property->base_price->amount;
        $year = now()->year;

        PricingRule::create([
            'property_id' => $property->id,
            'label' => 'Haute saison',
            'starts_on' => "{$year}-12-15",
            'ends_on' => ($year + 1).'-01-10',
            'price_per_night' => (int) round($base * 1.45 / 5_000) * 5_000,
            'min_nights' => 4,
            'priority' => 10,
        ]);

        PricingRule::create([
            'property_id' => $property->id,
            'label' => 'Basse saison',
            'starts_on' => "{$year}-06-01",
            'ends_on' => "{$year}-09-30",
            'price_per_night' => (int) round($base * 0.80 / 5_000) * 5_000,
            'priority' => 5,
        ]);
    }

    // ------------------------------------------------------------------ réservations

    /**
     * Les dates sont posées à la file sur chaque villa, séparées par un écart.
     * La contrainte d'exclusion refuserait tout chevauchement — le seeder doit
     * donc être correct par construction, pas par chance.
     */
    /**
     * @param  Collection<int, Property>  $properties
     * @param  Collection<int, User>  $customers
     */
    private function createBookings(Collection $properties, Collection $customers): void
    {
        $commissionRate = (string) Setting::get('platform.commission_rate', 10);
        $serviceFeeRate = (string) Setting::get('platform.service_fee_rate', 3);
        $created = 0;

        foreach ($properties->where('status', PropertyStatus::Published) as $property) {
            $cursor = Carbon::today()->subMonths(7);

            foreach (range(1, random_int(3, 6)) as $n) {
                $cursor->addDays(random_int(6, 34));
                $nights = random_int(3, 12);
                $checkin = $cursor->copy();
                $checkout = $cursor->copy()->addDays($nights);
                $cursor = $checkout->copy();

                $status = match (true) {
                    $checkout->isPast() => BookingStatus::Completed,
                    $n % 7 === 0 => BookingStatus::Cancelled,
                    default => BookingStatus::Confirmed,
                };

                $booking = $this->makeBooking(
                    $property, $customers->random(), $checkin, $checkout, $nights,
                    $status, $commissionRate, $serviceFeeRate
                );

                $created++;

                // Une réservation annulée libère ses dates : pas de blocage.
                if ($status !== BookingStatus::Cancelled) {
                    AvailabilityBlock::create([
                        'property_id' => $property->id,
                        'starts_on' => $checkin->toDateString(),
                        'ends_on' => $checkout->toDateString(),
                        'reason' => BlockReason::Booking,
                        'booking_id' => $booking->id,
                    ]);
                }

                if ($status === BookingStatus::Completed && random_int(1, 100) <= 70) {
                    $this->makeReview($booking);
                }
            }
        }

        $this->note("  {$created} réservations, blocages et avis associés");
    }

    private function makeBooking(
        Property $property,
        User $customer,
        Carbon $checkin,
        Carbon $checkout,
        int $nights,
        BookingStatus $status,
        string $commissionRate,
        string $serviceFeeRate,
    ): Booking {
        $nightly = $property->base_price->times($nights);
        $cleaning = $property->cleaning_fee;
        $service = $nightly->percentage($serviceFeeRate);
        $total = Money::sum($nightly, $cleaning, $service);

        $commission = $total->percentage($commissionRate);

        $booking = Booking::create([
            'reference' => BookingReference::next(),
            'property_id' => $property->id,
            'user_id' => $customer->id,
            'checkin_date' => $checkin->toDateString(),
            'checkout_date' => $checkout->toDateString(),
            'nights' => $nights,
            'guests_count' => random_int(2, max(2, $property->capacity)),
            'status' => $status,
            'nightly_subtotal' => $nightly->amount,
            'cleaning_fee' => $cleaning->amount,
            'service_fee' => $service->amount,
            'total_amount' => $total->amount,
            'security_deposit' => $property->security_deposit->amount,
            'commission_rate' => $status === BookingStatus::Cancelled ? null : $commissionRate,
            'commission_amount' => $status === BookingStatus::Cancelled ? null : $commission->amount,
            'owner_payout_amount' => $status === BookingStatus::Cancelled ? null : $total->minus($commission)->amount,
            'confirmed_at' => $status === BookingStatus::Cancelled ? null : $checkin->copy()->subDays(random_int(5, 40)),
            'completed_at' => $status === BookingStatus::Completed ? $checkout : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? $checkin->copy()->subDays(random_int(2, 20)) : null,
            'cancellation_reason' => $status === BookingStatus::Cancelled ? 'Changement de programme du client' : null,
        ]);

        if ($status !== BookingStatus::Cancelled) {
            Payment::create([
                'booking_id' => $booking->id,
                'gateway' => 'manual',
                'status' => PaymentStatus::Succeeded,
                'amount' => $total->amount,
                'currency' => 'XOF',
                'provider_reference' => 'DEMO-'.Str::upper(Str::random(12)),
                'paid_at' => $booking->confirmed_at,
            ]);

            Commission::create([
                'booking_id' => $booking->id,
                'property_owner_id' => $property->property_owner_id,
                'rate' => $commissionRate,
                'base_amount' => $total->amount,
                'commission_amount' => $commission->amount,
                'owner_payout_amount' => $total->minus($commission)->amount,
                'status' => $status === BookingStatus::Completed ? CommissionStatus::Settled : CommissionStatus::Pending,
                'settled_at' => $status === BookingStatus::Completed ? $checkout : null,
            ]);
        }

        return $booking;
    }

    private function makeReview(Booking $booking): void
    {
        $scores = collect(Review::CRITERIA)
            ->mapWithKeys(fn (string $c) => [$c => random_int(3, 5)])
            ->all();

        $comments = [
            "Villa conforme aux photos, très calme, à quelques minutes de la plage. L'accueil a été parfait.",
            'Séjour excellent en famille. La piscine est impeccable et le quartier très sûr.',
            'Belle maison, bien équipée. Une climatisation un peu bruyante dans une chambre, sans plus.',
            "Emplacement idéal pour découvrir la région. Nous reviendrons l'an prochain.",
            'Très bon rapport qualité-prix. Le personnel de maison est discret et efficace.',
        ];

        Review::create([
            'booking_id' => $booking->id,
            'property_id' => $booking->property_id,
            'user_id' => $booking->user_id,
            ...$scores,
            'overall' => Review::computeOverall($scores),
            'comment' => $comments[array_rand($comments)],
            'status' => ReviewStatus::Approved,
            'published_at' => $booking->completed_at?->addDays(random_int(1, 6)),
        ]);
    }

    // ------------------------------------------------------------------ divers

    /** @param  Collection<int, Property>  $properties */
    private function createManualBlocks(Collection $properties, User $admin): void
    {
        foreach ($properties->where('status', PropertyStatus::Published)->random(5) as $property) {
            $start = Carbon::today()->addMonths(random_int(4, 8));

            // Le blocage manuel peut tomber sur une réservation existante :
            // la contrainte le refuserait, on laisse donc la base arbitrer.
            try {
                AvailabilityBlock::create([
                    'property_id' => $property->id,
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $start->copy()->addDays(random_int(3, 8))->toDateString(),
                    'reason' => BlockReason::Maintenance,
                    'note' => 'Entretien annuel de la piscine',
                    'created_by' => $admin->id,
                ]);
            } catch (QueryException) {
                // Chevauchement refusé par PostgreSQL : comportement attendu.
            }
        }
    }

    /**
     * @param  Collection<int, Property>  $properties
     * @param  Collection<int, User>  $customers
     */
    private function createFavorites(Collection $properties, Collection $customers): void
    {
        $published = $properties->where('status', PropertyStatus::Published);

        foreach ($customers as $customer) {
            foreach ($published->random(random_int(0, 4)) as $property) {
                Favorite::firstOrCreate([
                    'user_id' => $customer->id,
                    'property_id' => $property->id,
                ]);
            }
        }
    }

    /** La sortie console n'existe que si le seeder est lancé par artisan. */
    private function note(string $message): void
    {
        if (isset($this->command)) {
            $this->command->info($message);
        }
    }
}
