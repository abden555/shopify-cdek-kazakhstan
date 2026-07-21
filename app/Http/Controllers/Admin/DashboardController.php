<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the administrative dashboard.
     */
    public function __invoke(): View
    {
        return view('admin.dashboard.index');
    }
}
