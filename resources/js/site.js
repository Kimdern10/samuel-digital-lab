// Interactions and motion for the public website. Dependency-free so pages stay light.

const root = document.documentElement;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Light / dark switch, remembered on this device
document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const current = root.dataset.theme
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        const next = current === 'dark' ? 'light' : 'dark';

        root.dataset.theme = next;

        try {
            localStorage.setItem('sdl-theme', next);
        } catch (error) {
            // Storage can be blocked; the switch still works for this page.
        }
    });
});

// Header turns solid after scrolling, and a thin bar shows how far down the page you are
const header = document.querySelector('.site-header');
const progress = document.querySelector('.progress');
let ticking = false;

const onScroll = () => {
    const scrolled = window.scrollY;
    const height = document.documentElement.scrollHeight - window.innerHeight;

    header?.classList.toggle('is-solid', scrolled > 24);
    progress?.style.setProperty('--progress', height > 0 ? (scrolled / height).toFixed(4) : 0);
    ticking = false;
};

window.addEventListener('scroll', () => {
    if (! ticking) {
        window.requestAnimationFrame(onScroll);
        ticking = true;
    }
}, { passive: true });
onScroll();

// Mobile menu
const menuButton = document.querySelector('[data-menu-toggle]');

const setMenu = (open) => {
    root.classList.toggle('menu-open', open);
    menuButton?.setAttribute('aria-expanded', String(open));
};

menuButton?.addEventListener('click', () => setMenu(! root.classList.contains('menu-open')));
document.querySelectorAll('.mobile-menu a').forEach((link) => link.addEventListener('click', () => setMenu(false)));
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMenu(false);
    }
});

// Rotating word at the end of the main heading
document.querySelectorAll('[data-rotate]').forEach((rotator) => {
    const words = JSON.parse(rotator.dataset.rotate || '[]');
    const word = rotator.querySelector('.rotator__word');

    if (reduceMotion || words.length < 2 || ! word) {
        return;
    }

    let index = 0;

    setInterval(() => {
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
});

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

// Reveal things as they scroll into view
const revealTargets = document.querySelectorAll('[data-reveal], [data-count], .score__arc, .steps');

if ('IntersectionObserver' in window && ! reduceMotion) {
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

    revealTargets.forEach((element) => observer.observe(element));
} else {
    revealTargets.forEach((element) => element.classList.add('is-visible'));
    document.querySelectorAll('.score__arc').forEach(fillRing);
}

// A soft light that follows the pointer across cards
document.querySelectorAll('.card').forEach((card) => {
    card.addEventListener('pointermove', (event) => {
        const box = card.getBoundingClientRect();
        card.style.setProperty('--x', `${event.clientX - box.left}px`);
        card.style.setProperty('--y', `${event.clientY - box.top}px`);
    });
});

// Only one question open at a time
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

// "Get a quote" on a service card picks that service in the form
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-pick-service]');
    const select = document.getElementById('q-type');

    if (! link || ! select) {
        return;
    }

    const option = [...select.options].find((item) => item.value === link.dataset.pickService);

    if (option) {
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        select.dispatchEvent(new Event('input', { bubbles: true }));
    }
});

// Copy buttons, with a fallback that selects the text
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');

    if (! button) {
        return;
    }

    const target = document.getElementById(button.dataset.copy);
    const text = target.textContent.trim();
    const done = () => {
        button.textContent = 'Copied';
        setTimeout(() => { button.textContent = 'Copy'; }, 1600);
    };
    const select = () => {
        const range = document.createRange();
        range.selectNodeContents(target);
        const selection = getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(select);
    } else {
        select();
    }
});

// Tap-to-enlarge screenshot viewer
const lightbox = document.querySelector('[data-lightbox]');

if (lightbox) {
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
}
