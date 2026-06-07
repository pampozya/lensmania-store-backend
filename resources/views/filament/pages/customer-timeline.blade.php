<x-filament-panels::page>
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <label for="customer-search" class="text-sm font-medium text-gray-950 dark:text-white">Find customer</label>
        <input id="customer-search" type="search" wire:model.live.debounce.400ms="search" class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white" placeholder="Search name or email">
    </div>

    <div class="grid gap-6">
        @forelse ($this->getCustomers() as $customer)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $customer['name'] }} · {{ $customer['email'] }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th class="py-2">When</th><th class="py-2">Type</th><th class="py-2">Detail</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($customer['timeline'] as $row)
                                <tr><td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['when'] }}</td><td class="py-2 text-gray-950 dark:text-white">{{ $row['type'] }}</td><td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['detail'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl bg-white p-4 text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">Search for a customer to see their timeline.</div>
        @endforelse
    </div>
</x-filament-panels::page>
