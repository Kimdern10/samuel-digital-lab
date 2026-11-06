// Interactions and motion for the public website. Dependency-free so pages stay light.
// Pages are swapped by Livewire's navigation (no full reload), so page features are set
// up again after every swap by initPage(), and cleaned up before the next one.

const root = document.documentElement;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let cleanups = [];

const onCleanup = (callback) => cleanups.push(callback);

// ---------- Set up once for the whole visit ----------

// Header turns solid after scrolling, and a thin bar shows how far down the page you are
let ticking = false;

const onScroll = () => {
    const scrolled = window.scrollY;
    const height = document.documentElement.scrollHeight - window.innerHeight;

    document.querySelector('.site-header')?.classList.toggle('is-solid', scrolled > 24);
    document.querySelector('.progress')?.style.setProperty('--progress', height > 0 ? (scrolled / height).toFixed(4) : 0);
    ticking = false;
};

window.addEventListener('scroll', () => {
    if (! ticking) {
        window.requestAnimationFrame(onScroll);
        ticking = true;
    }
}, { passive: true });

const setMenu = (open) => {
    root.classList.toggle('menu-open', open);
    document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', String(open));
};

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMenu(false);
    }
});

// Cookie consent: Google Analytics loads only after the visitor accepts
const CONSENT_KEY = 'sdl-cookie-consent';
let analyticsLoaded = false;

const getConsent = () => {
    try {
        return localStorage.getItem(CONSENT_KEY);
    } catch (error) {
        return null;
    }
};

const loadAnalytics = () => {
    const id = document.querySelector('meta[name="analytics-id"]')?.content;

    if (! id || analyticsLoaded) {
        return;
    }

    analyticsLoaded = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', id);

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
    document.head.append(script);
};

const setCookieBanner = (visible) => {
    const banner = document.querySelector('[data-cookie-banner]');

    if (banner) {
        banner.hidden = ! visible;
    }
};

document.addEventListener('click', (event) => {
    const choice = event.target.closest('[data-cookie-choice]');

    if (choice) {
        try {
            localStorage.setItem(CONSENT_KEY, choice.dataset.cookieChoice);
        } catch (error) {
            // Without storage the choice lasts for this visit only.
        }

        setCookieBanner(false);

        if (choice.dataset.cookieChoice === 'accepted') {
            loadAnalytics();
        }

        return;
    }

    if (event.target.closest('[data-cookie-settings]')) {
        setCookieBanner(true);

        return;
    }

    // Light / dark switch, remembered on this device. It uses the same saved setting as the
    // admin and client account pages (Flux), so every page shows the same mode.
    if (event.target.closest('[data-theme-toggle]')) {
        const current = root.dataset.theme
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        const next = current === 'dark' ? 'light' : 'dark';

        root.dataset.theme = next;

        try {
            localStorage.setItem('flux.appearance', next);
        } catch (error) {
            // Storage can be blocked; the switch still works for this visit.
        }

        return;
    }

    // Mobile menu
    if (event.target.closest('[data-menu-toggle]')) {
        setMenu(! root.classList.contains('menu-open'));

        return;
    }

    if (event.target.closest('.mobile-menu a')) {
        setMenu(false);
    }

    // "Get a quote" on a service card picks that service in the form
    const pick = event.target.closest('[data-pick-service]');
    const select = document.getElementById('q-type');

    if (pick && select) {
        const option = [...select.options].find((item) => item.value === pick.dataset.pickService);

        if (option) {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            select.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    // Copy buttons, with a fallback that selects the text
    const copy = event.target.closest('[data-copy]');

    if (copy) {
        const target = document.getElementById(copy.dataset.copy);
        const text = target.textContent.trim();
        const done = () => {
            copy.textContent = 'Copied';
            setTimeout(() => { copy.textContent = 'Copy'; }, 1600);
        };
        const selectText = () => {
            const range = document.createRange();
            range.selectNodeContents(target);
            const selection = getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(selectText);
        } else {
            selectText();
        }
    }
});

// A soft light that follows the pointer across cards
document.addEventListener('pointermove', (event) => {
    const card = event.target.closest?.('.card');

    if (card) {
        const box = card.getBoundingClientRect();
        card.style.setProperty('--x', `${event.clientX - box.left}px`);
        card.style.setProperty('--y', `${event.clientY - box.top}px`);
    }
}, { passive: true });

// ---------- Set up for each page ----------

// Count a number up from zero, keeping any text around it ("16 s → 0.7 s" counts the 0.7)
const countUp = (element) => {
    const text = element.textContent;
    const matches = [...text.matchAll(/\d[\d,]*(?:\.\d+)?/g)];
    const last = matches.at(-1);

    if (! last || reduceMotion) {
        return;
    }

    const raw = last[0];
    const target = parseFloat(raw.replace(/,/g, ''));
    const decimals = raw.includes('.') ? raw.split('.')[1].length : 0;
    const before = text.slice(0, last.index);
    const after = text.slice(last.index + raw.length);
    const start = performance.now();
    const duration = 1400;

    const frame = (now) => {
        const progressed = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progressed, 3);
        const value = (target * eased).toFixed(decimals);
        const formatted = raw.includes(',') ? Number(value).toLocaleString('en-NG', { minimumFractionDigits: decimals }) : value;

        element.textContent = before + formatted + after;

        if (progressed < 1) {
            requestAnimationFrame(frame);
        } else {
            element.textContent = text;
        }
    };

    requestAnimationFrame(frame);
};

// Fill the PageSpeed score rings
const fillRing = (arc) => {
    const length = 2 * Math.PI * 32;
    arc.style.strokeDashoffset = length * (1 - Number(arc.dataset.score || 0) / 100);
};

const initRotators = () => {
    document.querySelectorAll('[data-rotate]').forEach((rotator) => {
        const words = JSON.parse(rotator.dataset.rotate || '[]');
        const word = rotator.querySelector('.rotator__word');

        if (reduceMotion || words.length < 2 || ! word) {
            return;
        }

        let index = 0;

        const timer = setInterval(() => {
            index = (index + 1) % words.length;
            word.classList.add('is-out');

            setTimeout(() => {
                word.textContent = words[index];
                word.classList.remove('is-out');
                word.classList.add('is-in');
                void word.offsetWidth;
                word.classList.remove('is-in');
            }, 450);
        }, 2600);

        onCleanup(() => clearInterval(timer));
    });
};

const initReveal = () => {
    const targets = document.querySelectorAll('[data-reveal], [data-count], .score__arc, .steps');

    if (! ('IntersectionObserver' in window) || reduceMotion) {
        targets.forEach((element) => element.classList.add('is-visible'));
        document.querySelectorAll('.score__arc').forEach(fillRing);

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            const element = entry.target;

            element.classList.add('is-visible');

            if (element.hasAttribute('data-count')) {
                countUp(element);
            }

            if (element.classList.contains('score__arc')) {
                fillRing(element);
            }

            observer.unobserve(element);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

    targets.forEach((element) => observer.observe(element));
    onCleanup(() => observer.disconnect());
};

// Only one question open at a time
const initFaq = () => {
    document.querySelectorAll('.faq details').forEach((item, _, all) => {
        item.addEventListener('toggle', () => {
            if (item.open) {
                all.forEach((other) => {
                    if (other !== item) {
                        other.open = false;
                    }
                });
            }
        });
    });
};

// Live preview of a project's website, scaled to fit, as a desktop or a phone
const initLivePreviews = () => {
    document.querySelectorAll('[data-live-preview]').forEach((preview) => {
        const viewport = preview.querySelector('.live__viewport');
        const frame = preview.querySelector('.live__frame');
        const shot = preview.querySelector('.live__shot');
        const sizes = { desktop: [1280, 800], phone: [390, 780] };

        const layout = () => {
            const [width, height] = sizes[preview.dataset.mode] || sizes.desktop;
            const available = viewport.clientWidth;
            const scale = Math.min(1, available / width);

            if (frame) {
                viewport.style.setProperty('--live-scale', scale);
                viewport.style.setProperty('--live-width', `${width}px`);
                viewport.style.setProperty('--live-frame-height', `${height}px`);
                viewport.style.setProperty('--live-left', `${Math.max(0, (available - width * scale) / 2)}px`);
                viewport.style.setProperty('--live-height', `${Math.round(height * scale)}px`);
            } else if (shot) {
                viewport.style.setProperty('--live-height', 'auto');
            }
        };

        preview.querySelectorAll('[data-live-mode]').forEach((button) => {
            button.addEventListener('click', () => {
                preview.dataset.mode = button.dataset.liveMode;
                preview.querySelectorAll('[data-live-mode]').forEach((other) => {
                    other.classList.toggle('is-active', other === button);
                    other.setAttribute('aria-pressed', String(other === button));
                });

                if (shot) {
                    shot.src = shot.dataset[button.dataset.liveMode] || shot.src;
                }

                layout();
            });
        });

        layout();
        window.addEventListener('resize', layout, { passive: true });
        onCleanup(() => window.removeEventListener('resize', layout));
    });
};

// Tap-to-enlarge screenshot viewer
const initLightbox = () => {
    const lightbox = document.querySelector('[data-lightbox]');

    if (! lightbox) {
        return;
    }

    const triggers = [...document.querySelectorAll('[data-lightbox-src]')];
    const image = lightbox.querySelector('img');
    const count = lightbox.querySelector('[data-lightbox-count]');
    let index = 0;

    const show = (next) => {
        index = (next + triggers.length) % triggers.length;
        image.src = triggers[index].dataset.lightboxSrc;
        image.alt = triggers[index].dataset.lightboxAlt || '';
        count.textContent = `${index + 1} / ${triggers.length}`;
    };

    triggers.forEach((trigger, position) => {
        trigger.addEventListener('click', () => {
            show(position);
            lightbox.showModal();
        });
    });

    lightbox.querySelector('[data-lightbox-close]').addEventListener('click', () => lightbox.close());
    lightbox.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(index - 1));
    lightbox.querySelector('[data-lightbox-next]').addEventListener('click', () => show(index + 1));

    lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox || event.target.matches('.lightbox__stage')) {
            lightbox.close();
        }
    });

    lightbox.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            show(index - 1);
        } else if (event.key === 'ArrowRight') {
            show(index + 1);
        }
    });

    // Swipe left / right on phones
    let startX = null;

    lightbox.addEventListener('touchstart', (event) => { startX = event.touches[0].clientX; }, { passive: true });
    lightbox.addEventListener('touchend', (event) => {
        if (startX === null) {
            return;
        }

        const distance = event.changedTouches[0].clientX - startX;

        if (Math.abs(distance) > 50) {
            show(distance < 0 ? index + 1 : index - 1);
        }

        startX = null;
    });
};

const initPage = () => {
    const main = document.getElementById('main');

    // Run once per page: the marker lives on <main>, which is replaced on every navigation.
    if (! main || main.dataset.ready) {
        return;
    }

    main.dataset.ready = '1';
    setMenu(false);

    const consent = getConsent();

    if (consent === 'accepted') {
        loadAnalytics();
    }

    setCookieBanner(consent === null && analyticsLoaded === false);
    onScroll();
    initRotators();
    initReveal();
    initFaq();
    initLivePreviews();
    initLightbox();
};

// Livewire's navigation replaces the <html> attributes with the server's, which have no
// saved mode, so put the visitor's light / dark choice (and the "js" flag) back before the new page shows.
const applySavedTheme = () => {
    let theme = null;

    root.classList.add('js');

    try {
        theme = localStorage.getItem('flux.appearance');
    } catch (error) {
        // Storage can be blocked; fall back to the device setting.
    }

    if (theme === 'light' || theme === 'dark') {
        root.dataset.theme = theme;
    } else {
        delete root.dataset.theme;
    }
};

document.addEventListener('livewire:navigating', (event) => {
    cleanups.forEach((cleanup) => cleanup());
    cleanups = [];

    event.detail?.onSwap?.(applySavedTheme);
});

document.addEventListener('livewire:navigated', applySavedTheme);

let firstPage = true;

document.addEventListener('livewire:navigated', () => {
    initPage();

    // Count each page change in visitor statistics (the first page is counted by the tag itself)
    if (! firstPage && typeof window.gtag === 'function') {
        window.gtag('event', 'page_view', { page_location: location.href, page_title: document.title });
    }

    firstPage = false;
});

initPage();
