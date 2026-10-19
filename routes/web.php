<?php

use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\PaymentVerificationController;
use App\Http\Controllers\OrganizerDashboardController;
use App\Http\Controllers\OrganizerEventController;
use App\Http\Controllers\OrganizerOrderController;
use App\Http\Controllers\OrganizerParticipantController;
use App\Http\Controllers\OrganizerTicketTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicEventPageController;
use App\Http\Controllers\PublicOrderStatusPageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', OrganizerDashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/events', [PublicEventPageController::class, 'index'])->name('public.events.index');
Route::get('/order-status', PublicOrderStatusPageController::class)->name('public.orders.status');
Route::get('/events/{event:slug}/checkout', [PublicEventPageController::class, 'checkout'])->name('public.events.checkout');
Route::get('/events/{event:slug}', [PublicEventPageController::class, 'show'])->name('public.events.show');

Route::middleware(['auth', 'verified'])->post('/payments/verify', [PaymentVerificationController::class, 'store'])->name('payments.verify');
Route::middleware(['auth', 'verified'])->prefix('organizer')->name('organizer.')->group(function () {
    Route::get('/orders', [OrganizerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrganizerOrderController::class, 'show'])->name('orders.show');
    Route::get('/participants/export', [OrganizerParticipantController::class, 'export'])->name('participants.export');
    Route::get('/participants', [OrganizerParticipantController::class, 'index'])->name('participants.index');
});
Route::middleware(['auth', 'verified'])->prefix('organizer/events')->name('organizer.events.')->group(function () {
    Route::get('/', [OrganizerEventController::class, 'index'])->name('index');
    Route::post('/', [OrganizerEventController::class, 'store'])->name('store');
    Route::get('/create', [OrganizerEventController::class, 'create'])->name('create');
    Route::get('/{event}/edit', [OrganizerEventController::class, 'edit'])->name('edit');
    Route::get('/{event}', [OrganizerEventController::class, 'show'])->name('show');
    Route::patch('/{event}', [OrganizerEventController::class, 'update'])->name('update');
    Route::delete('/{event}', [OrganizerEventController::class, 'destroy'])->name('destroy');
    Route::post('/{event}/publish', [OrganizerEventController::class, 'publish'])->name('publish');
    Route::post('/{event}/close', [OrganizerEventController::class, 'close'])->name('close');
});
Route::middleware(['auth', 'verified'])->post('/check-in', [CheckInController::class, 'store'])->name('check-in.store');
Route::middleware(['auth', 'verified'])->prefix('organizer/events/{event}/ticket-types')->name('organizer.ticket-types.')->group(function () {
    Route::get('/', [OrganizerTicketTypeController::class, 'index'])->name('index');
    Route::post('/', [OrganizerTicketTypeController::class, 'store'])->name('store');
    Route::get('/create', [OrganizerTicketTypeController::class, 'create'])->name('create');
    Route::get('/{ticketType}/edit', [OrganizerTicketTypeController::class, 'edit'])->name('edit');
    Route::get('/{ticketType}', [OrganizerTicketTypeController::class, 'show'])->name('show');
    Route::patch('/{ticketType}', [OrganizerTicketTypeController::class, 'update'])->name('update');
    Route::delete('/{ticketType}', [OrganizerTicketTypeController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
