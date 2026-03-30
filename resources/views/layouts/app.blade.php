<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'LotG')</title>
        <style>
            :root {
                --page-bg: #f5efe4;
                --card-bg: #fffaf2;
                --ink: #1f2933;
                --muted: #52606d;
                --line: #d9cdb8;
                --accent: #a53f2b;
                --accent-dark: #6e2518;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Georgia, "Times New Roman", serif;
                background:
                    radial-gradient(circle at top, rgba(165, 63, 43, 0.10), transparent 30%),
                    linear-gradient(180deg, #f8f3ea 0%, var(--page-bg) 100%);
                color: var(--ink);
            }

            html {
                scroll-behavior: smooth;
            }

            a {
                color: inherit;
            }

            .shell {
                width: min(1100px, calc(100% - 2rem));
                margin: 0 auto;
                padding: 2rem 0 4rem;
            }

            .mobile-header {
                display: none;
            }

            .mobile-header-shell {
                position: fixed;
                inset: 0 0 auto 0;
                z-index: 50;
                padding: 0;
                pointer-events: none;
                transform: translateY(0);
                transition: transform 0.18s ease;
            }

            .mobile-header-shell.is-hidden {
                transform: translateY(calc(-100% - 0.5rem));
            }

            .mobile-header-bar {
                width: 100%;
                margin: 0;
                pointer-events: auto;
            }

            .mobile-header-bar {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                gap: 0.75rem;
                align-items: center;
                padding: 0.7rem 0.9rem;
                border-bottom: 1px solid rgba(110, 37, 24, 0.14);
                background: rgba(255, 250, 242, 0.94);
                box-shadow: 0 14px 28px rgba(31, 41, 51, 0.10);
                backdrop-filter: blur(10px);
            }

            .mobile-logo-link {
                display: inline-flex;
                text-decoration: none;
            }

            .mobile-logo {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 3rem;
                height: 2rem;
                border-radius: 0.45rem;
                background: linear-gradient(135deg, #a53f2b, #d98a52);
                color: #fffaf2;
                font-size: 0.78rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                box-shadow: inset 0 0 0 1px rgba(255, 250, 242, 0.35);
            }

            .mobile-header-title {
                display: block;
                overflow: hidden;
                border: 0;
                background: transparent;
                color: var(--accent-dark);
                text-align: left;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: 0.95rem;
                font-weight: 700;
                padding: 0;
                touch-action: manipulation;
            }

            .mobile-header-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 3.2rem;
                padding: 0.55rem 0.8rem;
                border: 1px solid rgba(110, 37, 24, 0.12);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.86);
                color: var(--accent-dark);
                font-size: 1rem;
                font-weight: 700;
                touch-action: manipulation;
            }

            .mobile-header-panel {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 45;
                padding-top: 3.95rem;
                background: rgba(245, 239, 228, 0.98);
                backdrop-filter: blur(10px);
            }

            .mobile-header-tray {
                display: grid;
                gap: 0.85rem;
                width: 100%;
                height: calc(100vh - 4rem);
                margin: 0;
                padding: 0.9rem;
                overflow-y: auto;
                pointer-events: auto;
            }

            .mobile-header-shell.is-open .mobile-header-panel {
                display: grid;
            }

            .mobile-nav-links {
                display: grid;
                gap: 0.55rem;
            }

            .mobile-nav-link {
                display: block;
                padding: 0.8rem 0.9rem;
                border: 1px solid rgba(110, 37, 24, 0.10);
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.78);
                color: var(--accent-dark);
                text-decoration: none;
                font-weight: 700;
                touch-action: manipulation;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .mobile-search-form {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 0.55rem;
                align-items: center;
            }

            .mobile-search-form input {
                min-width: 0;
                margin-top: 0;
            }

            .mobile-search-form button {
                width: 3rem;
                min-width: 3rem;
                height: 3rem;
                padding: 0;
                font-size: 1.1rem;
                line-height: 1;
            }

            .hero {
                margin-bottom: 2rem;
                padding: 2rem;
                border: 1px solid rgba(110, 37, 24, 0.12);
                border-radius: 24px;
                background: linear-gradient(135deg, rgba(255, 250, 242, 0.98), rgba(255, 243, 229, 0.92));
                box-shadow: 0 18px 60px rgba(110, 37, 24, 0.08);
            }

            .nav {
                display: block;
                margin-bottom: 1rem;
            }

            .nav-panel {
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                gap: 1rem;
                align-items: center;
            }

            .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .nav-brand {
                display: inline-flex;
                justify-self: center;
                text-decoration: none;
            }

            .nav-brand-mark {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 4.5rem;
                height: 3rem;
                border-radius: 0.8rem;
                background: linear-gradient(135deg, #a53f2b, #d98a52);
                color: #fffaf2;
                font-size: 1rem;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                box-shadow: inset 0 0 0 1px rgba(255, 250, 242, 0.28);
            }

            .nav-link {
                display: inline-flex;
                align-items: center;
                padding: 0.7rem 1rem;
                border: 1px solid rgba(110, 37, 24, 0.14);
                border-radius: 999px;
                background: rgba(255, 250, 242, 0.86);
                text-decoration: none;
                font-weight: 700;
                color: var(--accent-dark);
            }

            .nav-meta {
                color: var(--muted);
                font-size: 0.9rem;
            }

            .search-form {
                display: flex;
                gap: 0.5rem;
                align-items: center;
            }

            .search-form input {
                min-width: min(22rem, 55vw);
                padding: 0.75rem 0.9rem;
                border: 1px solid var(--line);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.9);
            }

            .search-form button {
                padding: 0.75rem 1rem;
                border: 0;
                border-radius: 999px;
                background: var(--accent);
                color: #fffaf2;
                font-weight: 700;
                cursor: pointer;
            }

            input,
            select,
            textarea,
            button {
                font: inherit;
            }

            input:not([type="checkbox"]):not([type="radio"]),
            select,
            textarea {
                width: 100%;
                margin-top: 0.35rem;
                padding: 0.75rem 0.9rem;
                border: 1px solid var(--line);
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.94);
                color: var(--ink);
            }

            textarea {
                resize: vertical;
            }

            input[type="checkbox"],
            input[type="radio"] {
                width: auto;
                margin-right: 0.4rem;
            }

            button {
                display: inline-flex;
                width: fit-content;
                align-items: center;
                justify-content: center;
                padding: 0.8rem 1.1rem;
                border: 0;
                border-radius: 999px;
                background: var(--accent);
                color: #fffaf2;
                font-weight: 700;
                cursor: pointer;
            }

            .eyebrow {
                margin: 0 0 0.75rem;
                color: var(--accent);
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .hero h1, .hero h2, .hero h3 {
                margin: 0;
                font-weight: 700;
                line-height: 1.05;
            }

            .hero p {
                max-width: 60ch;
                color: var(--muted);
                font-size: 1.05rem;
                line-height: 1.7;
            }

            .card {
                padding: 1.5rem;
                border: 1px solid var(--line);
                border-radius: 20px;
                background: var(--card-bg);
                box-shadow: 0 12px 32px rgba(82, 96, 109, 0.08);
            }

            .law-grid {
                display: grid;
                gap: 1.1rem;
                grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            }

            .law-link {
                display: grid;
                gap: 0.9rem;
                text-decoration: none;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }

            .law-link:hover {
                transform: translateY(-2px);
                box-shadow: 0 16px 36px rgba(82, 96, 109, 0.10);
            }

            .law-link h2 {
                margin: 0;
                font-size: 1.45rem;
                line-height: 1.15;
            }

            .law-number {
                display: inline-flex;
                width: fit-content;
                align-items: center;
                padding: 0.35rem 0.65rem;
                border-radius: 999px;
                background: rgba(165, 63, 43, 0.10);
                color: var(--accent-dark);
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .law-slug {
                font-family: "Courier New", monospace;
                font-size: 0.88rem;
                color: #7b6a5f;
            }

            .law-link-cta {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                color: var(--accent-dark);
                font-weight: 700;
            }

            .law-meta {
                color: var(--muted);
                font-size: 0.95rem;
                line-height: 1.65;
            }

            .law-detail-shell {
                display: grid;
                gap: 1.5rem;
            }

            .law-detail-grid {
                display: grid;
                gap: 1.5rem;
                align-items: start;
                grid-template-columns: minmax(0, 280px) minmax(0, 1fr);
            }

            .law-detail-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .law-detail-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.45rem 0.75rem;
                border: 1px solid rgba(110, 37, 24, 0.12);
                border-radius: 999px;
                background: rgba(255, 250, 242, 0.88);
                color: var(--accent-dark);
                font-size: 0.88rem;
                font-weight: 700;
            }

            .result-list {
                display: grid;
                gap: 1rem;
            }

            .result-section + .result-section {
                margin-top: 1.5rem;
            }

            .result-section-title {
                margin: 0 0 0.85rem;
                font-size: 1.2rem;
                color: var(--accent-dark);
            }

            .result-card {
                padding: 1rem 1.1rem;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.82);
            }

            .result-card h2,
            .result-card h3,
            .result-card h4 {
                line-height: 1.2;
            }

            .result-card h3,
            .result-card h4 {
                margin: 0 0 0.5rem;
            }

            .result-link {
                color: var(--accent-dark);
                text-decoration: none;
            }

            .result-link:hover {
                text-decoration: underline;
            }

            .tree-node {
                margin-top: 0.75rem;
                border-left: 4px solid rgba(165, 63, 43, 0.18);
            }

            .tree-node-children {
                margin-top: 0.35rem;
            }

            .empty-state {
                color: var(--muted);
                line-height: 1.7;
            }

            .node {
                position: relative;
                margin-top: 1.1rem;
                padding: 0.95rem 0 0 0;
            }

            .node[data-type="section"] {
                margin-top: 2.4rem;
                padding: 1.5rem 1.5rem 0.45rem 1.5rem;
                border-radius: 0 22px 22px 0;
                border-top: 1px solid rgba(165, 63, 43, 0.12);
                background: linear-gradient(180deg, rgba(255, 247, 238, 0.98), rgba(255, 255, 255, 0.84));
                box-shadow: 0 10px 26px rgba(82, 96, 109, 0.06);
            }

            .node[data-depth="0"] {
                margin-top: 0;
            }

            .node[data-depth="1"] {
                margin-top: 1.4rem;
                margin-left: 0.4rem;
            }

            .node[data-depth="2"],
            .node[data-depth="3"] {
                margin-left: 0.6rem;
            }

            .node-title {
                margin: 0 0 0.85rem;
                color: var(--accent-dark);
                line-height: 1.12;
                letter-spacing: -0.01em;
            }

            h2.node-title {
                font-size: clamp(1.75rem, 3vw, 2.35rem);
                padding-bottom: 0.6rem;
                border-bottom: 1px solid rgba(165, 63, 43, 0.18);
            }

            h3.node-title {
                font-size: clamp(1.35rem, 2vw, 1.7rem);
            }

            h4.node-title {
                font-size: 1.15rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #8b3b2b;
            }

            h5.node-title {
                font-size: 1rem;
                color: #7c4b3f;
            }

            .node-title[id] {
                scroll-margin-top: 1.5rem;
            }

            .node-body {
                color: var(--ink);
                max-width: 72ch;
                font-size: 1.03rem;
                line-height: 1.82;
            }

            .node-body > *:first-child {
                margin-top: 0;
            }

            .node-body > *:last-child {
                margin-bottom: 0;
            }

            .node-body p,
            .node-body ul,
            .node-body ol,
            .node-body blockquote {
                margin: 0 0 1rem;
            }

            .node-body ul,
            .node-body ol {
                padding-left: 1.25rem;
            }

            .node-body li + li {
                margin-top: 0.45rem;
            }

            .node-body strong {
                color: #12202b;
            }

            .media-grid {
                display: grid;
                gap: 1rem;
                margin-top: 1.35rem;
                margin-bottom: 0.75rem;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }

            .media-frame {
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 10px 24px rgba(31, 41, 51, 0.07);
            }

            .media-frame img,
            .media-frame iframe {
                width: 100%;
                border: 0;
                display: block;
            }

            .media-frame img {
                height: auto;
                object-fit: cover;
                background: #f2ede4;
            }

            .media-frame iframe {
                aspect-ratio: 16 / 9;
            }

            .media-caption {
                padding: 0.9rem 1rem 1rem;
                color: var(--muted);
                font-size: 0.95rem;
                line-height: 1.6;
                border-top: 1px solid rgba(217, 205, 184, 0.7);
                background: rgba(248, 243, 234, 0.7);
            }

            .law-content {
                padding: 0.5rem;
                display: grid;
                gap: 1.5rem;
            }

            .law-detail-mobile-toc {
                display: none;
            }

            .toc-card {
                position: sticky;
                top: 1.25rem;
                display: grid;
                gap: 1rem;
            }

            .toc-title {
                margin: 0;
                color: var(--accent-dark);
                font-size: 1.1rem;
            }

            .toc-summary {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                cursor: pointer;
                list-style: none;
                font-weight: 700;
                color: var(--accent-dark);
            }

            .toc-summary::-webkit-details-marker {
                display: none;
            }

            .toc-summary::after {
                content: "+";
                font-size: 1.1rem;
            }

            details[open] > .toc-summary::after {
                content: "-";
            }

            .toc-list {
                display: grid;
                gap: 0.3rem;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .toc-list .toc-list {
                margin-top: 0.45rem;
                padding-left: 1.15rem;
                border-left: 1px solid rgba(165, 63, 43, 0.14);
            }

            .toc-link {
                display: block;
                padding: 0.45rem 0.6rem;
                border-radius: 10px;
                color: var(--accent-dark);
                text-decoration: none;
                line-height: 1.45;
                transition: background-color 0.12s ease, color 0.12s ease, transform 0.12s ease;
            }

            .toc-link:hover {
                background: rgba(165, 63, 43, 0.10);
                color: #7b2f20;
                transform: translateX(2px);
            }

            .toc-link.is-active {
                background: rgba(165, 63, 43, 0.14);
                color: #6e2518;
                font-weight: 700;
                box-shadow: inset 3px 0 0 rgba(165, 63, 43, 0.55);
            }

            .toc-item[data-depth="1"] > .toc-link {
                padding-left: 0.75rem;
            }

            .toc-item[data-depth="2"] > .toc-link,
            .toc-item[data-depth="3"] > .toc-link {
                color: #7b5347;
            }

            .law-content > .node + .node {
                margin-top: 0;
            }

            .back-link {
                display: inline-block;
                margin-bottom: 1.25rem;
                color: var(--accent-dark);
                text-decoration: none;
                font-weight: 700;
            }

            .auth-shell {
                width: min(480px, 100%);
                margin: 4rem auto;
            }

            .auth-card {
                display: grid;
                gap: 1rem;
            }

            .mobile-law-context {
                display: none;
            }

            .mobile-law-context-bar {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                gap: 0.6rem;
                align-items: center;
                width: 100%;
                margin: 0;
                padding: 0.45rem 0.7rem;
                border-bottom: 1px solid rgba(110, 37, 24, 0.12);
                background: rgba(255, 250, 242, 0.96);
                box-shadow: 0 12px 24px rgba(31, 41, 51, 0.08);
                backdrop-filter: blur(10px);
            }

            .mobile-law-context-title {
                overflow: hidden;
                border: 0;
                background: transparent;
                color: var(--accent-dark);
                text-overflow: ellipsis;
                white-space: nowrap;
                text-align: center;
                font-size: 0.92rem;
                font-weight: 700;
                padding: 0;
                touch-action: manipulation;
                text-decoration: none;
            }

            .mobile-law-context-side {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                padding: 0;
                border: 1px solid rgba(110, 37, 24, 0.12);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.86);
                color: var(--accent-dark);
                font-size: 1rem;
                font-weight: 700;
                cursor: default;
            }

            .mobile-law-context-side[aria-disabled="true"] {
                opacity: 0.45;
            }

            .mobile-law-context-side.is-link {
                cursor: pointer;
                text-decoration: none;
            }

            @media (max-width: 700px) {
                body {
                    padding-top: 4.05rem;
                }

                body.has-mobile-law-context {
                    padding-top: 6.95rem;
                }

                .shell {
                    width: min(100% - 1rem, 1100px);
                    padding-top: 1rem;
                }

                .mobile-header {
                    display: block;
                }

                .mobile-header-title,
                .mobile-header-action,
                .mobile-nav-link,
                .mobile-law-context-title,
                .mobile-law-context-side {
                    font-size: 1rem;
                }

                .mobile-law-context {
                    display: block;
                    position: fixed;
                    inset: 3.95rem 0 auto 0;
                    z-index: 44;
                    padding: 0;
                    transition: top 0.18s ease;
                }

                html.mobile-header-hidden .mobile-law-context {
                    top: 0;
                }

                .nav {
                    display: none;
                }

                .search-form input {
                    min-width: 0;
                    width: 100%;
                }

                .hero,
                .card,
                .node {
                    padding: 1rem;
                }

                .law-detail-grid {
                    grid-template-columns: 1fr;
                }

                .law-detail-mobile-toc {
                    display: block;
                }

                .toc-card {
                    display: none;
                }

                .law-grid {
                    grid-template-columns: 1fr;
                }

                .node {
                    padding-top: 0.85rem;
                    padding-left: 0;
                    margin-left: 0;
                }

                .node[data-type="section"] {
                    padding: 1rem;
                }

                .node[data-depth="1"],
                .node[data-depth="2"],
                .node[data-depth="3"] {
                    margin-left: 0.2rem;
                }

                .media-grid {
                    grid-template-columns: 1fr;
                    margin-top: 1rem;
                    margin-bottom: 0.4rem;
                }
            }
        </style>
    </head>
    <body class="@yield('body_class')">
        <div class="mobile-header" aria-hidden="false">
            <div class="mobile-header-shell" data-mobile-header>
                <div class="mobile-header-bar">
                    <a href="{{ route('laws.index') }}" class="mobile-logo-link" aria-label="Go to Laws home">
                        <div class="mobile-logo" aria-hidden="true">PSSI</div>
                    </a>
                    <button type="button" class="mobile-header-title" data-scroll-top>
                        @yield('mobile_header_title', trim($__env->yieldContent('title', 'LotG')))
                    </button>
                    <button type="button" class="mobile-header-action" data-mobile-menu-toggle aria-expanded="false">Menu</button>
                </div>

                <div class="mobile-header-panel" data-mobile-tray>
                    <div class="mobile-header-tray">
                    <form class="search-form mobile-search-form" action="{{ route('search.index') }}" method="get">
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search laws, sections, or body text">
                        <button type="submit" aria-label="Search">&#128269;</button>
                    </form>

                    <div class="mobile-nav-links">
                        <a class="mobile-nav-link" href="{{ route('laws.index') }}">Laws</a>
                        <a class="mobile-nav-link" href="{{ route('updates.index') }}">Updates</a>
                        <a class="mobile-nav-link" href="{{ route('search.index') }}">Search page</a>
                        @auth
                            <a class="mobile-nav-link" href="{{ route('admin.laws.index') }}">Admin</a>
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                        @else
                            <a class="mobile-nav-link" href="{{ route('login') }}">Admin login</a>
                        @endauth
                    </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $mobileLawPrev = trim($__env->yieldContent('mobile_law_prev'));
            $mobileLawNext = trim($__env->yieldContent('mobile_law_next'));
        @endphp

        @hasSection('mobile_law_context')
            <div class="mobile-law-context">
                <div class="mobile-law-context-bar">
                    @if ($mobileLawPrev !== '')
                        <a href="{{ $mobileLawPrev }}" class="mobile-law-context-side is-link" aria-label="Previous law">&lsaquo;</a>
                    @else
                        <button type="button" class="mobile-law-context-side" aria-disabled="true">&lsaquo;</button>
                    @endif
                    <button type="button" class="mobile-law-context-title" data-scroll-top>@yield('mobile_law_context')</button>
                    @if ($mobileLawNext !== '')
                        <a href="{{ $mobileLawNext }}" class="mobile-law-context-side is-link" aria-label="Next law">&rsaquo;</a>
                    @else
                        <button type="button" class="mobile-law-context-side" aria-disabled="true">&rsaquo;</button>
                    @endif
                </div>
            </div>
        @endif

        <div class="shell">
            <nav class="nav">
                <div class="nav-panel">
                    <div class="nav-links">
                        <a class="nav-link" href="{{ route('laws.index') }}">Laws</a>
                        <a class="nav-link" href="{{ route('updates.index') }}">Updates</a>
                        <a class="nav-link" href="{{ route('search.index') }}">Search</a>
                        @auth
                            <a class="nav-link" href="{{ route('admin.laws.index') }}">Admin</a>
                            <form action="{{ route('logout') }}" method="post" style="display: inline-flex;">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                        @else
                            <a class="nav-link" href="{{ route('login') }}">Admin login</a>
                        @endauth
                    </div>

                    <a href="{{ route('laws.index') }}" class="nav-brand" aria-label="Go to Laws home">
                        <span class="nav-brand-mark">PSSI</span>
                    </a>

                    <form class="search-form" action="{{ route('search.index') }}" method="get">
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search laws, sections, or body text">
                        <button type="submit">Search</button>
                    </form>
                </div>
            </nav>

            @yield('content')
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const mobileHeader = document.querySelector('[data-mobile-header]');
                const toggleButton = document.querySelector('[data-mobile-menu-toggle]');
                const scrollTopButtons = Array.from(document.querySelectorAll('[data-scroll-top]'));
                const root = document.documentElement;

                if (! mobileHeader || ! toggleButton) {
                    return;
                }

                let lastScrollY = window.scrollY;

                const setTrayOpen = (open) => {
                    mobileHeader.classList.toggle('is-open', open);
                    toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
                    toggleButton.textContent = open ? 'Close' : 'Menu';
                };

                toggleButton.addEventListener('click', function () {
                    setTrayOpen(! mobileHeader.classList.contains('is-open'));
                });

                scrollTopButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                });

                window.addEventListener('scroll', function () {
                    const currentScrollY = window.scrollY;
                    const isMobile = window.innerWidth <= 700;

                    if (! isMobile) {
                        mobileHeader.classList.remove('is-hidden');
                        root.classList.remove('mobile-header-hidden');
                        return;
                    }

                    if (mobileHeader.classList.contains('is-open')) {
                        mobileHeader.classList.remove('is-hidden');
                        root.classList.remove('mobile-header-hidden');
                        lastScrollY = currentScrollY;
                        return;
                    }

                    if (currentScrollY < 12 || currentScrollY < lastScrollY) {
                        mobileHeader.classList.remove('is-hidden');
                        root.classList.remove('mobile-header-hidden');
                    } else if (currentScrollY > lastScrollY + 8) {
                        mobileHeader.classList.add('is-hidden');
                        root.classList.add('mobile-header-hidden');
                    }

                    lastScrollY = currentScrollY;
                }, { passive: true });
            });
        </script>
    </body>
</html>
