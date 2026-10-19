<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Services\GuestCheckoutService;
use Illuminate\Http\JsonResponse;

class GuestCheckoutController extends Controller
{
    public function store(CheckoutRequest $request, GuestCheckoutService $checkout): JsonResponse
    {
        $order = $checkout->create(Event::query()->findOrFail($request->integer('event_id')), $request->validated());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
