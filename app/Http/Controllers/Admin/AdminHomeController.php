<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Contracts\View\View;

class AdminHomeController extends Controller
{
    public function index(): View
    {
        return view('admin.home', [
            'activeEdition' => Edition::current(),
        ]);
    }
}
