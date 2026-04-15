<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\MediaAsset;
use Illuminate\Contracts\View\View;

class AdminHomeController extends Controller
{
    public function index(): View
    {
        $this->authorize('access-admin');

        return view('admin.home', [
            'activeEdition' => Edition::current(),
            'canManageMedia' => auth()->user()?->can('viewAny', MediaAsset::class) ?? false,
        ]);
    }
}
