<div class="lotg-hub-nav-shell">
    <details class="lotg-hub-mobile-nav">
        <summary class="toc-summary">{{ __('site.hub.menu_title') }}</summary>
        <div class="stack-top">
            @include('laws.partials.hub-nav-list', [
                'hubDocuments' => $hubDocuments,
                'hubLaws' => $hubLaws ?? collect(),
                'language' => $language,
                'currentKey' => $currentKey ?? 'laws',
            ])
        </div>
    </details>

    <aside class="lotg-hub-nav">
        <h2 class="toc-title">{{ __('site.hub.menu_title') }}</h2>
        @include('laws.partials.hub-nav-list', [
            'hubDocuments' => $hubDocuments,
            'hubLaws' => $hubLaws ?? collect(),
            'language' => $language,
            'currentKey' => $currentKey ?? 'laws',
        ])
    </aside>
</div>
