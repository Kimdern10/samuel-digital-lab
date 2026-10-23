// Small helpers for the public website. Kept dependency-free so pages stay light.

const root = document.documentElement;

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
