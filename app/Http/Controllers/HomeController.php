<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('panel.dashboard');
        }

        return redirect()->route('login');
    }
}
