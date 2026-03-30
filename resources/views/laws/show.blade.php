@extends('layouts.app')

@section('title', 'Law '.$law->law_number)

@section('content')
    <a class="back-link" href="{{ route('laws.index') }}">Back to all laws</a>

    <div class="law-detail-shell">
        <section class="hero">
            <p class="eyebrow">Law {{ $law->law_number }}</p>
            <h1>{{ $law->displayTitle() }}</h1>
            <p>
                Read the law in a structured format with nested sections, supporting text, diagrams,
                and related video examples where available.
            </p>
            <div class="law-detail-meta">
                <span class="law-detail-pill">Language: {{ strtoupper($language) }}</span>
                <span class="law-detail-pill">Slug: {{ $law->slug }}</span>
            </div>
        </section>

        @if (count($tableOfContents) > 0)
            <details class="card law-detail-mobile-toc">
                <summary class="toc-summary">Table of contents</summary>
                <div style="margin-top: 1rem;">
                    @include('laws.partials.toc', ['items' => $tableOfContents])
                </div>
            </details>
        @endif

        <div class="law-detail-grid">
            @if (count($tableOfContents) > 0)
                <aside class="card toc-card">
                    <div>
                        <p class="eyebrow">Contents</p>
                        <h2 class="toc-title">On this page</h2>
                    </div>

                    @include('laws.partials.toc', ['items' => $tableOfContents])
                </aside>
            @endif

            <section class="card law-content">
                @forelse ($tree as $node)
                    @include('laws.partials.node', ['node' => $node])
                @empty
                    <p class="law-meta">This law has no published content yet.</p>
                @endforelse
            </section>
        </div>
    </div>

    @if (count($tableOfContents) > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tocLinks = Array.from(document.querySelectorAll('.toc-link[data-anchor]'));
                const headings = tocLinks
                    .map((link) => document.getElementById(link.dataset.anchor))
                    .filter(Boolean);

                if (! headings.length) {
                    return;
                }

                const setActive = (id) => {
                    tocLinks.forEach((link) => {
                        link.classList.toggle('is-active', link.dataset.anchor === id);
                    });
                };

                const observer = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

                    if (visible.length > 0) {
                        setActive(visible[0].target.id);
                    }
                }, {
                    rootMargin: '-20% 0px -65% 0px',
                    threshold: [0, 1],
                });

                headings.forEach((heading) => observer.observe(heading));

                if (headings[0]) {
                    setActive(headings[0].id);
                }
            });
        </script>
    @endif
@endsection
