@php
    $currentLanguage = \App\Support\LotgLanguage::normalize(request('lang'));
    $languageOptions = \App\Support\LotgLanguage::supported();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'LotG')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="@yield('body_class')">
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
                            <a class="mobile-nav-link" href="{{ route('updates.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.updates') }}</a>
                            <a class="mobile-nav-link" href="{{ route('qas.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.qas') }}</a>
                            <a class="mobile-nav-link" href="{{ route('search.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.search') }}</a>
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
                                <a class="mobile-nav-link" href="{{ route('admin.home') }}">{{ __('site.nav.admin') }}</a>
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
        @endphp

        @hasSection('mobile_law_context')
            <div class="mobile-law-context">
                <div class="mobile-law-context-bar">
                    @if ($mobileLawPrev !== '')
                        <a href="{{ $mobileLawPrev }}" class="mobile-law-context-side left is-link" aria-label="{{ __('site.laws.previous_law') }}" onclick="event.stopPropagation()">&lsaquo;</a>
                    @endif
                    <p class="mobile-law-context-title" data-scroll-top>@yield('mobile_law_context')</p>
                    @if ($mobileLawNext !== '')
                        <a href="{{ $mobileLawNext }}" class="mobile-law-context-side right is-link" aria-label="{{ __('site.laws.next_law') }}" onclick="event.stopPropagation()">&rsaquo;</a>
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
                                <a class="nav-link" href="{{ route('updates.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.updates') }}</a>
                                <a class="nav-link" href="{{ route('qas.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.qas') }}</a>
                                <a class="nav-link" href="{{ route('search.index', ['lang' => $currentLanguage]) }}">{{ __('site.nav.search') }}</a>
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
                                <a class="nav-link" href="{{ route('admin.home') }}">{{ __('site.nav.admin') }}</a>
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

            @yield('content')
        </div>
    </body>
</html>
