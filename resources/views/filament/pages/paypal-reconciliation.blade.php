<x-filament-panels::page>
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Internal reconciliation view for the latest 50 orders. Rows marked OK have enough local PayPal identifiers to match against PayPal dashboard exports.
        </p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2">Order</th>
                        <th class="py-2">Customer</th>
                        <th class="py-2">Product</th>
                        <th class="py-2">Amount</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">PayPal Order</th>
                        <th class="py-2">Capture/Payment</th>
                        <th class="py-2">Check</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($this->getRows() as $row)
                        <tr>
                            <td class="py-2 text-gray-950 dark:text-white">#{{ $row['id'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['customer'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['product'] }}</td>
                            <td class="py-2 text-gray-950 dark:text-white">{{ $row['amount'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['status'] }}</td>
                            <td class="py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row['paypal_order_id'] }}</td>
                            <td class="py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row['paypal_capture_id'] }}</td>
                            <td class="py-2">
                                @if (count($row['issues']) === 0)
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">OK</span>
                                @else
                                    <span class="text-xs text-red-600 dark:text-red-400">{{ implode('; ', $row['issues']) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-4 text-center text-gray-500">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
