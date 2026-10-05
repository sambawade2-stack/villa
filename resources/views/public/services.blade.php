@php
    $services = [
        ['plane', __('Transfert aéroport'), __('Accueil à l\'aéroport Blaise-Diagne et transfert privé jusqu\'à votre villa, de jour comme de nuit. Le chauffeur suit votre vol.'), __('à partir de 35 000 FCFA')],
        ['car', __('Chauffeur'), __('Un chauffeur à la journée ou pour la durée du séjour, pour vos déplacements sur la Petite Côte et vers Dakar.'), __('à partir de 10 000 FCFA / jour')],
        ['chef-hat', __('Chef privé'), __('Cuisine sénégalaise ou internationale préparée dans la villa. Marché fait, service assuré, cuisine rendue propre.'), __('à partir de 5 000 FCFA / repas')],
        ['sparkles', __('Ménage et blanchisserie'), __('Entretien quotidien ou ponctuel, changement du linge, repassage. Une équipe déjà connue de la maison.'), __('à partir de 15 000 FCFA')],
        ['key', __('Location de voiture'), __('Véhicule livré à la villa avec assurance et assistance. Berline, 4×4 ou minibus selon le groupe.'), __('à partir de 30 000 FCFA / jour')],
        ['compass', __('Excursions'), __('Lagune de la Somone en pirogue, île aux coquillages de Fadiouth, réserve de Bandia, Lac Rose.'), __('sur devis')],
        ['utensils', __('Réservation de tables'), __('Nous réservons pour vous dans les meilleures adresses de Saly, Ngaparou et Somone.'), __('offert')],
        ['users', __('Personnel de maison'), __('Gardien, cuisinière, femme de ménage : du personnel présent pendant tout le séjour.'), __('sur devis')],
    ];
@endphp

<x-layouts.public
    :title="__('Services — Petite Côte Villas')"
    :description="__('Transfert aéroport, chauffeur, chef privé, ménage, location de voiture et excursions : les services que nous organisons autour de votre séjour.')"
>
    <div class="border-b border-stone-200 bg-stone-50">
        <div class="container-page py-10">
            <x-ui.breadcrumb :items="[['label' => __('Accueil'), 'url' => route('home')], ['label' => __('Services')]]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Services à la carte') }}</h1>
            <p class="mt-2 max-w-2xl leading-relaxed text-navy-500">
                {{ __('Tous nos services sont assurés par des prestataires locaux que nous connaissons et suivons. Ils se réservent avant l\'arrivée ou pendant le séjour, à la demande.') }}
            </p>
        </div>
    </div>

    <div class="container-page py-12">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as [$icon, $title, $text, $price])
                <article class="flex flex-col gap-3 rounded-card border border-stone-200 bg-white p-6">
                    <span class="flex size-11 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                        <x-ui.icon :name="$icon" class="size-5" />
                    </span>
                    <h2 class="text-base font-semibold text-navy-900">{{ $title }}</h2>
                    <p class="text-sm leading-relaxed text-navy-500">{{ $text }}</p>
                    <p class="mt-auto pt-2 text-sm font-medium text-navy-700">{{ $price }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-10 rounded-block bg-navy-900 px-6 py-10 sm:px-10">
            <div class="grid items-center gap-6 lg:grid-cols-[1fr_auto]">
                <div class="max-w-2xl">
                    <h2 class="text-2xl text-white">{{ __('Un besoin qui n\'est pas dans la liste ?') }}</h2>
                    <p class="mt-2 leading-relaxed text-white/75">
                        {{ __('Anniversaire, séminaire, mariage, séjour longue durée : dites-nous ce que vous avez en tête, nous organisons le reste.') }}
                    </p>
                </div>
                <x-ui.button :href="route('contact')" variant="amber" size="lg" icon="message-circle">
                    {{ __('Nous écrire') }}
                </x-ui.button>
            </div>
        </div>

        <p class="mt-6 text-xs text-navy-400">
            {{ __('Tarifs indicatifs, confirmés au devis. Les services ne sont pas inclus dans le prix de la villa.') }}
        </p>
    </div>
</x-layouts.public>
