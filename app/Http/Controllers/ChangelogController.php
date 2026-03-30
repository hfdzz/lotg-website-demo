<?php

namespace App\Http\Controllers;

use App\Models\ChangelogEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function index(Request $request): View
    {
        $language = (string) $request->query('lang', 'en');

        $entries = ChangelogEntry::published()
            ->where('language_code', $language)
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->get();

        return view('updates.index', [
            'entries' => $entries,
            'language' => $language,
        ]);
    }
}
