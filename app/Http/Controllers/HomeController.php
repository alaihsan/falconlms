<?php

namespace App\Http\Controllers;

use App\Support\CreationContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the home page (Beranda).
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Home', [
            'context' => CreationContext::personalLibrary($request->user())->toArray(),
        ]);
    }
}
