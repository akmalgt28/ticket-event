<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Resources\TicketResource;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;

class CheckInController extends Controller
{
    public function store(CheckInRequest $request, CheckInService $checkIn): JsonResponse
    {
        $ticket = $checkIn->checkIn($request->input('token'), $request->user(), $request->input('ticket_number'));

        return (new TicketResource($ticket))->response();
    }
}
