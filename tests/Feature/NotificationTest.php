<?php

declare(strict_types=1);

use App\Enums\MessageChannel;
use App\Exceptions\DatesUnavailableException;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRequested;
use App\Notifications\NewBookingForAdmin;
use App\Notifications\NewMessageReceived;
use App\Services\Booking\BookingService;
use App\Services\Messaging\MessagingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->bookings = app(BookingService::class);

    $this->property = Property::factory()->published()->create([
        'base_price' => 150_000, 'weekend_price' => null, 'cleaning_fee' => 20_000,
        'capacity' => 6, 'min_nights' => 1,
    ]);

    Setting::put('platform.commission_rate', 10.0, 'commission');
    Setting::flushCache();

    $this->from = Carbon::today()->addMonth()->toDateString();
    $this->to = Carbon::today()->addMonth()->addDays(3)->toDateString();
});

/*
|--------------------------------------------------------------------------
| Réservation
|--------------------------------------------------------------------------
*/

it('prévient le client et l\'administration à la demande', function () {
    Notification::fake();

    $booking = $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);

    Notification::assertSentTo($this->customer, BookingRequested::class,
        fn ($n) => $n->booking->is($booking));

    Notification::assertSentTo($this->admin, NewBookingForAdmin::class);
});

it('prévient le client à la confirmation', function () {
    Notification::fake();

    $booking = $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $this->bookings->confirm($booking);

    Notification::assertSentTo($this->customer, BookingConfirmed::class);
});

it('prévient le client à l\'annulation, avec le motif', function () {
    Notification::fake();

    $booking = $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $this->bookings->cancel($booking, reason: 'Paiement non reçu dans le délai imparti.');

    Notification::assertSentTo($this->customer, BookingCancelled::class,
        fn (BookingCancelled $n) => $n->reason === 'Paiement non reçu dans le délai imparti.');
});

it('ne notifie rien quand la réservation est refusée', function () {
    $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);

    // Posé après la première réservation : seule la seconde est observée.
    Notification::fake();

    try {
        $this->bookings->hold($this->property, User::factory()->create(), $this->from, $this->to, 2);
    } catch (DatesUnavailableException) {
        // attendu
    }

    Notification::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Contenu des notifications
|--------------------------------------------------------------------------
*/

it('n\'expose jamais l\'adresse de la villa dans la notification stockée', function () {
    $this->property->update([
        'internal_address' => 'Lot 42, cité privée, Saly',
        'internal_notes' => 'Code portail 4477',
    ]);

    $booking = $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $payload = (new BookingRequested($booking))->toArray($this->customer);

    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

    expect($encoded)->not->toContain('Lot 42')
        ->not->toContain('4477')
        ->and($payload)->toHaveKeys(['reference', 'property', 'url', 'title']);
});

it('mène vers la bonne destination selon le destinataire', function () {
    $booking = $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);

    $pourClient = (new BookingRequested($booking))->toArray($this->customer);
    $pourAdmin = (new NewBookingForAdmin($booking))->toArray($this->admin);

    expect($pourClient['url'])->toContain('/reservations/')
        ->and($pourAdmin['url'])->toContain('/admin/reservations/');
});

/*
|--------------------------------------------------------------------------
| Messagerie
|--------------------------------------------------------------------------
*/

it('prévient l\'administration quand le client écrit', function () {
    Notification::fake();

    $messaging = app(MessagingService::class);
    $conversation = $messaging->openConversation($this->customer, 'Question sur les dates');

    $messaging->post($conversation, $this->customer, 'Bonjour, la villa est-elle libre ?');

    Notification::assertSentTo($this->admin, NewMessageReceived::class,
        fn (NewMessageReceived $n) => $n->forAdmin === true);
    Notification::assertNotSentTo($this->customer, NewMessageReceived::class);
});

it('prévient le client quand l\'administration répond', function () {
    Notification::fake();

    $messaging = app(MessagingService::class);
    $conversation = $messaging->openConversation($this->customer, 'Question');

    $messaging->replyAsAdmin($conversation, $this->admin, 'Bonjour, oui elle est libre.', MessageChannel::InApp);

    Notification::assertSentTo($this->customer, NewMessageReceived::class,
        fn (NewMessageReceived $n) => $n->forAdmin === false);
});

/*
|--------------------------------------------------------------------------
| Espace client
|--------------------------------------------------------------------------
*/

it('affiche les notifications du client, et lui seul', function () {
    $booking = $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);

    $this->actingAs($this->customer)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Demande enregistrée')
        ->assertSee($booking->reference);

    $this->actingAs(User::factory()->create())->get(route('notifications.index'))
        ->assertOk()
        ->assertDontSee($booking->reference);
});

it('marque une notification lue et suit son lien', function () {
    $booking = $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $notification = $this->customer->notifications()->firstOrFail();

    expect($notification->read_at)->toBeNull();

    $this->actingAs($this->customer)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('bookings.show', $booking));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('interdit de marquer lue la notification d\'un autre', function () {
    $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $notification = $this->customer->notifications()->firstOrFail();

    $this->actingAs(User::factory()->create())
        ->post(route('notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('marque toutes les notifications lues d\'un geste', function () {
    $this->bookings->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $this->bookings->hold(
        Property::factory()->published()->create(['min_nights' => 1]),
        $this->customer, $this->from, $this->to, 2,
    );

    expect($this->customer->unreadNotifications()->count())->toBe(2);

    $this->actingAs($this->customer)->post(route('notifications.read-all'))->assertRedirect();

    expect($this->customer->fresh()->unreadNotifications()->count())->toBe(0);
});
