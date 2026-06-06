<x-filament-panels::page>
    <div class="fi-ta overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full text-start divide-y divide-gray-200 dark:divide-white/10">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Promo Code</th>
                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Influencer</th>
                    <th class="px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Sales</th>
                    <th class="px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($this->getRows() as $row)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $row['code'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['label'] }}</td>
                        <td class="px-4 py-3 text-sm text-end text-gray-950 dark:text-white">{{ $row['sales'] }}</td>
                        <td class="px-4 py-3 text-sm text-end font-semibold text-gray-950 dark:text-white">${{ number_format($row['revenue'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
        Revenue counts fulfilled orders only. Use this to calculate influencer commissions.
    </p>
</x-filament-panels::page>
