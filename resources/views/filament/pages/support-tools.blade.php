<x-filament-panels::page>
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <label class="text-sm font-medium text-gray-950 dark:text-white" for="support-search">Search customer, order, PayPal id, promo, or license</label>
        <input
            id="support-search"
            type="search"
            wire:model.live.debounce.400ms="search"
            class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
            placeholder="customer@email.com, LM-..., PayPal id, promo code"
        />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Customers</h2>
            <div class="mt-3 space-y-3">
                @forelse ($this->getCustomers() as $row)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="font-medium text-gray-950 dark:text-white">{{ $row['name'] ?: 'Unnamed' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $row['email'] }}</p>
                        <p class="text-xs text-gray-500">ID #{{ $row['id'] }} · {{ $row['created'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Enter a search term.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Orders</h2>
            <div class="mt-3 space-y-3">
                @forelse ($this->getOrders() as $row)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="font-medium text-gray-950 dark:text-white">Order #{{ $row['id'] }} · {{ $row['product'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $row['customer'] }}</p>
                        <p class="text-xs text-gray-500">{{ $row['status'] }} · {{ $row['paypal'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Enter a search term.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Licenses</h2>
            <div class="mt-3 space-y-3">
                @forelse ($this->getLicenses() as $row)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="font-mono text-xs text-gray-950 dark:text-white">{{ $row['key'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $row['customer'] }} · {{ $row['product'] }}</p>
                        <p class="text-xs text-gray-500">{{ $row['status'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Enter a search term.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
