<?php

namespace App\Http\Controllers;

use App\Models\Law;
use App\Services\LawTreeBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LawController extends Controller
{
    public function index(): View
    {
        $laws = Law::published()
            ->orderBy('sort_order')
            ->get();

        return view('laws.index', [
            'laws' => $laws,
        ]);
    }

    public function show(Request $request, Law $law, LawTreeBuilder $treeBuilder): View
    {
        abort_unless($law->status === 'published', 404);

        $language = (string) $request->query('lang', 'en');

        return view('laws.show', [
            'law' => $law,
            'language' => $language,
            'tree' => $treeBuilder->build($law, $language),
        ]);
    }
}
