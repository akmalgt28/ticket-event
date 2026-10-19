<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderExpirationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire')]
#[Description('Expire unpaid orders and release reserved ticket quota')]
class ExpireOrders extends Command
{
    public function handle(OrderExpirationService $expiration): int
    {
        $expired = 0;

        Order::query()
            ->where('order_status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->pluck('id')
            ->each(function (int $orderId) use ($expiration, &$expired): void {
                if ($expiration->expire(Order::query()->findOrFail($orderId))) {
                    $expired++;
                }
            });

        $this->info("Expired {$expired} order(s).");

        return self::SUCCESS;
    }
}
