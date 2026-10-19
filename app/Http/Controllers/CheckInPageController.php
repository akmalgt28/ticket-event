<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class CheckInPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Staff/CheckIn');
    }
}
