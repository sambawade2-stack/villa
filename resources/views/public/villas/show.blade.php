@php
    use App\Models\Setting;
    use App\Models\Review;

    $images = $property->images;
    $cover = $property->primaryImage ?? $images->first();
    $grouped = $property->amenities->groupBy('category');

    $categoryLabels = [
        'exterieur' => __('Extérieur'),
        'confort' => __('Confort'),
        'cuisine' => __('Cuisine'),
        'services' => __('Services'),
        'pratique' => __('Pratique'),
        'general' => __('Divers'),
    ];

    $email = Setting::get('contact.email');
    $phone = Setting::get('contact.phone');
    $subject = __('Demande de réservation — :villa', ['villa' => $property->name]);
@endphp

<x-layouts.public
    :title="$property->meta_title ?: $property->name.' — '.$property->destination?->name"
    :description="$property->meta_description ?: Str::limit(strip_tags((string) $property->short_description), 155)"
    :og-image="$cover?->url('hero')"
    og-type="article"
>
    @php
        // Construit hors de la directive : @json() analyse ses arguments en
        // comptant les crochets et trébuche sur un opérateur de décomposition.
        $structuredData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LodgingBusiness',
            'name' => $property->name,
            'description' => (string) $property->short_description,
            'url' => route('villas.show', $property),
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
        {{-- Données structurées : la fiche est le contenu que Google doit comprendre. --}}
        <script type="application/ld+json">
            {!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endpush

    <div class="container-page pt-6">
        <x-ui.breadcrumb :items="[
            ['label' => __('Accueil'), 'url' => route('home')],
            ['label' => __('Villas'), 'url' => route('villas.index')],
            ['label' => (string) $property->destination?->name, 'url' => route('destinations.show', $property->destination)],
            ['label' => $property->name],
        ]" />
    </div>

    {{-- ------------------------------------------------------------- Galerie --}}
    @if ($images->isNotEmpty())
        <section class="container-page mt-5"
                 x-data="{ open: false, index: 0, total: {{ $images->count() }} }"
                 @keydown.window.escape="open = false"
                 @keydown.window.arrow-right="if (open) index = (index + 1) % total"
                 @keydown.window.arrow-left="if (open) index = (index - 1 + total) % total">

            <div class="grid gap-2 overflow-hidden rounded-2xl sm:grid-cols-[2fr_1fr] sm:gap-3">
                <button type="button" @click="index = 0; open = true"
                        class="relative aspect-4/3 overflow-hidden bg-stone-200 sm:aspect-16/11">
                    <img src="{{ $cover->url('hero') }}" srcset="{{ $cover->srcset('card', 'hero') }}"
                         sizes="(min-width: 640px) 62vw, 100vw"
                         alt="{{ $cover->alt?->get() ?? $property->name }}"
                         fetchpriority="high"
                         class="size-full object-cover transition-transform duration-500 hover:scale-[1.02]">
                </button>

                <div class="hidden grid-rows-2 gap-3 sm:grid">
                    @foreach ($images->skip(1)->take(2) as $image)
                        <button type="button" @click="index = {{ $loop->index + 1 }}; open = true"
                                class="relative overflow-hidden bg-stone-200">
                            <img src="{{ $image->url('card') }}" alt="{{ $image->alt?->get() ?? '' }}"
                                 loading="lazy" class="size-full object-cover transition-transform duration-500 hover:scale-[1.03]">
                            @if ($loop->last && $images->count() > 3)
                                <span class="absolute inset-0 flex items-center justify-center bg-navy-950/50 text-sm font-medium text-white">
                                    {{ trans_choice('+ :count photo|+ :count photos', $images->count() - 3, ['count' => $images->count() - 3]) }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Visionneuse --}}
            <div x-show="open" x-cloak
                 class="fixed inset-0 z-50 flex flex-col bg-navy-950/95 p-4"
                 role="dialog" aria-modal="true" :aria-label="'{{ __('Photos de la villa') }}'">
                <div class="flex justify-between text-white">
                    <span class="text-sm tabular" x-text="(index + 1) + ' / ' + total"></span>
                    <button type="button" @click="open = false" class="rounded-full p-2 hover:bg-white/10">
                        <span class="sr-only">{{ __('Fermer') }}</span>
                        <x-ui.icon name="x" class="size-6" />
                    </button>
                </div>

                <div class="flex flex-1 items-center gap-3">
                    <button type="button" @click="index = (index - 1 + total) % total"
                            class="rounded-full p-2 text-white hover:bg-white/10">
                        <span class="sr-only">{{ __('Photo précédente') }}</span>
                        <x-ui.icon name="chevron-left" class="size-7" />
                    </button>

                    @foreach ($images as $image)
                        <img x-show="index === {{ $loop->index }}" x-cloak
                             src="{{ $image->url('hero') }}" alt="{{ $image->alt?->get() ?? '' }}"
                             class="mx-auto max-h-[80vh] w-auto max-w-full rounded-lg object-contain">
                    @endforeach

                    <button type="button" @click="index = (index + 1) % total"
                            class="rounded-full p-2 text-white hover:bg-white/10">
                        <span class="sr-only">{{ __('Photo suivante') }}</span>
                        <x-ui.icon name="chevron-right" class="size-7" />
                    </button>
                </div>
            </div>
        </section>
    @endif

    <div class="container-page mt-8 grid gap-10 pb-8 lg:grid-cols-[1fr_22rem] lg:gap-14">
        {{-- ---------------------------------------------------------- Contenu --}}
        <div class="min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl text-navy-900 lg:text-display">{{ $property->name }}</h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-navy-500">
                        <x-ui.rating :value="$property->rating_avg" :count="$property->reviews_count ?: null" size="lg" />
                        <span aria-hidden="true" class="text-stone-400">·</span>
                        <span class="inline-flex items-center gap-1 text-sm">
                            <x-ui.icon name="map-pin" class="size-4" />
                            {{ $property->neighborhood ? $property->neighborhood.', ' : '' }}{{ $property->destination?->name }}, {{ __('Sénégal') }}
                        </span>
                    </p>
                </div>

                @if ($property->is_verified)
                    <x-ui.badge variant="gold" icon="badge-check">{{ __('Villa vérifiée') }}</x-ui.badge>
                @endif
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-4 border-y border-stone-200 py-5 sm:grid-cols-4">
                @foreach ([
                    ['users', trans_choice(':count voyageur|:count voyageurs', $property->capacity, ['count' => $property->capacity])],
                    ['bed', trans_choice(':count chambre|:count chambres', $property->bedrooms, ['count' => $property->bedrooms])],
                    ['home', trans_choice(':count lit|:count lits', $property->beds, ['count' => $property->beds])],
                    ['bath', trans_choice(':count salle de bain|:count salles de bain', $property->bathrooms, ['count' => $property->bathrooms])],
                ] as [$icon, $label])
                    <div class="flex items-center gap-2.5">
                        <x-ui.icon :name="$icon" class="size-5 text-navy-400" />
                        <dd class="text-sm font-medium text-navy-900">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($property->description?->get())
                <section class="mt-8">
                    <h2 class="text-title text-navy-900">{{ __('À propos de cette villa') }}</h2>
                    <div class="mt-3 space-y-4 leading-relaxed text-navy-600">
                        @foreach (preg_split('/\n\s*\n/', (string) $property->description->get()) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($property->amenities->isNotEmpty())
                <section class="mt-10 border-t border-stone-200 pt-8">
                    <h2 class="text-title text-navy-900">{{ __('Équipements') }}</h2>
                    <div class="mt-4 space-y-6">
                        @foreach ($grouped as $category => $items)
                            <div>
                                <h3 class="font-sans text-xs font-semibold uppercase tracking-wider text-navy-400">
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

            <section class="mt-10 border-t border-stone-200 pt-8">
                <h2 class="text-title text-navy-900">{{ __('Disponibilités') }}</h2>
                <p class="mt-1.5 text-sm text-navy-500">
                    {{ __('Le jour du départ reste réservable : un séjour se termine le matin, le suivant commence l\'après-midi.') }}
                </p>
                <x-availability-calendar :property="$property" :months="2" class="mt-5" />
            </section>

            <section class="mt-10 border-t border-stone-200 pt-8">
                <h2 class="text-title text-navy-900">{{ __('Règles de séjour') }}</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li class="flex items-center gap-2.5 text-sm text-navy-700">
                        <x-ui.icon name="key" class="size-4.5 text-navy-400" />
                        {{ __('Arrivée à partir de :time', ['time' => \Illuminate\Support\Carbon::parse($property->checkin_time)->format('H:i')]) }}
                    </li>
                    <li class="flex items-center gap-2.5 text-sm text-navy-700">
                        <x-ui.icon name="key" class="size-4.5 text-navy-400" />
                        {{ __('Départ avant :time', ['time' => \Illuminate\Support\Carbon::parse($property->checkout_time)->format('H:i')]) }}
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

            <section class="mt-10 border-t border-stone-200 pt-8">
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

            @if ($property->owner)
                <section class="mt-10 border-t border-stone-200 pt-8">
                    <h2 class="text-title text-navy-900">{{ __('Votre hôte') }}</h2>
                    <div class="mt-4 flex items-center gap-4">
                        <span class="flex size-14 items-center justify-center rounded-full bg-navy-900 font-display text-lg text-stone-50">
                            {{ mb_substr($property->owner->first_name, 0, 1) }}
                        </span>
                        <div>
                            {{-- Seul le prénom est public : le nom, le téléphone et
                                 l'adresse du propriétaire restent en administration. --}}
                            <p class="font-sans font-semibold text-navy-900">{{ $property->owner->first_name }}</p>
                            <p class="text-sm text-navy-500">
                                {{ __('Hôte depuis :date', ['date' => $property->owner->created_at->translatedFormat('F Y')]) }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-navy-500">
                        {{ __('Les échanges passent par notre équipe, qui assure le lien avec l\'hôte.') }}
                    </p>
                </section>
            @endif

            <section class="mt-10 border-t border-stone-200 pt-8">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="text-title text-navy-900">{{ __('Avis des voyageurs') }}</h2>
                    <x-ui.rating :value="$property->rating_avg" :count="$property->reviews_count ?: null" />
                </div>

                @if ($reviews->isEmpty())
                    <p class="mt-4 text-navy-500">{{ __('Cette villa n\'a pas encore reçu d\'avis.') }}</p>
                @else
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        @foreach ($reviews as $review)
                            <article class="rounded-card border border-stone-200 bg-white p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-sans text-sm font-semibold text-navy-900">
                                        {{ $review->user?->first_name }} {{ mb_substr((string) $review->user?->last_name, 0, 1) }}.
                                    </p>
                                    <x-ui.rating :value="$review->overall" />
                                </div>
                                <p class="mt-1 text-xs text-navy-400">
                                    {{ $review->published_at?->translatedFormat('F Y') }}
                                </p>
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
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-card">
                <p class="text-navy-900">
                    <span class="text-2xl font-semibold tabular">{{ $property->base_price->format(withCurrency: false) }}</span>
                    <span class="font-medium">FCFA</span>
                    <span class="text-sm text-navy-400">{{ __('/ nuit') }}</span>
                </p>

                <dl class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm">
                    @if (! $property->cleaning_fee->isZero())
                        <div class="flex justify-between">
                            <dt class="text-navy-500">{{ __('Frais de ménage') }}</dt>
                            <dd class="text-navy-900 tabular">{{ $property->cleaning_fee->format() }}</dd>
                        </div>
                    @endif
                    @if (! $property->security_deposit->isZero())
                        <div class="flex justify-between">
                            <dt class="text-navy-500">{{ __('Caution') }}</dt>
                            <dd class="text-navy-900 tabular">{{ $property->security_deposit->format() }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-navy-500">{{ __('Séjour minimum') }}</dt>
                        <dd class="text-navy-900 tabular">
                            {{ trans_choice(':count nuit|:count nuits', $property->min_nights, ['count' => $property->min_nights]) }}
                        </dd>
                    </div>
                </dl>

                {{-- La réservation en ligne arrive à l'étape 08. D'ici là ces boutons
                     mènent à un contact réel, plutôt qu'à un formulaire inopérant. --}}
                <div class="mt-5 flex flex-col gap-2 border-t border-stone-200 pt-5">
                    @if ($email)
                        <x-ui.button size="lg" icon="mail" class="w-full"
                                     :href="'mailto:'.$email.'?subject='.rawurlencode($subject)">
                            {{ __('Demander ces dates') }}
                        </x-ui.button>
                    @endif
                    @if ($phone)
                        <x-ui.button variant="outline" size="lg" icon="phone" class="w-full"
                                     :href="'tel:'.preg_replace('/\s+/', '', $phone)">
                            {{ __('Appeler l\'équipe') }}
                        </x-ui.button>
                    @endif
                </div>

                <p class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-navy-400">
                    <x-ui.icon name="info" class="mt-0.5 size-3.5 shrink-0" />
                    {{ __('La réservation et le paiement en ligne sont en cours de mise en service. Vos dates sont confirmées par notre équipe sous 2 heures.') }}
                </p>
            </div>
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="border-t border-stone-200 bg-white py-14">
            <div class="container-page">
                <h2 class="text-title text-navy-900">
                    {{ __('Autres villas à :destination', ['destination' => $property->destination?->name]) }}
                </h2>
                <div class="mt-6 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similar as $other)
                        <x-villa-card :property="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
