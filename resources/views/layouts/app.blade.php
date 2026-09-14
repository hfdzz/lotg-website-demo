@php
    $currentLanguage = \App\Support\LotgLanguage::normalize(request('lang'));
    $languageOptions = \App\Support\LotgLanguage::supported();
    $publicFeatureNav = $publicFeatureNav ?? [];
    $showUpdatesNav = (bool) ($publicFeatureNav['legacy_updates'] ?? true);
    $showQasNav = (bool) ($publicFeatureNav['qas'] ?? true);
    $rawPageTitle = trim($__env->yieldContent('title', __('site.brand')));
    $siteName = config('app.name') && config('app.name') !== 'Laravel' ? config('app.name') : __('site.brand');
    $pageTitle = str_contains($rawPageTitle, $siteName) ? $rawPageTitle : $rawPageTitle.' | '.$siteName;
    $metaDescription = trim($__env->yieldContent('meta_description', __('site.seo.default_description')));
    $canonicalUrl = trim($__env->yieldContent('canonical_url', request()->fullUrlWithQuery(['lang' => $currentLanguage])));
    $robotsMeta = trim($__env->yieldContent('robots', 'index, follow'));
    $ogImageUrl = trim($__env->yieldContent('og_image', asset('statics/logo_pssi_tulisan.png')));
    $layoutActiveEdition = \App\Models\Edition::current();
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => url('/'),
        'inLanguage' => array_keys($languageOptions),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => url('/search').'?q={search_term_string}&lang='.$currentLanguage,
            'query-input' => 'required name=search_term_string',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <meta name="robots" content="{{ $robotsMeta }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        @foreach ($languageOptions as $languageCode => $languageLabel)
            <link rel="alternate" hreflang="{{ $languageCode }}" href="{{ request()->fullUrlWithQuery(['lang' => $languageCode]) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ request()->fullUrlWithQuery(['lang' => \App\Support\LotgLanguage::default()]) }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:type" content="@yield('og_type', 'website')">
        <meta property="og:title" content="{{ $rawPageTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $rawPageTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="@yield('body_class')">
        <a class="skip-link" href="#main-content">{{ __('site.nav.skip_to_content') }}</a>
        <div class="mobile-header" aria-hidden="false">
            <div class="mobile-header-shell" data-mobile-header>
                <div class="mobile-header-bar">
                    <a href="{{ route('laws.index', ['lang' => $currentLanguage]) }}" class="mobile-logo-link" aria-label="{{ __('site.nav.go_home') }}">
                        <img class="mobile-logo" src="{{ asset('statics/logo_pssi_tulisan.png') }}" alt="PSSI">
                    </a>
                    <p type="button" class="mobile-header-title" data-scroll-top>@yield('mobile_header_title', __('site.brand'))</p>
                    <button type="button" class="mobile-header-action" data-mobile-menu-toggle aria-expanded="false" aria-label="{{ __('site.nav.open_menu') }}">&#9776;</button>
                </div>

                <div class="mobile-header-panel" data-mobile-tray>
                    <div class="mobile-header-tray">
                        <form class="mobile-search-form" action="{{ route('search.index') }}" method="get">
                            <input type="hidden" name="lang" value="{{ $currentLanguage }}">
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('site.nav.search_placeholder') }}">
                            <button type="submit" aria-label="{{ __('site.nav.search') }}">
                                <svg width="100px" height="100px" viewBox="0 -0.5 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="1.4499999999999997"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 10.7655C5.50003 8.01511 7.44296 5.64777 10.1405 5.1113C12.8381 4.57483 15.539 6.01866 16.5913 8.55977C17.6437 11.1009 16.7544 14.0315 14.4674 15.5593C12.1804 17.0871 9.13257 16.7866 7.188 14.8415C6.10716 13.7604 5.49998 12.2942 5.5 10.7655Z" stroke="#7a7a7a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M17.029 16.5295L19.5 19.0005" stroke="#7a7a7a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                            </button>
                        </form>

                        <div class="mobile-nav-links">
                            <a class="mobile-nav-link" href="{{ route('laws.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.laws') }}</a>
                            @if ($showUpdatesNav)
                                <a class="mobile-nav-link" href="{{ route('updates.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.updates') }}</a>
                            @endif
                            @if ($showQasNav)
                                <a class="mobile-nav-link" href="{{ route('qas.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.qas') }}</a>
                            @endif
                            <form class="mobile-lang-form" action="{{ url()->current() }}" method="get">
                                @if (request()->filled('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                @endif
                                <label class="mobile-lang-label" for="mobile-lang-select">{{ __('site.nav.language') }}</label>
                                <select id="mobile-lang-select" name="lang" class="mobile-lang-select" onchange="this.form.submit()">
                                    @foreach ($languageOptions as $languageCode => $languageLabel)
                                        <option value="{{ $languageCode }}" @selected($currentLanguage === $languageCode)>{{ strtoupper($languageCode) }} - {{ $languageLabel }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @auth
                                @can('access-admin')
                                    <a class="mobile-nav-link" href="{{ route('admin.home') }}">{{ __('site.nav.admin') }}</a>
                                @endcan
                                <form class="mobile-nav-form" action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="mobile-nav-button">{{ __('site.nav.logout') }}</button>
                                </form>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $mobileLawPrev = trim($__env->yieldContent('mobile_law_prev'));
            $mobileLawNext = trim($__env->yieldContent('mobile_law_next'));
            $mobileLawContextControl = trim($__env->yieldContent('mobile_law_context_control'));
        @endphp

        @hasSection('mobile_law_context')
            <div class="mobile-law-context">
                <div class="mobile-law-context-bar">
                    @if ($mobileLawPrev !== '')
                        <a href="{{ $mobileLawPrev }}" class="mobile-law-context-side left is-link" aria-label="{{ __('site.laws.previous_law') }}" onclick="event.stopPropagation()" data-nav-pending-link>&lsaquo;</a>
                    @endif
                    @if ($mobileLawContextControl !== '')
                        <div class="mobile-law-context-title mobile-law-context-control">
                            {!! $mobileLawContextControl !!}
                        </div>
                    @else
                        <p class="mobile-law-context-title" data-scroll-top>@yield('mobile_law_context')</p>
                    @endif
                    @if ($mobileLawNext !== '')
                        <a href="{{ $mobileLawNext }}" class="mobile-law-context-side right is-link" aria-label="{{ __('site.laws.next_law') }}" onclick="event.stopPropagation()" data-nav-pending-link>&rsaquo;</a>
                    @endif
                </div>
            </div>
        @endif

        <div class="shell">
            <nav class="nav">
                <div class="nav-panel">
                    <div class="nav-row">
                        <div class="nav-brand-area">
                            <a href="{{ route('laws.index', ['lang' => $currentLanguage]) }}" class="nav-brand" aria-label="{{ __('site.nav.go_home') }}">
                                <img class="nav-brand-mark" src="{{ asset('statics/logo_pssi_tulisan.png') }}" alt="PSSI">
                            </a>
                        </div>

                        <div class="nav-main-wrapper">
                            <div class="nav-main">
                                <a class="nav-link" href="{{ route('laws.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.laws') }}</a>
                                @if ($showUpdatesNav)
                                    <a class="nav-link" href="{{ route('updates.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.updates') }}</a>
                                @endif
                                @if ($showQasNav)
                                    <a class="nav-link" href="{{ route('qas.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.qas') }}</a>
                                @endif
                            </div>
                        </div>

                        <div class="nav-utility">
                            <form class="nav-lang-form" action="{{ url()->current() }}" method="get">
                                @if (request()->filled('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                @endif
                                <label class="sr-only" for="desktop-lang-select">{{ __('site.nav.language') }}</label>
                                <select id="desktop-lang-select" name="lang" class="nav-lang-select" onchange="this.form.submit()">
                                    @foreach ($languageOptions as $languageCode => $languageLabel)
                                        <option value="{{ $languageCode }}" @selected($currentLanguage === $languageCode)>{{ strtoupper($languageCode) }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @auth
                                @can('access-admin')
                                    <a class="nav-link" href="{{ route('admin.home') }}">{{ __('site.nav.admin') }}</a>
                                @endcan
                                <form action="{{ route('logout') }}" method="post" class="inline-form">
                                    @csrf
                                    <button type="submit">{{ __('site.nav.logout') }}</button>
                                </form>
                            @endauth
                            <details class="search-popover">
                                <summary class="nav-icon-button search-popover-toggle" aria-label="{{ __('site.nav.search') }}">
                                    <svg width="100px" height="100px" viewBox="0 -0.5 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 10.7655C5.50003 8.01511 7.44296 5.64777 10.1405 5.1113C12.8381 4.57483 15.539 6.01866 16.5913 8.55977C17.6437 11.1009 16.7544 14.0315 14.4674 15.5593C12.1804 17.0871 9.13257 16.7866 7.188 14.8415C6.10716 13.7604 5.49998 12.2942 5.5 10.7655Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M17.029 16.5295L19.5 19.0005" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </summary>
                                <div class="search-popover-panel">
                                    <form class="search-form search-form-panel" action="{{ route('search.index') }}" method="get">
                                        <input type="hidden" name="lang" value="{{ $currentLanguage }}">
                                        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('site.nav.search_placeholder') }}">
                                        <button type="submit">{{ __('site.nav.search_submit') }}</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>

                </div>
            </nav>

            <main id="main-content" class="main-content" tabindex="-1">
                @yield('content')
            </main>

        </div>
        <footer class="site-footer">
            <div class="site-footer-row site-footer-row-primary">
                <div class="site-footer-primary">
                    <a href="{{ route('laws.index', ['lang' => $currentLanguage]) }}" class="site-footer-brand" aria-label="{{ __('site.nav.go_home') }}">
                        <img src="{{ asset('statics/logo_pssi_tulisan.png') }}" alt="PSSI">
                        <!-- <span>{{ __('site.brand') }}</span> -->
                    </a>
                    <p>{{ __('site.footer.description') }}</p>
                    <p class="site-footer-edition">
                        @if ($layoutActiveEdition)
                            {{ __('site.footer.edition', ['edition' => $layoutActiveEdition->name]) }}
                        @else
                            {{ __('site.footer.no_edition') }}
                        @endif
                    </p>
                </div>
                <nav class="site-footer-nav" aria-label="{{ __('site.footer.browse') }}">
                    <a href="{{ route('laws.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.laws') }}</a>
                    <a href="{{ route('editions.index', ['lang' => $currentLanguage]) }}">{{ __('site.editions.title') }}</a>
                    @if ($showUpdatesNav)
                        <a href="{{ route('updates.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.updates') }}</a>
                    @endif
                    @if ($showQasNav)
                        <a href="{{ route('qas.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.qas') }}</a>
                    @endif
                </nav>
            </div>
            <div class="site-footer-row site-footer-row-secondary">
                <div class="site-footer-copyright">
                    <p>PSSI - Football Association of Indonesia © 2026. All Rights Reserved</p>
                </div>
                <div class="site-footer-social">
                    <ul>
                        <li>
                            <a href="https://www.pssi.org" rel="nofollow noopener noreferrer" target="_blank">
                                <span class="sr-only">PSSI</span>
                                <img class="site-footer-social-logo" src="{{ asset('storage/logos/pssi_bnw.png') }}" alt="">
                            </a>
                        </li>
                        <li>
                            <a href="https://www.facebook.com/pssi" rel="nofollow noopener noreferrer" target="_blank">
                                <span class="sr-only">Facebook</span>
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M4.8 3h14.4A1.8 1.8 0 0 1 21 4.8v14.4a1.8 1.8 0 0 1-1.8 1.8h-3.7v-6.8h2.3l.4-2.8h-2.7V9.6c0-.8.2-1.4 1.4-1.4h1.5V5.7c-.7-.1-1.4-.1-2.1-.1-2.1 0-3.6 1.3-3.6 3.7v2.1h-2.4v2.8h2.4V21H4.8A1.8 1.8 0 0 1 3 19.2V4.8A1.8 1.8 0 0 1 4.8 3Z" fill="currentColor"></path>
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a href="https://twitter.com/pssi" rel="nofollow noopener noreferrer" target="_blank">
                                <span class="sr-only">Twitter</span>
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M13.9 10.6 21.2 2h-1.7l-6.4 7.4L8.1 2H2.3l7.7 11.2L2.3 22h1.8l6.7-7.8 5.4 7.8h5.8l-8-11.4Zm-2.4 2.8-.8-1.1L4.5 3.3h2.8l5 7.2.8 1.1 6.5 9.2h-2.8l-5.3-7.4Z" fill="currentColor"></path>
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.youtube.com/channel/UCbOdQk9540V09ff51nFEDKw" rel="nofollow noopener noreferrer" target="_blank">
                                <span class="sr-only">Youtube</span>
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M21.6 7.2s-.2-1.6-.9-2.3c-.9-.9-1.9-.9-2.3-1C15.2 3.7 12 3.7 12 3.7h0s-3.2 0-6.4.2c-.5.1-1.5.1-2.3 1-.7.7-.9 2.3-.9 2.3S2.2 9.1 2.2 11v1.8c0 1.9.2 3.8.2 3.8s.2 1.6.9 2.3c.9.9 2 .9 2.5 1 1.8.2 6.2.2 6.2.2s3.2 0 6.4-.2c.5-.1 1.5-.1 2.3-1 .7-.7.9-2.3.9-2.3s.2-1.9.2-3.8V11c0-1.9-.2-3.8-.2-3.8ZM10 15.1V8.5l5.9 3.3-5.9 3.3Z" fill="currentColor"></path>
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.instagram.com/pssi/" rel="nofollow noopener noreferrer" target="_blank">
                                <span class="sr-only">Instagram</span>
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2Zm8.4 18.2a4 4 0 0 0 4-4V7.8a4 4 0 0 0-4-4H7.8a4 4 0 0 0-4 4v8.4a4 4 0 0 0 4 4h8.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 8.2A3.2 3.2 0 1 0 12 8.8a3.2 3.2 0 0 0 0 6.4Zm5.2-8.4a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4Z" fill="currentColor"></path>
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </footer>
    </body>
</html>
