<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ($this->getSummary() as $card)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Revenue by product</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-2">Product</th>
                            <th class="py-2 text-right">Orders</th>
                            <th class="py-2 text-right">Revenue</th>
                            <th class="py-2 text-right">Avg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse ($this->getProductRows() as $row)
                            <tr>
                                <td class="py-2 font-medium text-gray-950 dark:text-white">{{ $row['product'] }}</td>
                                <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ $row['orders'] }}</td>
                                <td class="py-2 text-right text-gray-950 dark:text-white">${{ number_format($row['revenue'], 2) }}</td>
                                <td class="py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($row['average'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-500">No paid orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Recent paid orders</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-2">Order</th>
                            <th class="py-2">Customer</th>
                            <th class="py-2">Product</th>
                            <th class="py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse ($this->getRecentOrders() as $row)
                            <tr>
                                <td class="py-2 text-gray-700 dark:text-gray-300">#{{ $row['id'] }}</td>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['customer'] }}</td>
                                <td class="py-2 text-gray-950 dark:text-white">{{ $row['product'] }}</td>
                                <td class="py-2 text-right text-gray-950 dark:text-white">${{ number_format($row['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-500">No paid orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
