<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyHouseholdController extends Controller
{
    public function __invoke(Request $request): View
    {
        $resident = $request->user()->resident()->with('household.residents')->first();

        return view('management.my-household', ['household' => $resident?->household]);
    }
}
