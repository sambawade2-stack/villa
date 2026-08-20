@props(['headers' => [], 'empty' => null])

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-stone-200 bg-stone-50 text-left text-xs uppercase tracking-wider text-navy-400">
                @foreach ($headers as $header)
                    <th class="px-4 py-3 font-medium {{ str_ends_with((string) $header, '|right') ? 'text-right' : '' }}">
                        {{ str_replace('|right', '', (string) $header) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            {{ $slot }}
        </tbody>
    </table>
</div>
