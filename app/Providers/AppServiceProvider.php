<?php

namespace App\Providers;

use App\Models\EmailDelivery;
use App\Policies\ReportPolicy;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewReports', [ReportPolicy::class, 'view']);

        Vite::prefetch(concurrency: 3);

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $header = $event->message->getHeaders()->get('X-Ticket-Event-Delivery');

            if (! $header) {
                return;
            }

            EmailDelivery::query()->whereKey((int) $header->getBodyAsString())->update([
                'status' => 'sent',
                'message_id' => $event->sent->getMessageId(),
                'sent_at' => now(),
            ]);
        });
    }
}
