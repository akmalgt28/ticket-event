<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentVerificationRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PaymentVerificationController extends Controller
{
    public function store(PaymentVerificationRequest $request, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $order = Order::query()->findOrFail($request->integer('order_id'));
        $payment = $payments->verifyManual($order, $request->validated(), $request->user());

        if ($request->header('X-Inertia')) {
            return back()->with('success', "Payment order {$order->order_number} berhasil diverifikasi.");
        }

        return (new PaymentResource($payment))->response();
    }
}
