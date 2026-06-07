<x-filament-panels::page>
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Commission owed = tracked purchase revenue × each affiliate's commission rate. "Ready" means owed commission meets the minimum payout threshold. Set the rate per affiliate in the Affiliates resource.
        </p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2">Code</th>
                        <th class="py-2">Affiliate</th>
                        <th class="py-2">Account</th>
                        <th class="py-2 text-right">Visits</th>
                        <th class="py-2 text-right">Clicks</th>
                        <th class="py-2 text-right">Purchases</th>
                        <th class="py-2 text-right">Revenue</th>
                        <th class="py-2 text-right">Rate</th>
                        <th class="py-2 text-right">Owed</th>
                        <th class="py-2 text-right">Threshold</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($this->getRows() as $row)
                        <tr>
                            <td class="py-2 font-mono text-gray-950 dark:text-white">{{ $row['code'] }}</td>
                            <td class="py-2 text-gray-950 dark:text-white">{{ $row['label'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['account'] }}</td>
                            <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ $row['visits'] }}</td>
                            <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ $row['clicks'] }}</td>
                            <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ $row['purchases'] }}</td>
                            <td class="py-2 text-right text-gray-950 dark:text-white">${{ number_format($row['revenue'], 2) }}</td>
                            <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['commission_rate'], 1) }}%</td>
                            <td class="py-2 text-right font-medium text-gray-950 dark:text-white">${{ number_format($row['commission_owed'], 2) }}</td>
                            <td class="py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($row['threshold'], 2) }}</td>
                            <td class="py-2">
                                @if ($row['ready'])
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">Ready</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">Hold</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="py-4 text-center text-gray-500">No affiliates yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
