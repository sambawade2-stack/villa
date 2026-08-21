@php
    use App\Models\Setting;
    use Illuminate\Support\Carbon;

    $images = $property->images;
    $cover = $property->primaryImage ?? $images->first();
    $grouped = $property->amenities->groupBy('category');

    $categoryLabels = [
        'exterieur' => __('Extérieur'), 'confort' => __('Confort'), 'cuisine' => __('Cuisine'),
        'services' => __('Services'), 'pratique' => __('Pratique'), 'general' => __('Divers'),
    ];

    // Bandeau de caractéristiques : la composition, puis les trois équipements
    // que les voyageurs cherchent en premier.
    $highlights = collect([
        ['bed', trans_choice(':count chambre|:count chambres', $property->bedrooms, ['count' => $property->bedrooms])],
        ['bath', trans_choice(':count salle de bain|:count salles de bain', $property->bathrooms, ['count' => $property->bathrooms])],
        ['users', trans_choice(':count voyageur|:count voyageurs', $property->capacity, ['count' => $property->capacity])],
    ])->concat(
        $property->amenities
            ->whereIn('slug', ['piscine', 'climatisation', 'wifi', 'vue-mer'])
            ->map(fn ($a) => [$a->icon ?? 'check', (string) $a->name])
            ->values()
    );

    $email = Setting::get('contact.email');
    $whatsapp = Setting::get('contact.whatsapp');

    $bookingSubject = __('Demande de réservation — :villa', ['villa' => $property->name]);
    $bookingBody = $quote
        ? __("Bonjour,\n\nJe souhaite réserver :villa du :from au :to pour :guests voyageurs.\n\nMerci de me confirmer la disponibilité.", [
            'villa' => $property->name,
            'from' => Carbon::parse($quote->checkin)->format('d/m/Y'),
            'to' => Carbon::parse($quote->checkout)->format('d/m/Y'),
            'guests' => $quote->guests,
        ])
        : __("Bonjour,\n\nJe souhaite des informations sur :villa.", ['villa' => $property->name]);
@endphp

<x-layouts.public
    :title="$property->meta_title ?: $property->name.' — '.$property->destination?->name"
    :description="$property->meta_description ?: Str::limit(strip_tags((string) $property->short_description), 155)"
    :og-image="$cover?->url('hero')"
    og-type="article"
>
    @php
        $structuredData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LodgingBusiness',
            'name' => $property->name,
            'description' => (string) $property->short_description,
            'url' => route('villas.show', [$property->destination, $property]),
            'image' => $cover?->url('hero'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => (string) $property->destination?->name,
                'addressRegion' => $property->destination?->region,
                'addressCountry' => 'SN',
            ],
            'numberOfRooms' => $property->bedrooms,
            'petsAllowed' => $property->pets_allowed,
            'aggregateRating' => $property->rating_avg ? [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $property->rating_avg,
                'reviewCount' => $property->reviews_count,
                'bestRating' => 5,
            ] : null,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    @push('head')
        <script type="application/ld+json">
            {{--
                JSON_HEX_TAG (et les drapeaux HEX voisins) transforment < > & ' "
                en séquences \uXXXX : un nom ou une description de villa contenant
                littéralement "</script>" ne peut plus refermer la balise et
                injecter du HTML à sa suite. Le contenu vient aujourd'hui de
                l'administrateur, donc l'exposition est mesurée — mais la v2 prévue
                dans docs/architecture.html ouvre ces mêmes champs aux propriétaires,
                ce qui en ferait une XSS stockée touchant tout visiteur de la fiche.
                Autant l'exclure maintenant, pendant qu'elle ne coûte qu'une ligne.
            --}}
            {!! json_encode(
                $structuredData,
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) !!}
        </script>
    @endpush

    <div class="container-page pt-5">
        <x-ui.breadcrumb :items="[
            ['label' => __('Accueil'), 'url' => route('home')],
            ['label' => __('Villas'), 'url' => route('villas.index')],
            ['label' => (string) $property->destination?->name, 'url' => route('destinations.show', $property->destination)],
            ['label' => $property->name],
        ]" />
    </div>

    {{-- ------------------------------------------------------------- Galerie --}}
    @if ($images->isNotEmpty())
        <section class="container-page mt-4"
                 x-data="{ open: false, index: 0, total: {{ $images->count() }} }"
                 @keydown.window.escape="open = false"
                 @keydown.window.arrow-right="if (open) index = (index + 1) % total"
                 @keydown.window.arrow-left="if (open) index = (index - 1 + total) % total">

            <div class="relative grid gap-2 sm:grid-cols-[2fr_1fr]">
                <button type="button" @click="index = 0; open = true"
                        class="aspect-4/3 overflow-hidden rounded-card bg-stone-100 sm:aspect-16/11">
                    <img src="{{ $cover->url('hero') }}" srcset="{{ $cover->srcset('card', 'hero') }}"
                         sizes="(min-width: 640px) 62vw, 100vw"
                         alt="{{ $cover->alt?->get() ?? $property->name }}" fetchpriority="high"
                         class="size-full object-cover transition-transform duration-500 hover:scale-[1.02]">
                </button>

                <div class="hidden grid-rows-2 gap-2 sm:grid">
                    @foreach ($images->skip(1)->take(2) as $image)
                        <button type="button" @click="index = {{ $loop->index + 1 }}; open = true"
                                class="overflow-hidden rounded-card bg-stone-100">
                            <img src="{{ $image->url('card') }}" alt="{{ $image->alt?->get() ?? '' }}" loading="lazy"
                                 class="size-full object-cover transition-transform duration-500 hover:scale-[1.03]">
                        </button>
                    @endforeach
                </div>

                <button type="button" @click="index = 0; open = true"
                        class="absolute bottom-3 right-3 inline-flex items-center gap-2 rounded-lg bg-white/95 px-3.5 py-2 text-sm font-medium text-navy-900 shadow-card backdrop-blur-sm hover:bg-white">
                    <x-ui.icon name="eye" class="size-4" />
                    {{ trans_choice('Voir la photo|Voir toutes les photos (:count)', $images->count(), ['count' => $images->count()]) }}
                </button>
            </div>

            {{-- Visionneuse --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex flex-col bg-navy-950/95 p-4"
                 role="dialog" aria-modal="true" aria-label="{{ __('Photos de la villa') }}">
                <div class="flex justify-between text-white">
                    <span class="text-sm tabular" x-text="(index + 1) + ' / ' + total"></span>
                    <button type="button" @click="open = false" class="rounded-lg p-2 hover:bg-white/10">
                        <span class="sr-only">{{ __('Fermer') }}</span>
                        <x-ui.icon name="x" class="size-6" />
                    </button>
                </div>

                <div class="flex flex-1 items-center gap-3">
                    <button type="button" @click="index = (index - 1 + total) % total" class="rounded-lg p-2 text-white hover:bg-white/10">
                        <span class="sr-only">{{ __('Photo précédente') }}</span>
                        <x-ui.icon name="chevron-left" class="size-7" />
                    </button>

                    @foreach ($images as $image)
                        <img x-show="index === {{ $loop->index }}" x-cloak
                             src="{{ $image->url('hero') }}" alt="{{ $image->alt?->get() ?? '' }}"
                             class="mx-auto max-h-[80vh] w-auto max-w-full rounded-lg object-contain">
                    @endforeach

                    <button type="button" @click="index = (index + 1) % total" class="rounded-lg p-2 text-white hover:bg-white/10">
                        <span class="sr-only">{{ __('Photo suivante') }}</span>
                        <x-ui.icon name="chevron-right" class="size-7" />
                    </button>
                </div>
            </div>
        </section>
    @endif

    <div class="container-page mt-7 grid gap-10 pb-8 lg:grid-cols-[1fr_22rem] lg:gap-12">
        <div class="min-w-0">
            {{-- ------------------------------------------------------- Titre --}}
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="flex flex-wrap items-center gap-x-3 gap-y-2 text-2xl text-navy-900 lg:text-display">
                        {{ $property->name }}
                        @if ($property->is_verified)
                            <x-ui.badge variant="success" icon="badge-check">{{ __('Villa vérifiée') }}</x-ui.badge>
                        @endif
                    </h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-navy-500">
                        <x-ui.rating :value="$property->rating_avg"
                                     :count="$property->reviews_count ? trans_choice(':count avis|:count avis', $property->reviews_count, ['count' => $property->reviews_count]) : null" />
                        <span aria-hidden="true" class="text-stone-300">·</span>
                        <span class="inline-flex items-center gap-1">
                            <x-ui.icon name="map-pin" class="size-4" />
                            {{ $property->neighborhood ? $property->neighborhood.', ' : '' }}{{ $property->destination?->name }}, {{ __('Sénégal') }}
                        </span>
                    </p>
                </div>

                <x-favorite-button :property="$property" class="mt-1 !size-10 shrink-0 border border-stone-200 !shadow-none" />
            </div>

            {{-- ---------------------------------------------- Caractéristiques --}}
            <ul class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3 border-y border-stone-200 py-4">
                @foreach ($highlights as [$icon, $label])
                    <li class="inline-flex items-center gap-2 text-sm text-navy-700">
                        <x-ui.icon :name="$icon" class="size-4.5 text-navy-400" />
                        {{ $label }}
                    </li>
                @endforeach
            </ul>

            @if ($property->description?->get())
                <section class="mt-7">
                    <h2 class="text-title text-navy-900">{{ __('À propos de cette villa') }}</h2>
                    <div class="mt-3 space-y-4 leading-relaxed text-navy-600">
                        @foreach (preg_split('/\n\s*\n/', (string) $property->description->get()) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($property->amenities->isNotEmpty())
                <section class="mt-9 border-t border-stone-200 pt-7">
                    <h2 class="text-title text-navy-900">{{ __('Équipements') }}</h2>
                    <div class="mt-4 space-y-5">
                        @foreach ($grouped as $category => $items)
                            <div>
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-navy-400">
                                    {{ $categoryLabels[$category] ?? $category }}
                                </h3>
                                <ul class="mt-2.5 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($items as $amenity)
                                        <li class="flex items-center gap-2.5 text-sm text-navy-700">
                                            <x-ui.icon :name="$amenity->icon ?? 'check'" class="size-4.5 shrink-0 text-navy-400" />
                                            {{ $amenity->name }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="mt-9 border-t border-stone-200 pt-7">
                <h2 class="text-title text-navy-900">{{ __('Disponibilités') }}</h2>
                <p class="mt-1.5 text-sm text-navy-500">
                    {{ __('Le jour du départ reste réservable : un séjour se termine le matin, le suivant commence l\'après-midi.') }}
                </p>
                <x-availability-calendar :property="$property" :months="2" class="mt-5" />
            </section>

            <section class="mt-9 border-t border-stone-200 pt-7">
                <h2 class="text-title text-navy-900">{{ __('Règles de séjour') }}</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li class="flex items-center gap-2.5 text-sm text-navy-700">
                        <x-ui.icon name="key" class="size-4.5 text-navy-400" />
                        {{ __('Arrivée à partir de :time', ['time' => Carbon::parse($property->checkin_time)->format('H:i')]) }}
                    </li>
                    <li class="flex items-center gap-2.5 text-sm text-navy-700">
                        <x-ui.icon name="key" class="size-4.5 text-navy-400" />
                        {{ __('Départ avant :time', ['time' => Carbon::parse($property->checkout_time)->format('H:i')]) }}
                    </li>
                    <li class="flex items-center gap-2.5 text-sm text-navy-700">
                        <x-ui.icon name="calendar" class="size-4.5 text-navy-400" />
                        {{ trans_choice('Séjour de :count nuit minimum|Séjour de :count nuits minimum', $property->min_nights, ['count' => $property->min_nights]) }}
                    </li>
                    @foreach ([
                        [__('Animaux acceptés'), __('Animaux non acceptés'), $property->pets_allowed, 'baby'],
                        [__('Fêtes autorisées'), __('Fêtes non autorisées'), $property->parties_allowed, 'sparkles'],
                        [__('Fumeur autorisé'), __('Non-fumeur'), $property->smoking_allowed, 'flame'],
                    ] as [$yes, $no, $allowed, $icon])
                        <li class="flex items-center gap-2.5 text-sm text-navy-700">
                            <x-ui.icon :name="$icon" class="size-4.5 text-navy-400" />
                            {{ $allowed ? $yes : $no }}
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="mt-9 border-t border-stone-200 pt-7">
                <h2 class="text-title text-navy-900">{{ __('Emplacement') }}</h2>
                <p class="mt-3 leading-relaxed text-navy-600">
                    {{ __('Quartier de :neighborhood, à :destination.', [
                        'neighborhood' => $property->neighborhood ?: $property->destination?->name,
                        'destination' => $property->destination?->name,
                    ]) }}
                </p>
                <p class="mt-2 flex items-start gap-2 text-sm text-navy-500">
                    <x-ui.icon name="shield-check" class="mt-0.5 size-4 shrink-0 text-navy-400" />
                    {{ __('L\'adresse exacte est communiquée après confirmation de la réservation.') }}
                </p>
            </section>

            <section class="mt-9 border-t border-stone-200 pt-7">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="text-title text-navy-900">{{ __('Avis des voyageurs') }}</h2>
                    <x-ui.rating :value="$property->rating_avg" :count="$property->reviews_count ?: null" />
                </div>

                @if ($reviews->isEmpty())
                    <p class="mt-4 text-navy-500">{{ __('Cette villa n\'a pas encore reçu d\'avis.') }}</p>
                @else
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($reviews as $review)
                            <article class="rounded-card border border-stone-200 bg-white p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-navy-900">
                                        {{ $review->user?->first_name }} {{ mb_substr((string) $review->user?->last_name, 0, 1) }}.
                                    </p>
                                    <x-ui.rating :value="$review->overall" />
                                </div>
                                <p class="mt-1 text-xs text-navy-400">{{ $review->published_at?->translatedFormat('F Y') }}</p>
                                @if ($review->comment)
                                    <p class="mt-3 text-sm leading-relaxed text-navy-600">{{ $review->comment }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- ------------------------------------------------- Carte réservation --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-card border border-stone-200 bg-white p-5 shadow-card">
                <p class="text-navy-900">
                    {{--
                        Le prix en tête reflète le tarif réellement appliqué sur les
                        dates du devis (moyenne des nuits, tarifs de saison compris),
                        jamais le tarif de base brut : afficher 450 000 en titre puis
                        360 000 dans le détail — parce qu'un tarif de saison couvre la
                        période — ferait deux chiffres qui ne se recoupent jamais aux
                        yeux du client.
                    --}}
                    <span class="text-2xl font-bold tabular">
                        {{ ($quote?->averageNightly() ?? $property->base_price)->format(withCurrency: false) }}
                    </span>
                    <span class="font-medium">FCFA</span>
                    <span class="text-sm text-navy-400">{{ __('/ nuit') }}</span>
                </p>

                @if ($quote && $quote->averageNightly()->amount !== $property->base_price->amount)
                    <p class="mt-0.5 text-xs text-navy-400">
                        {{ __('Tarif moyen pour ces dates — le prix de base est de :base FCFA / nuit.', [
                            'base' => $property->base_price->format(withCurrency: false),
                        ]) }}
                    </p>
                @endif

                @if ($quote)
                    <p class="mt-1 text-sm text-navy-500">
                        {{ __('Prix total pour :nights : :total', [
                            'nights' => trans_choice(':count nuit|:count nuits', $quote->nightCount, ['count' => $quote->nightCount]),
                            'total' => $quote->total->format(),
                        ]) }}
                        @unless ($requestedDates)
                            <span class="text-navy-400">{{ __('(estimation)') }}</span>
                        @endunless
                    </p>
                @endif

                {{-- Choix des dates : recharge la fiche avec le devis correspondant. --}}
                <form method="GET" action="{{ route('villas.show', [$property->destination, $property]) }}" class="mt-4 flex flex-col gap-2">
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.input type="date" name="checkin" :label="__('Arrivée')"
                                    :min="now()->toDateString()"
                                    :value="$requestedDates ? $quote->checkin : null" />
                        <x-ui.input type="date" name="checkout" :label="__('Départ')"
                                    :min="now()->addDay()->toDateString()"
                                    :value="$requestedDates ? $quote->checkout : null" />
                    </div>

                    <x-ui.select name="guests" :label="__('Voyageurs')" icon="users">
                        @foreach (range(1, max(1, $property->capacity)) as $count)
                            <option value="{{ $count }}" @selected($quote && $quote->guests === $count)>
                                {{ trans_choice(':count voyageur|:count voyageurs', $count, ['count' => $count]) }}
                            </option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.button type="submit" variant="outline" size="sm" class="mt-1">
                        {{ __('Calculer le prix') }}
                    </x-ui.button>
                </form>

                @if ($quote)
                    <dl class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-navy-500">
                                {{ __(':average × :nights', [
                                    'average' => $quote->averageNightly()->format(),
                                    'nights' => trans_choice(':count nuit|:count nuits', $quote->nightCount, ['count' => $quote->nightCount]),
                                ]) }}
                            </dt>
                            <dd class="text-navy-900 tabular">{{ $quote->nightlySubtotal->format() }}</dd>
                        </div>
                        @unless ($quote->cleaningFee->isZero())
                            <div class="flex justify-between gap-3">
                                <dt class="text-navy-500">{{ __('Frais de ménage') }}</dt>
                                <dd class="text-navy-900 tabular">{{ $quote->cleaningFee->format() }}</dd>
                            </div>
                        @endunless
                        <div class="flex justify-between gap-3">
                            <dt class="text-navy-500">{{ __('Frais de service') }}</dt>
                            <dd class="text-navy-900 tabular">{{ $quote->serviceFee->format() }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-stone-200 pt-2 font-semibold">
                            <dt class="text-navy-900">{{ __('Total') }}</dt>
                            <dd class="text-navy-900 tabular">{{ $quote->total->format() }}</dd>
                        </div>
                        @unless ($quote->securityDeposit->isZero())
                            <div class="flex justify-between gap-3 text-xs">
                                <dt class="text-navy-400">{{ __('Caution, restituée après le séjour') }}</dt>
                                <dd class="text-navy-500 tabular">{{ $quote->securityDeposit->format() }}</dd>
                            </div>
                        @endunless
                    </dl>
                @endif

                <div class="mt-5 flex flex-col gap-2 border-t border-stone-200 pt-5">
                    @if (session('error'))
                        <x-ui.alert variant="danger" class="mb-1">{{ session('error') }}</x-ui.alert>
                    @endif

                    @auth
                        @if ($requestedDates)
                            {{-- Seules les dates et le nombre de voyageurs traversent :
                                 le montant est recalculé côté serveur, jamais accepté du formulaire. --}}
                            <form method="POST" action="{{ route('bookings.store', $property) }}">
                                @csrf
                                <input type="hidden" name="checkin" value="{{ $quote->checkin }}">
                                <input type="hidden" name="checkout" value="{{ $quote->checkout }}">
                                <input type="hidden" name="guests" value="{{ $quote->guests }}">
                                <x-ui.button type="submit" size="lg" icon="calendar" class="flex w-full">
                                    {{ __('Réserver ces dates') }}
                                </x-ui.button>
                            </form>
                        @else
                            <x-ui.button size="lg" icon="calendar" class="flex w-full" disabled
                                         title="{{ __('Choisissez vos dates ci-dessus') }}">
                                {{ __('Choisissez vos dates') }}
                            </x-ui.button>
                        @endif
                    @else
                        <x-ui.button size="lg" icon="calendar" class="flex w-full"
                                     :href="route('login', ['redirect' => request()->fullUrl()])">
                            {{ __('Se connecter pour réserver') }}
                        </x-ui.button>
                    @endauth

                    @if ($whatsapp)
                        <x-whatsapp-button :message="$bookingBody" variant="outline" size="lg" class="w-full">
                            {{ __('Discuter sur WhatsApp') }}
                        </x-whatsapp-button>
                    @endif
                </div>

                <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-navy-400">
                    <x-ui.icon name="message-circle" class="size-3.5" />
                    {{ __('Réponse en moins de 2 heures') }}
                </p>
            </div>

            {{-- ------------------------------------------------------- L'hôte --}}
            @if ($property->owner)
                <div class="mt-4 rounded-card border border-stone-200 bg-white p-5">
                    <p class="text-xs uppercase tracking-wider text-navy-400">{{ __('Proposé par') }}</p>
                    <div class="mt-2.5 flex items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-navy-800 text-base font-semibold text-white">
                            {{ mb_substr($property->owner->first_name, 0, 1) }}
                        </span>
                        <div>
                            {{-- Seul le prénom est public : nom, téléphone et adresse
                                 du propriétaire restent en administration. --}}
                            <p class="font-semibold text-navy-900">{{ $property->owner->first_name }}</p>
                            <p class="text-sm text-navy-500">{{ __('Hôte professionnel') }}</p>
                        </div>
                    </div>
                    <p class="mt-3 flex items-center gap-1.5 text-xs text-navy-400">
                        <x-ui.icon name="badge-check" class="size-3.5" />
                        {{ __('Membre depuis :date', ['date' => $property->owner->created_at->translatedFormat('F Y')]) }}
                    </p>
                    <p class="mt-3 border-t border-stone-200 pt-3 text-xs leading-relaxed text-navy-400">
                        {{ __('Les échanges passent par notre équipe, qui assure le lien avec l\'hôte.') }}
                    </p>
                </div>
            @endif
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="border-t border-stone-200 bg-stone-50 py-12">
            <div class="container-page">
                <h2 class="text-title text-navy-900">
                    {{ __('Autres villas à :destination', ['destination' => $property->destination?->name]) }}
                </h2>
                <div class="mt-6 grid gap-x-6 gap-y-9 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similar as $other)
                        <x-villa-card :property="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
