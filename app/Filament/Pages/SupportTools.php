<?php

namespace App\Filament\Pages;

use App\Models\License;
use App\Models\Order;
use App\Models\User;
use Filament\Pages\Page;

final class SupportTools extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Support Tools';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $title = 'Support Tools';

    protected static string $view = 'filament.pages.support-tools';

    public string $search = '';

    public function getCustomers(): array
    {
        $term = trim($this->search);

        if ($term === '') {
            return [];
        }

        return User::query()
            ->where(function ($query) use ($term): void {
                $query->where('email', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            })
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created' => optional($user->created_at)->format('M j, Y H:i') ?? '-',
            ])
            ->all();
    }

    public function getOrders(): array
    {
        $term = trim($this->search);

        if ($term === '') {
            return [];
        }

        return Order::query()
            ->with('user')
            ->where(function ($query) use ($term): void {
                $query->where('id', is_numeric($term) ? (int) $term : 0)
                    ->orWhere('paypal_order_id', 'like', "%{$term}%")
                    ->orWhere('paypal_capture_id', 'like', "%{$term}%")
                    ->orWhere('paypal_payment_id', 'like', "%{$term}%")
                    ->orWhere('promo_code', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$term}%"));
            })
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'customer' => $order->user?->email ?? 'Unknown',
                'product' => $order->product_slug ?: $order->product_name,
                'status' => $order->status . ' / ' . $order->api_status,
                'paypal' => $order->paypal_capture_id ?: ($order->paypal_payment_id ?: ($order->paypal_order_id ?: '-')),
            ])
            ->all();
    }

    public function getLicenses(): array
    {
        $term = trim($this->search);

        if ($term === '') {
            return [];
        }

        return License::query()
            ->with(['user', 'product'])
            ->where(function ($query) use ($term): void {
                $query->where('license_key', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$term}%"));
            })
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (License $license): array => [
                'key' => $license->license_key,
                'customer' => $license->user?->email ?? 'Unknown',
                'product' => $license->product?->name ?? 'Unknown',
                'status' => $license->status,
            ])
            ->all();
    }
}
