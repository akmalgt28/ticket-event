<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicOrderStatusRequest;
use App\Http\Resources\PublicOrderStatusResource;
use App\Models\Order;
use App\Services\OrderExpirationService;
use Illuminate\Http\JsonResponse;

class PublicOrderStatusController extends Controller
{
    public function __invoke(PublicOrderStatusRequest $request, OrderExpirationService $expiration): JsonResponse
    {
        $order = Order::query()
            ->where('order_number', $request->string('order_number')->trim()->toString())
            ->whereRaw('LOWER(customer_email) = ?', [$request->string('customer_email')->lower()->trim()->toString()])
            ->first();

        abort_if($order === null, 404, 'Order tidak ditemukan.');

        $expiration->expire($order);
        $order->refresh()->load(['payments' => fn ($query) => $query->latest('id'), 'event']);

        return (new PublicOrderStatusResource($order))->response();
    }
}
