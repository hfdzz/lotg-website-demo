import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    setupMobileHeader();
    setupTableOfContentsTracking();
});

function setupMobileHeader() {
    const mobileHeader = document.querySelector('[data-mobile-header]');
    const toggleButton = document.querySelector('[data-mobile-menu-toggle]');
    const tray = document.querySelector('[data-mobile-tray]');
    const scrollTopButtons = Array.from(document.querySelectorAll('[data-scroll-top]'));
    const root = document.documentElement;

    if (!mobileHeader || !toggleButton) {
        return;
    }

    let lastScrollY = window.scrollY;

    const setTrayOpen = (open) => {
        mobileHeader.classList.toggle('is-open', open);
        document.body.classList.toggle('menu-open', open);
        toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggleButton.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        toggleButton.innerHTML = open ? '&times;' : '&#9776;';
    };

    toggleButton.addEventListener('click', () => {
        setTrayOpen(!mobileHeader.classList.contains('is-open'));
    });

    tray?.addEventListener('click', (event) => {
        const target = event.target;

        if (target instanceof HTMLElement && target.closest('a')) {
            setTrayOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileHeader.classList.contains('is-open')) {
            setTrayOpen(false);
        }
    });

    scrollTopButtons.forEach((button) => {
        button.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    window.addEventListener(
        'resize',
        () => {
            if (window.innerWidth > 700) {
                setTrayOpen(false);
                mobileHeader.classList.remove('is-hidden');
                root.classList.remove('mobile-header-hidden');
            }
        },
        { passive: true },
    );

    window.addEventListener(
        'scroll',
        () => {
            const currentScrollY = window.scrollY;
            const isMobile = window.innerWidth <= 700;

            if (!isMobile) {
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
        },
        { passive: true },
    );
}

function setupTableOfContentsTracking() {
    const tocLinks = Array.from(document.querySelectorAll('.toc-link[data-anchor]'));

    if (!tocLinks.length) {
        return;
    }

    const headings = tocLinks
        .map((link) => document.getElementById(link.dataset.anchor))
        .filter(Boolean);

    if (!headings.length) {
        return;
    }

    const setActive = (id) => {
        tocLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.anchor === id);
        });
    };

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

            if (visible.length > 0) {
                setActive(visible[0].target.id);
            }
        },
        {
            rootMargin: '-18% 0px -65% 0px',
            threshold: [0, 1],
        },
    );

    headings.forEach((heading) => observer.observe(heading));

    if (headings[0]) {
        setActive(headings[0].id);
    }
}
