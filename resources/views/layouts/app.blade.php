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
                <div class="mobile-header-bar" data-scroll-top>
                    <a href="{{ route('laws.index') }}" class="mobile-logo-link" aria-label="Go to Laws home">
                        <img class="mobile-logo" src="{{ asset('demo/logo_pssi_tulisan.png') }}" alt="PSSI">
                    </a>
                    <button type="button" class="mobile-header-title" data-scroll-top>@yield('mobile_header_title', 'Laws of the Game')</button>
                    <button type="button" class="mobile-header-action" data-mobile-menu-toggle aria-expanded="false" aria-label="Open menu">&#9776;</button>
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
                <div class="mobile-law-context-bar" data-scroll-top>
                    @if ($mobileLawPrev !== '')
                        <a href="{{ $mobileLawPrev }}" class="mobile-law-context-side left is-link" aria-label="Previous law" onclick="event.stopPropagation()">&lsaquo;</a>
                    @endif
                    <button type="button" class="mobile-law-context-title">@yield('mobile_law_context')</button>
                    @if ($mobileLawNext !== '')
                        <a href="{{ $mobileLawNext }}" class="mobile-law-context-side right is-link" aria-label="Next law" onclick="event.stopPropagation()">&rsaquo;</a>
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
                            <form action="{{ route('logout') }}" method="post" class="inline-form">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                        @endauth
                    </div>

                    <a href="{{ route('laws.index') }}" class="nav-brand" aria-label="Go to Laws home">
                        <img class="nav-brand-mark" src="{{ asset('demo/logo_pssi_tulisan.png') }}" alt="PSSI">
                    </a>

                    <form class="search-form" action="{{ route('search.index') }}" method="get">
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search laws, sections, or body text">
                        <button type="submit">Search</button>
                    </form>
                </div>
            </nav>

            @yield('content')
        </div>
    </body>
</html>
