<x-filament-panels::page>
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <label for="paypal-csv" class="text-sm font-medium text-gray-950 dark:text-white">Paste PayPal CSV export</label>
        <textarea id="paypal-csv" wire:model.live.debounce.600ms="csv" rows="8" class="mt-2 block w-full rounded-lg border-gray-300 bg-white font-mono text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white" placeholder="Paste CSV with Transaction ID / Capture ID / Payment ID columns"></textarea>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This importer previews matches only. It does not alter orders until we add an explicit reconciliation action.</p>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
            <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th class="py-2">PayPal ID</th><th class="py-2">Amount</th><th class="py-2">Local order</th><th class="py-2">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($this->getRows() as $row)
                    <tr>@foreach ($row as $cell)<td class="py-2 text-gray-800 dark:text-gray-200">{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-500">Paste a CSV to preview matches.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
