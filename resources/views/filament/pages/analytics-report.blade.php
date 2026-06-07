<x-filament-panels::page>
    @php
        $cards = method_exists($this, 'getCards') ? $this->getCards() : [];
        $sections = method_exists($this, 'getSections') ? $this->getSections() : [];
    @endphp

    @if (count($cards) > 0)
        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($cards as $card)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                    @if (! empty($card['hint']))
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6">
        @foreach ($sections as $section)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $section['title'] }}</h2>
                    @if (! empty($section['description']))
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $section['description'] }}</p>
                    @endif
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                @foreach ($section['columns'] as $column)
                                    <th class="py-2 pr-4">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @forelse ($section['rows'] as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td class="py-2 pr-4 text-gray-800 dark:text-gray-200">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($section['columns']) }}" class="py-4 text-center text-gray-500">
                                        No data yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
