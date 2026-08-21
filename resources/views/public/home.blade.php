@php
    /*
     * Visuel de marque de la page d'accueil : une image fixe, pas la photo
     * d'une villa au hasard. Contrairement au fond utilisé auparavant — la
     * couverture de la première villa mise en avant, susceptible de changer
     * à chaque publication — ce visuel est stable et choisi, à la manière
     * d'une couverture de magazine plutôt que d'une fiche produit.
     */
    $heroImage = [
        'src' => asset('images/hero-hero.webp'),
        'srcset' => asset('images/hero-card.webp').' 800w, '.asset('images/hero-hero.webp').' 1536w',
    ];

    $services = [
        ['plane', __('Transfert aéroport'), __('Accueil à Blaise-Diagne et transfert privé jusqu\'à votre villa.')],
        ['car', __('Chauffeur'), __('Un chauffeur à la journée ou pour la durée du séjour.')],
        ['chef-hat', __('Chef privé'), __('Cuisine sénégalaise ou internationale, préparée sur place.')],
        ['sparkles', __('Ménage'), __('Entretien quotidien ou ponctuel, à la demande.')],
        ['key', __('Location de voiture'), __('Véhicule livré à la villa, assurance comprise.')],
        ['compass', __('Excursions'), __('Lagune de la Somone, île de Fadiouth, réserve de Bandia.')],
    ];

    $reasons = [
        ['badge-check', __('Villas sélectionnées'), __('Chaque villa est visitée avant d\'entrer au catalogue. Nous refusons plus de biens que nous n\'en acceptons.')],
        ['shield-check', __('Villas vérifiées'), __('Équipements, photos et adresses contrôlés sur place, pas déclarés à distance.')],
        ['key', __('Réservation sécurisée'), __('Vos dates sont bloquées dès la demande et le paiement n\'est validé qu\'après confirmation.')],
        ['message-circle', __('Assistance locale'), __('Une équipe sur la Petite Côte, joignable avant, pendant et après le séjour.')],
    ];
@endphp

<x-layouts.public
    :title="__('Location de villas sur la Petite Côte du Sénégal — Petite Côte Villas')"
    :description="__('Découvrez et réservez des villas soigneusement sélectionnées à Saly, Mbour, Ngaparou, Somone, Popenguine et Joal-Fadiouth.')"
    :og-image="$heroImage['src']"
    transparent-nav
>
    {{-- ---------------------------------------------------------------- Hero --}}
    <section class="relative isolate flex min-h-[34rem] items-end overflow-hidden bg-navy-900 lg:min-h-[40rem]">
        <img src="{{ $heroImage['src'] }}" srcset="{{ $heroImage['srcset'] }}" sizes="100vw"
             alt="" fetchpriority="high" class="hero-drift absolute inset-0 -z-10 size-full object-cover">
        <div class="scrim-hero absolute inset-0 -z-10"></div>

        <div class="container-page w-full pb-8 pt-32 lg:pb-12">
            <div class="max-w-xl">
                <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-hero">
                    {{ __('Trouvez la villa idéale sur la Petite Côte') }}
                </h1>
                <p class="mt-4 text-lg leading-relaxed text-white/85">
                    {{ __('Vivez l\'exceptionnel à Saly, Ngaparou, Somone, Mbour et alentours.') }}
                </p>
            </div>

            <x-search-box class="mt-8 max-w-5xl" />
        </div>
    </section>

    {{-- -------------------------------------------------------- Destinations --}}
    <section class="container-page py-16 lg:py-24">
        <x-section-header accent class="reveal" :title="__('Destinations populaires')">
            <x-slot:action>
                <x-ui.button :href="route('destinations.index')" variant="ghost" size="sm" icon-after="arrow-right">
                    {{ __('Voir toutes') }}
                </x-ui.button>
            </x-slot:action>
        </x-section-header>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($destinations as $entry)
                <x-destination-card
                    class="reveal card-hover" :data-delay="min($loop->index, 3)"
                    :destination="$entry->destination"
                    :count="$entry->count"
                    :image-url="$entry->imageUrl"
                    :srcset="$entry->srcset"
                    :eager="$loop->index < 3"
                />
            @endforeach
        </div>
    </section>

    {{-- -------------------------------------------------------------- Villas --}}
    <section class="border-y border-stone-200 bg-stone-50 py-16 lg:py-24">
        <div class="container-page">
            <x-section-header accent class="reveal" :title="__('Villas coup de cœur')"
                              :subtitle="__('Nos villas les plus appréciées par les voyageurs.')">
                <x-slot:action>
                    <x-ui.button :href="route('villas.index')" variant="outline" size="sm" icon-after="arrow-right">
                        {{ __('Voir toutes les villas') }}
                    </x-ui.button>
                </x-slot:action>
            </x-section-header>

            @if ($featured->isEmpty())
                <x-ui.empty-state class="mt-8" icon="home" :title="__('Aucune villa publiée pour le moment')">
                    {{ __('Le catalogue s\'étoffe : revenez très bientôt.') }}
                </x-ui.empty-state>
            @else
                <div class="mt-7 grid gap-x-6 gap-y-9 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($featured->take(4) as $property)
                        <x-villa-card :property="$property" :eager="$loop->index < 4"
                                      class="reveal" :data-delay="min($loop->index, 3)" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ------------------------------------------------------ Pourquoi nous --}}
    <section id="pourquoi-nous" class="container-page py-16 lg:py-24">
        <x-section-header accent class="reveal" :title="__('Pourquoi passer par nous')"
                          :subtitle="__('Une sélection tenue à la main, sur le terrain.')" />

        <div class="mt-8 grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($reasons as [$icon, $heading, $text])
                <div class="reveal flex flex-col gap-3" data-delay="{{ min($loop->index, 3) }}">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-amber-50 text-amber-500">
                        <x-ui.icon :name="$icon" class="size-5" />
                    </span>
                    <h3 class="text-base font-semibold text-navy-900">{{ $heading }}</h3>
                    <p class="text-sm leading-relaxed text-navy-500">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ------------------------------------------------------------ Services --}}
    <section id="services" class="border-t border-stone-200 bg-stone-50 py-16 lg:py-24">
        <div class="container-page">
            <x-section-header accent class="reveal" :title="__('Services à la carte')"
                              :subtitle="__('Organisés avec des prestataires locaux, avant ou pendant votre séjour.')">
                <x-slot:action>
                    <x-ui.button :href="route('services')" variant="outline" size="sm" icon-after="arrow-right">
                        {{ __('En savoir plus') }}
                    </x-ui.button>
                </x-slot:action>
            </x-section-header>

            <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as [$icon, $heading, $text])
                    <div class="reveal card-hover flex gap-4 rounded-card border border-stone-200 bg-white p-5" data-delay="{{ min($loop->index, 3) }}">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                            <x-ui.icon :name="$icon" class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-navy-900">{{ $heading }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-navy-500">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------ CTA propriétaire --}}
    <section id="proprietaires" class="container-page py-16 lg:py-24">
        <div class="reveal overflow-hidden rounded-block bg-navy-900 px-6 py-12 sm:px-10 lg:px-16 lg:py-14">
            <div class="grid items-center gap-8 lg:grid-cols-[1fr_auto]">
                <div class="max-w-2xl">
                    <h2 class="text-2xl text-white lg:text-display">{{ __('Vous possédez une villa ?') }}</h2>
                    <p class="mt-3 leading-relaxed text-white/75">
                        {{ __('Nous nous chargeons de tout : photographie, mise en ligne, calendrier, réservations et relation avec les voyageurs. Parlons-en, sans engagement.') }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                    <x-ui.button :href="route('contact')" variant="amber" size="lg" icon="home">
                        {{ __('Déposer ma villa') }}
                    </x-ui.button>
                    @php $phone = \App\Models\Setting::get('contact.phone'); @endphp
                    @if ($phone)
                        <x-ui.button :href="'tel:'.preg_replace('/\s+/', '', $phone)" variant="outline-light" size="lg" icon="phone">
                            {{ $phone }}
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
