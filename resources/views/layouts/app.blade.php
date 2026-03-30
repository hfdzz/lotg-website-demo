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
                gap: 1rem;
            }

            .law-link {
                display: block;
                text-decoration: none;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }

            .law-link:hover {
                transform: translateY(-2px);
                box-shadow: 0 16px 36px rgba(82, 96, 109, 0.10);
            }

            .law-meta {
                color: var(--muted);
                font-size: 0.95rem;
            }

            .node {
                margin-top: 1.25rem;
                padding: 1.25rem;
                border-left: 4px solid rgba(165, 63, 43, 0.18);
                border-radius: 0 16px 16px 0;
                background: rgba(255, 255, 255, 0.58);
            }

            .node[data-type="section"] {
                background: linear-gradient(180deg, rgba(255, 247, 238, 0.95), rgba(255, 255, 255, 0.78));
            }

            .node-title {
                margin: 0 0 0.75rem;
                color: var(--accent-dark);
                line-height: 1.15;
            }

            .node-body {
                color: var(--ink);
                line-height: 1.75;
            }

            .media-grid {
                display: grid;
                gap: 1rem;
                margin-top: 1rem;
            }

            .media-frame {
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
            }

            .media-frame img,
            .media-frame iframe {
                width: 100%;
                border: 0;
                display: block;
            }

            .media-frame iframe {
                aspect-ratio: 16 / 9;
            }

            .media-caption {
                padding: 0.9rem 1rem 1rem;
                color: var(--muted);
                font-size: 0.95rem;
            }

            .back-link {
                display: inline-block;
                margin-bottom: 1.25rem;
                color: var(--accent-dark);
                text-decoration: none;
                font-weight: 700;
            }

            @media (max-width: 700px) {
                .shell {
                    width: min(100% - 1rem, 1100px);
                    padding-top: 1rem;
                }

                .hero,
                .card,
                .node {
                    padding: 1rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            @yield('content')
        </div>
    </body>
</html>
