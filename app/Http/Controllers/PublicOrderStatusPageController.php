<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicOrderStatusPageController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->routeIs('public.orders.payment') && ! $request->session()->has('public_checkout')) {
            return to_route('public.orders.status');
        }

        return Inertia::render('Public/OrderStatus', [
            'checkout' => $request->session()->get('public_checkout'),
            'mode' => $request->routeIs('public.orders.payment') ? 'payment' : 'status',
        ]);
    }
}
