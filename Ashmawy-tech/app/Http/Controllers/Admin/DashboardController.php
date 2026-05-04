<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (Auth::user()?->isModerator()) {
            return redirect()->route('admin.orders.index');
        }

        return view('admin.dashboard');
    }
}
