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
                    <a href="{{ route('laws.index') }}" class="mobile-logo-link" aria-label="Go to Laws home">
                        <img class="mobile-logo" src="{{ asset('demo/logo_pssi_tulisan.png') }}" alt="PSSI">
                    </a>
                    <button type="button" class="mobile-header-title" data-scroll-top>@yield('mobile_header_title', 'Laws of the Game')</button>
                    <button type="button" class="mobile-header-action" data-mobile-menu-toggle aria-expanded="false" aria-label="Open menu">&#9776;</button>
                </div>

                <div class="mobile-header-panel" data-mobile-tray>
                    <div class="mobile-header-tray">
                        <form class="mobile-search-form" action="{{ route('search.index') }}" method="get">
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search laws, sections, or body text">
                            <button type="submit" aria-label="Search">
                                <svg width="100px" height="100px" viewBox="0 -0.5 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="1.4499999999999997"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 10.7655C5.50003 8.01511 7.44296 5.64777 10.1405 5.1113C12.8381 4.57483 15.539 6.01866 16.5913 8.55977C17.6437 11.1009 16.7544 14.0315 14.4674 15.5593C12.1804 17.0871 9.13257 16.7866 7.188 14.8415C6.10716 13.7604 5.49998 12.2942 5.5 10.7655Z" stroke="#7a7a7a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M17.029 16.5295L19.5 19.0005" stroke="#7a7a7a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                            </button>
                        </form>

                        <div class="mobile-nav-links">
                            <a class="mobile-nav-link" href="{{ route('laws.index') }}">Laws</a>
                            <a class="mobile-nav-link" href="{{ route('updates.index') }}">Updates</a>
                            <a class="mobile-nav-link" href="{{ route('search.index') }}">Search</a>
                            @auth
                                <a class="mobile-nav-link" href="{{ route('admin.laws.index') }}">Admin</a>
                                <form class="mobile-nav-form" action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="mobile-nav-button">Logout</button>
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
                        <a href="{{ $mobileLawPrev }}" class="mobile-law-context-side left is-link" aria-label="Previous law" onclick="event.stopPropagation()">&lsaquo;</a>
                    @endif
                    <p class="mobile-law-context-title" data-scroll-top>@yield('mobile_law_context')</p>
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
