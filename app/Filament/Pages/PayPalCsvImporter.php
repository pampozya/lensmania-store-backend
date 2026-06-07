<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;

final class PayPalCsvImporter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $navigationLabel = 'PayPal CSV Importer';
    protected static ?string $navigationGroup = 'Revenue';
    protected static ?string $title = 'PayPal CSV Importer';
    protected static string $view = 'filament.pages.paypal-csv-importer';

    public string $csv = '';

    public function getRows(): array
    {
        $lines = array_filter(array_map('trim', preg_split('/\R/', trim($this->csv))));
        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));

        return collect($lines)
            ->take(50)
            ->map(function (string $line) use ($headers): array {
                $row = array_combine($headers, str_getcsv($line)) ?: [];
                $paypalId = $row['Transaction ID'] ?? $row['TransactionId'] ?? $row['Capture ID'] ?? $row['Payment ID'] ?? '';
                $amount = $row['Gross'] ?? $row['Amount'] ?? '';
                $order = $paypalId ? Order::query()->where('paypal_capture_id', $paypalId)->orWhere('paypal_payment_id', $paypalId)->orWhere('paypal_order_id', $paypalId)->first() : null;

                return [
                    $paypalId ?: '-',
                    $amount ?: '-',
                    $order ? '#' . $order->id : 'No local match',
                    $order ? $order->status . '/' . $order->api_status : '-',
                ];
            })
            ->all();
    }
}
