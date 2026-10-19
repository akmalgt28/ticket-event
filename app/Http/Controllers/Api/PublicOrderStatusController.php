<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicOrderStatusRequest;
use App\Http\Resources\PublicOrderStatusResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class PublicOrderStatusController extends Controller
{
    public function __invoke(PublicOrderStatusRequest $request): JsonResponse
    {
        $order = Order::query()
            ->where('order_number', $request->string('order_number')->trim()->toString())
            ->where('customer_email', $request->string('customer_email')->lower()->trim()->toString())
            ->with(['payments:id,order_id,method,amount,status,paid_at'])
            ->first();

        abort_if($order === null, 404, 'Order tidak ditemukan.');

        return (new PublicOrderStatusResource($order))->response();
    }
}
