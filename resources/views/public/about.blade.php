<x-layouts.public
    :title="__('À propos — Petite Côte Villas')"
    :description="__('Qui nous sommes, comment nous choisissons les villas et ce que nous garantissons aux voyageurs comme aux propriétaires.')"
>
    <div class="border-b border-stone-200 bg-stone-50">
        <div class="container-page py-10">
            <x-ui.breadcrumb :items="[['label' => __('Accueil'), 'url' => route('home')], ['label' => __('À propos')]]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Une sélection tenue à la main') }}</h1>
            <p class="mt-2 max-w-2xl leading-relaxed text-navy-500">
                {{ __('Petite Côte Villas ne référence pas tout ce qui se loue entre Saly et Joal-Fadiouth. Nous visitons, nous vérifions, et nous refusons.') }}
            </p>
        </div>
    </div>

    <div class="container-page py-12">
        <dl class="grid gap-5 sm:grid-cols-3">
            @foreach ([
                [$stats['villas'], __('villas au catalogue')],
                [$stats['destinations'], __('destinations couvertes')],
                [$stats['verified'], __('villas vérifiées sur place')],
            ] as [$value, $label])
                <div class="rounded-card border border-stone-200 bg-white p-6">
                    <dt class="text-3xl font-bold text-navy-900 tabular">{{ $value }}</dt>
                    <dd class="mt-1 text-sm text-navy-500">{{ $label }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-12 grid gap-10 lg:grid-cols-2 lg:gap-16">
            <section>
                <h2 class="text-title text-navy-900">{{ __('Comment une villa entre au catalogue') }}</h2>
                <ol class="mt-5 flex flex-col gap-5">
                    @foreach ([
                        [__('Le premier contact'), __('Un propriétaire nous écrit, ou nous repérons une villa sur place et nous allons frapper à la porte.')],
                        [__('La visite'), __('Nous venons voir la maison. Nous vérifions les équipements annoncés, l\'état de la piscine, l\'eau, l\'électricité, le voisinage.')],
                        [__('La photographie'), __('Les photos sont prises par nos soins. Aucune image d\'agence, aucun rendu d\'architecte : ce que vous voyez est ce que vous louez.')],
                        [__('La mise en ligne'), __('Nous rédigeons la fiche, fixons les tarifs avec le propriétaire et publions. La gestion des réservations reste chez nous.')],
                    ] as $index => [$title, $text])
                        <li class="flex gap-4">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-navy-800 text-sm font-semibold text-white tabular">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-navy-900">{{ $title }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-navy-500">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section>
                <h2 class="text-title text-navy-900">{{ __('Ce que nous garantissons') }}</h2>
                <ul class="mt-5 flex flex-col gap-4">
                    @foreach ([
                        ['badge-check', __('Des photos honnêtes'), __('Prises par nous, à jour, sans grand-angle trompeur.')],
                        ['shield-check', __('Une adresse protégée'), __('L\'emplacement exact n\'est communiqué qu\'après confirmation de la réservation.')],
                        ['key', __('Un prix ferme'), __('Le montant annoncé est celui que vous payez. Les frais éventuels sont affichés avant le paiement.')],
                        ['message-circle', __('Une équipe joignable'), __('Sur la Petite Côte, aux heures sénégalaises, avant, pendant et après le séjour.')],
                    ] as [$icon, $title, $text])
                        <li class="flex gap-4 rounded-card border border-stone-200 bg-white p-5">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-500">
                                <x-ui.icon :name="$icon" class="size-5" />
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-navy-900">{{ $title }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-navy-500">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-layouts.public>
