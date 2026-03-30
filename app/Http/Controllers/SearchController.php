<?php

namespace App\Http\Controllers;

use App\Models\ContentNodeTranslation;
use App\Models\Law;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $language = (string) $request->query('lang', 'en');

        $lawMatches = collect();
        $contentMatches = collect();

        if ($query !== '') {
            $lawMatches = Law::published()
                ->where('law_number', 'like', '%'.$query.'%')
                ->orderBy('sort_order')
                ->get();

            $contentMatches = ContentNodeTranslation::query()
                ->select('content_node_translations.*')
                ->with(['contentNode.law'])
                ->join('content_nodes', 'content_nodes.id', '=', 'content_node_translations.content_node_id')
                ->join('laws', 'laws.id', '=', 'content_nodes.law_id')
                ->where('laws.status', 'published')
                ->where('content_nodes.is_published', true)
                ->where('content_node_translations.status', 'published')
                ->where('content_node_translations.language_code', $language)
                ->where(function ($builder) use ($query) {
                    $builder
                        ->where('content_node_translations.title', 'like', '%'.$query.'%')
                        ->orWhere('content_node_translations.body_html', 'like', '%'.$query.'%');
                })
                ->orderBy('laws.sort_order')
                ->orderBy('content_nodes.sort_order')
                ->limit(25)
                ->get()
                ->map(function (ContentNodeTranslation $translation) use ($query) {
                    $plainText = trim(strip_tags((string) $translation->body_html));
                    $title = trim((string) $translation->title);
                    $excerptSource = $title !== '' ? $title.' '.$plainText : $plainText;

                    $translation->search_excerpt = Str::limit($excerptSource, 180);
                    $translation->search_query = $query;

                    return $translation;
                });
        }

        return view('search.index', [
            'query' => $query,
            'language' => $language,
            'lawMatches' => $lawMatches,
            'contentMatches' => $contentMatches,
        ]);
    }
}
