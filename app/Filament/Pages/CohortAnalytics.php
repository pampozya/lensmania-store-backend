<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\User;
use Filament\Pages\Page;

final class CohortAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Cohort Analytics';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?string $title = 'Cohort Analytics';
    protected static string $view = 'filament.pages.analytics-report';

    public function getSections(): array
    {
        $rows = User::query()->latest('created_at')->get()
            ->groupBy(fn (User $user): string => optional($user->created_at)->format('Y-m') ?: 'Unknown')
            ->map(function ($users, string $month): array {
                $userIds = $users->pluck('id');
                $orders = Order::query()->whereIn('user_id', $userIds)->where(function ($query): void {
                    $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
                })->get();

                return [
                    $month,
                    (string) $users->count(),
                    (string) $orders->count(),
                    '$' . number_format($orders->sum(fn (Order $order): float => $order->amount_usd !== null ? (float) $order->amount_usd : (((int) $order->amount_cents) / 100)), 2),
                    $users->count() > 0 ? number_format(($orders->pluck('user_id')->unique()->count() / $users->count()) * 100, 1) . '%' : '0.0%',
                ];
            })
            ->sortByDesc(fn (array $row): string => $row[0])
            ->values()
            ->all();

        return [[
            'title' => 'Signup cohorts',
            'description' => 'Conversion and revenue by signup month.',
            'columns' => ['Signup month', 'Users', 'Paid orders', 'Revenue', 'Buyer conversion'],
            'rows' => $rows,
        ]];
    }
}
