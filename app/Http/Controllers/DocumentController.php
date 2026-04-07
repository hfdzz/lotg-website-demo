<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function show(Request $request, Document $document): View|RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $document->load('publishedPages');

        if ($document->status !== 'published') {
            return redirect()->route('laws.index', ['lang' => $language]);
        }

        if ($document->isCollection()) {
            $firstPage = $document->firstPublishedPage();

            if (! $firstPage) {
                return redirect()->route('laws.index', ['lang' => $language]);
            }

            return redirect()->route('documents.page', [
                'document' => $document,
                'page' => $firstPage->slug,
                'lang' => $language,
            ]);
        }

        return view('documents.show', [
            'document' => $document,
            'page' => $document->firstPublishedPage(),
            'pages' => collect(),
            'language' => $language,
            ...$this->hubData(),
        ]);
    }

    public function page(Request $request, Document $document, string $page): View|RedirectResponse
    {
        $language = LotgLanguage::normalize((string) $request->query('lang', LotgLanguage::default()));
        $document->load('publishedPages');

        if ($document->status !== 'published') {
            return redirect()->route('laws.index', ['lang' => $language]);
        }

        if (! $document->isCollection()) {
            return redirect()->route('documents.show', ['document' => $document, 'lang' => $language]);
        }

        $selectedPage = $document->publishedPages->firstWhere('slug', $page);

        if (! $selectedPage) {
            return redirect()->route('documents.show', ['document' => $document, 'lang' => $language]);
        }

        return view('documents.show', [
            'document' => $document,
            'page' => $selectedPage,
            'pages' => $document->publishedPages,
            'language' => $language,
            ...$this->hubData(),
        ]);
    }

    protected function hubData(): array
    {
        return [
            'hubDocuments' => Document::query()
                ->published()
                ->with('publishedPages')
                ->orderBy('sort_order')
                ->get(),
        ];
    }
}
