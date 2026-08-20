@php use App\Enums\ReviewStatus; @endphp

<x-layouts.admin :title="__('Avis')" :heading="__('Modération des avis')">
    <x-admin.filter-tabs route="admin.reviews.index" :current="$status" :counts="$counts"
                         :options="collect(ReviewStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />

    <div class="mt-5 flex flex-col gap-4">
        @forelse ($reviews as $review)
            <x-admin.panel>
                <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.rating :value="$review->overall" />
                            <span class="text-sm font-medium text-navy-900">{{ $review->user?->full_name }}</span>
                            <span class="text-sm text-navy-400">{{ $review->property?->name }}</span>
                            <span class="text-xs text-navy-400 tabular">{{ $review->booking?->reference }}</span>
                            <x-ui.badge :variant="$review->status->color()">{{ $review->status->label() }}</x-ui.badge>
                        </div>

                        @if ($review->comment)
                            <p class="mt-3 text-sm leading-relaxed text-navy-600">{{ $review->comment }}</p>
                        @endif

                        <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-navy-400">
                            @foreach (\App\Models\Review::CRITERIA as $criterion)
                                <li>
                                    {{ __('reviews.criteria.'.$criterion) }} :
                                    <span class="font-medium text-navy-600 tabular">{{ $review->{$criterion} }}/5</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('admin.reviews.update', $review) }}"
                          class="flex w-full shrink-0 flex-col gap-2 sm:w-64">
                        @csrf @method('PATCH')
                        <x-ui.select name="status" :label="__('Décision')">
                            @foreach (ReviewStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($review->status === $case)>{{ $case->label() }}</option>
                            @endforeach
                        </x-ui.select>
                        <textarea name="admin_reply" rows="2" maxlength="2000"
                                  placeholder="{{ __('Réponse publique (facultatif)') }}"
                                  class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-navy-500">{{ $review->admin_reply }}</textarea>
                        <x-ui.button type="submit" size="sm">{{ __('Enregistrer') }}</x-ui.button>
                    </form>
                </div>
            </x-admin.panel>
        @empty
            <x-ui.empty-state icon="star" :title="__('Aucun avis dans cette vue')" />
        @endforelse
    </div>

    <div class="mt-6">{{ $reviews->links() }}</div>
</x-layouts.admin>
