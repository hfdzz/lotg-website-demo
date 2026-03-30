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

            a {
                color: inherit;
            }

            .shell {
                width: min(1100px, calc(100% - 2rem));
                margin: 0 auto;
                padding: 2rem 0 4rem;
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
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 1rem;
            }

            .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
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
                padding: 1rem 0 0 1.25rem;
                border-left: 3px solid rgba(165, 63, 43, 0.16);
            }

            .node[data-type="section"] {
                margin-top: 2rem;
                padding: 1.35rem 1.35rem 0.25rem 1.35rem;
                border-left-width: 5px;
                border-radius: 0 22px 22px 0;
                background: linear-gradient(180deg, rgba(255, 247, 238, 0.98), rgba(255, 255, 255, 0.84));
                box-shadow: 0 10px 26px rgba(82, 96, 109, 0.06);
            }

            .node[data-depth="0"] {
                margin-top: 0;
            }

            .node[data-depth="1"] {
                margin-top: 1.4rem;
            }

            .node[data-depth="2"],
            .node[data-depth="3"] {
                border-left-color: rgba(82, 96, 109, 0.14);
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
                margin-top: 1.15rem;
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
                display: grid;
                gap: 1.5rem;
            }

            .toc-card {
                position: sticky;
                top: 1rem;
                display: grid;
                gap: 1rem;
            }

            .toc-title {
                margin: 0;
                color: var(--accent-dark);
                font-size: 1.1rem;
            }

            .toc-list {
                display: grid;
                gap: 0.4rem;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .toc-list .toc-list {
                margin-top: 0.45rem;
                padding-left: 1rem;
                border-left: 1px solid rgba(165, 63, 43, 0.14);
            }

            .toc-link {
                display: inline-block;
                color: var(--accent-dark);
                text-decoration: none;
                line-height: 1.45;
            }

            .toc-link:hover {
                text-decoration: underline;
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

            @media (max-width: 700px) {
                .shell {
                    width: min(100% - 1rem, 1100px);
                    padding-top: 1rem;
                }

                .nav,
                .search-form {
                    align-items: stretch;
                    flex-direction: column;
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

                .toc-card {
                    position: static;
                }

                .law-grid {
                    grid-template-columns: 1fr;
                }

                .node {
                    padding-top: 0.85rem;
                    padding-left: 0.9rem;
                }

                .node[data-type="section"] {
                    padding: 1rem;
                }

                .media-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <nav class="nav">
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

                <form class="search-form" action="{{ route('search.index') }}" method="get">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search laws, sections, or body text">
                    <button type="submit">Search</button>
                </form>
            </nav>

            @yield('content')
        </div>
    </body>
</html>
