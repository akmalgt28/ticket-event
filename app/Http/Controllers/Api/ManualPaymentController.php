<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManualPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class ManualPaymentController extends Controller
{
    public function store(Order $order, ManualPaymentRequest $request, PaymentService $payments): JsonResponse
    {
        return (new PaymentResource($payments->selectManualTransfer($order)))
            ->response()
            ->setStatusCode(201);
    }
}
