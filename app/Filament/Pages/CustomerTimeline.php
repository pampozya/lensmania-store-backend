<?php

namespace App\Filament\Pages;

use App\Models\DownloadToken;
use App\Models\License;
use App\Models\Order;
use App\Models\SupportNote;
use App\Models\User;
use Filament\Pages\Page;

final class CustomerTimeline extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Customer Timeline';
    protected static ?string $navigationGroup = 'Customers';
    protected static ?string $title = 'Customer Timeline';
    protected static string $view = 'filament.pages.customer-timeline';

    public string $search = '';

    public function getCustomers(): array
    {
        $term = trim($this->search);
        if ($term === '') {
            return [];
        }

        return User::query()
            ->where('email', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name ?: 'Unnamed',
                'email' => $user->email,
                'timeline' => $this->timeline($user),
            ])
            ->all();
    }

    private function timeline(User $user): array
    {
        $rows = collect([[
            'when' => optional($user->created_at)->format('M j, Y H:i') ?? '-',
            'type' => 'signup',
            'detail' => 'Account created',
        ]]);

        Order::query()->where('user_id', $user->id)->latest()->get()->each(function (Order $order) use ($rows): void {
            $rows->push([
                'when' => optional($order->created_at)->format('M j, Y H:i') ?? '-',
                'type' => 'order',
                'detail' => '#' . $order->id . ' ' . ($order->product_slug ?: $order->product_name) . ' · ' . $order->status . '/' . $order->api_status,
            ]);
        });

        License::query()->where('user_id', $user->id)->latest()->get()->each(function (License $license) use ($rows): void {
            $rows->push([
                'when' => optional($license->created_at)->format('M j, Y H:i') ?? '-',
                'type' => 'license',
                'detail' => $license->license_key . ' · ' . $license->status,
            ]);
        });

        DownloadToken::query()->where('user_id', $user->id)->latest()->get()->each(function (DownloadToken $token) use ($rows): void {
            $rows->push([
                'when' => optional($token->created_at)->format('M j, Y H:i') ?? '-',
                'type' => 'download',
                'detail' => ($token->used_at ? 'Used' : 'Issued') . ' token expiring ' . optional($token->expires_at)->format('M j, Y H:i'),
            ]);
        });

        SupportNote::query()->where('user_id', $user->id)->latest()->get()->each(function (SupportNote $note) use ($rows): void {
            $rows->push([
                'when' => optional($note->created_at)->format('M j, Y H:i') ?? '-',
                'type' => 'support',
                'detail' => $note->category . ' · ' . $note->status . ' · ' . str($note->body)->limit(90),
            ]);
        });

        return $rows->sortByDesc('when')->values()->all();
    }
}
