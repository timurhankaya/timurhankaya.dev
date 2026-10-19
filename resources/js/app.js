document.documentElement.classList.add('js');

const qs = (sel, root = document) => root.querySelector(sel);
const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

function safeInit(name, fn, onError) {
    try {
        fn();
    } catch (err) {
        // eslint-disable-next-line no-console
        console.error(`[app] ${name} failed`, err);
        onError?.(err);
    }
}

function isReducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false;
}

function initReveal() {
    const items = qsa('[data-reveal]');
    if (items.length === 0) {
        return;
    }

    if (isReducedMotion() || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -14% 0px' },
    );
    items.forEach((el) => observer.observe(el));
}

// `literal` keeps backslash sequences as typed (code samples); otherwise "\n" in data attributes becomes a newline.
async function copyText(text, { literal = false } = {}) {
    const value = literal ? String(text ?? '') : String(text ?? '').replaceAll('\\n', '\n');
    if (value === '') {
        return false;
    }

    try {
        await navigator.clipboard.writeText(value);
        return true;
    } catch {
        const el = document.createElement('textarea');
        el.value = value;
        el.setAttribute('readonly', 'true');
        Object.assign(el.style, {
            position: 'fixed',
            left: '0',
            top: '0',
            width: '2em',
            height: '2em',
            padding: '0',
            border: 'none',
            outline: 'none',
            boxShadow: 'none',
            background: 'transparent',
            opacity: '0',
            pointerEvents: 'none',
        });
        document.body.appendChild(el);
        el.focus();
        el.select();
        el.setSelectionRange(0, value.length);
        let ok = false;
        try {
            ok = document.execCommand('copy');
        } finally {
            document.body.removeChild(el);
        }
        return ok;
    }
}

function showCopyToast(message) {
    const existing = document.getElementById('copy-toast');
    if (existing) {
        existing.remove();
    }
    const toast = document.createElement('div');
    toast.id = 'copy-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    Object.assign(toast.style, {
        position: 'fixed',
        bottom: '1.5rem',
        left: '50%',
        transform: 'translateX(-50%)',
        zIndex: '9999',
        padding: '0.5rem 1rem',
        borderRadius: '0.75rem',
        background: 'rgb(24 24 27)',
        color: 'white',
        fontSize: '0.875rem',
        fontWeight: '600',
        boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
        transition: 'opacity 0.2s',
    });
    toast.textContent = message;
    document.body.appendChild(toast);
    window.setTimeout(() => {
        toast.style.opacity = '0';
        window.setTimeout(() => toast.remove(), 200);
    }, 1200);
}

function initCopyButtons() {
    let lastCopyAt = 0;
    const handleCopy = async (e, btn) => {
        if (Date.now() - lastCopyAt < 400) {
            return;
        }
        lastCopyAt = Date.now();
        const value = btn.getAttribute('data-copy-text') ?? '';
        const ok = await copyText(value);

        const label = btn.getAttribute('data-copy-label');
        const onSuccess = btn.getAttribute('data-copy-label-success') ?? 'Copied';
        const onFail = btn.getAttribute('data-copy-label-fail') ?? label;

        showCopyToast(ok ? onSuccess : onFail);
        if (label) {
            btn.textContent = ok ? onSuccess : onFail;
            window.setTimeout(() => {
                btn.textContent = label;
            }, 1200);
        }
    };

    const delegateCopy = (e) => {
        const btn = e.target instanceof Element ? e.target.closest('[data-copy-text]') : null;
        if (!(btn instanceof HTMLButtonElement || btn instanceof HTMLElement)) {
            return;
        }
        if (e.type === 'touchstart' || e.type === 'touchend') {
            e.preventDefault();
        }
        handleCopy(e, btn);
    };

    document.addEventListener('click', delegateCopy);
    document.addEventListener('touchstart', delegateCopy, { passive: false });
}

function initCommandPalette() {
    const root = qs('[data-command-palette]');
    if (!root) {
        return;
    }

    const input = qs('[data-command-palette-input]', root);
    const closeOverlay = qs('[data-command-palette-close]', root);
    const triggers = qsa('[data-command-palette-trigger]');
    const commands = qsa('[data-command]', root);

    let open = false;
    let lastActive = null;
    let activeIndex = 0;

    const setOpen = (next) => {
        open = next;
        root.classList.toggle('hidden', !open);
        root.setAttribute('aria-hidden', open ? 'false' : 'true');

        if (open) {
            lastActive = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            activeIndex = 0;
            if (input) {
                input.value = '';
                filter('');
                queueMicrotask(() => input.focus());
            }
        } else if (lastActive) {
            lastActive.focus();
            lastActive = null;
        }
    };

    const visibleCommands = () => commands.filter((btn) => !btn.hidden);
    // Case- and accent-insensitive in both languages: "iletisim" finds "İletişim", "Is" finds "İş".
    const fold = (text) => String(text ?? '').trim().toLocaleLowerCase('tr').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ı/g, 'i');

    const setActive = (idx) => {
        const list = visibleCommands();
        if (list.length === 0) {
            return;
        }
        activeIndex = Math.max(0, Math.min(idx, list.length - 1));
        list.forEach((el, i) => el.classList.toggle('is-active', i === activeIndex));
        list[activeIndex].scrollIntoView({ block: 'nearest' });
    };

    const filter = (q) => {
        const query = fold(q);
        commands.forEach((btn) => {
            const label = fold(btn.getAttribute('data-command-label') ?? btn.textContent);
            btn.hidden = query !== '' && !label.includes(query);
            btn.classList.remove('is-active');
        });
        // A group whose every command is filtered out hides its heading too.
        qsa('[data-command-palette-list]', root).forEach((list) => {
            const empty = qsa('[data-command]', list).every((btn) => btn.hidden);
            list.hidden = empty;
            if (list.previousElementSibling?.classList.contains('brand-palette-group')) {
                list.previousElementSibling.hidden = empty;
            }
        });
        const none = commands.every((btn) => btn.hidden);
        const emptyNote = qs('[data-command-palette-empty]', root);
        if (emptyNote) {
            emptyNote.hidden = !none;
        }
        setActive(0);
    };

    const run = async (btn) => {
        const href =
            btn.getAttribute('data-command-href') ??
            (btn instanceof HTMLAnchorElement ? btn.getAttribute('href') : null);
        const copy = btn.getAttribute('data-command-copy');
        const copyCurrentUrl = btn.getAttribute('data-command-copy-current-url');

        if (href) {
            setOpen(false);
            window.location.href = href;
            return;
        }

        if (copyCurrentUrl) {
            await copyText(window.location.href);
            setOpen(false);
            return;
        }

        if (copy) {
            await copyText(copy);
            setOpen(false);
        }
    };

    triggers.forEach((t) => t.addEventListener('click', () => setOpen(true)));
    closeOverlay?.addEventListener('click', () => setOpen(false));

    root.addEventListener('click', (e) => {
        const el = e.target instanceof Element ? e.target.closest('[data-command]') : null;
        if (!(el instanceof HTMLElement)) {
            return;
        }

        if (el instanceof HTMLAnchorElement) {
            const isModified =
                e instanceof MouseEvent &&
                (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1);

            if (!isModified) {
                setOpen(false);
            }

            return;
        }

        run(el);
    });

    input?.addEventListener('input', () => filter(input.value));

    root.addEventListener('keydown', (e) => {
        if (!open) {
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            setOpen(false);
            return;
        }

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(activeIndex + (e.key === 'ArrowDown' ? 1 : -1));
            return;
        }

        if (e.key === 'Enter') {
            const list = visibleCommands();
            const btn = list[activeIndex];
            if (btn) {
                e.preventDefault();
                run(btn);
            }
            return;
        }

        if (e.key === 'Tab') {
            const focusables = qsa('button,[href],input,[tabindex]:not([tabindex="-1"])', root).filter(
                (el) => !el.hasAttribute('disabled') && !el.getAttribute('aria-hidden') && !el.hidden,
            );
            if (focusables.length === 0) {
                return;
            }
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;

            if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            } else if (e.shiftKey && active === first) {
                e.preventDefault();
                last.focus();
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        const k = e.key?.toLowerCase?.() ?? '';
        const cmdK = (e.metaKey || e.ctrlKey) && k === 'k';

        if (cmdK) {
            e.preventDefault();
            setOpen(true);
            return;
        }

        if (open && k === 'escape') {
            e.preventDefault();
            setOpen(false);
        }
    });
}

function initModals() {
    const modals = qsa('[data-modal]').filter((el) => el instanceof HTMLElement);
    if (modals.length === 0) {
        return;
    }

    const byName = new Map(modals.map((m) => [m.getAttribute('data-modal'), m]));
    let openName = null;
    let lastActive = null;

    const setOpen = (name, next) => {
        const modal = byName.get(name);
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        const displayClass = modal.getAttribute('data-modal-display');

        if (next) {
            openName = name;
            lastActive = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            modal.classList.remove('hidden');
            if (displayClass) {
                modal.classList.add(displayClass);
            }
            modal.setAttribute('aria-hidden', 'false');

            const focusTarget = qs('[data-modal-close]', modal);
            if (focusTarget instanceof HTMLElement) {
                queueMicrotask(() => focusTarget.focus());
            }
        } else {
            openName = null;
            modal.classList.add('hidden');
            if (displayClass) {
                modal.classList.remove(displayClass);
            }
            modal.setAttribute('aria-hidden', 'true');
            if (lastActive) {
                lastActive.focus();
                lastActive = null;
            }
        }
    };

    document.addEventListener('click', (e) => {
        const t = e.target instanceof Element ? e.target : null;
        if (!t) {
            return;
        }

        const opener = t.closest('[data-modal-open]');
        if (opener) {
            const name = opener.getAttribute('data-modal-open');
            if (name) {
                e.preventDefault();
                setOpen(name, true);
            }
            return;
        }

        const closer = t.closest('[data-modal-close]');
        if (closer) {
            const parentModal = t.closest('[data-modal]');
            const name = parentModal?.getAttribute('data-modal') ?? openName;
            if (name) {
                e.preventDefault();
                setOpen(name, false);
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        if (!openName) {
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            setOpen(openName, false);
        }
    });
}

function initTiltCards() {
    if (isReducedMotion()) {
        return;
    }

    const cards = qsa('[data-tilt]').filter((el) => {
        if (!(el instanceof HTMLElement)) {
            return false;
        }

        if (el.hasAttribute('data-tilt-disabled')) {
            return false;
        }

        return true;
    });
    if (cards.length === 0) {
        return;
    }

    cards.forEach((card) => {
        let raf = 0;
        let next = null;
        let rect = null;

        const apply = () => {
            raf = 0;
            if (!next) return;
            const { rx, ry } = next;
            card.style.transform = `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg) translateZ(0)`;
            card.style.transition = 'transform 140ms ease-out';
        };

        const refreshRect = () => {
            rect = card.getBoundingClientRect();
        };

        const onMove = (e) => {
            if (!rect) {
                refreshRect();
            }
            if (!rect) {
                return;
            }
            const px = (e.clientX - rect.left) / rect.width;
            const py = (e.clientY - rect.top) / rect.height;
            const ry = (px - 0.5) * 8;
            const rx = (0.5 - py) * 6;
            next = { rx, ry };
            if (!raf) {
                raf = window.requestAnimationFrame(apply);
            }
        };

        const reset = () => {
            next = null;
            rect = null;
            if (raf) {
                window.cancelAnimationFrame(raf);
                raf = 0;
            }
            card.style.transform = '';
            card.style.transition = 'transform 260ms ease-out';
        };

        card.addEventListener('mouseenter', refreshRect);
        card.addEventListener('mousemove', onMove);
        card.addEventListener('mouseleave', reset);
        card.addEventListener('blur', reset, true);
    });
}

function initImposterArena() {
    if (isReducedMotion()) {
        return;
    }

    const arenas = qsa('[data-imposter-arena]').filter((el) => el instanceof HTMLElement);
    if (arenas.length === 0) {
        return;
    }

    arenas.forEach((arena) => {
        let rect = null;
        let raf = 0;
        let next = null;

        const refreshRect = () => {
            rect = arena.getBoundingClientRect();
        };

        const apply = () => {
            raf = 0;
            if (!rect || !next) {
                return;
            }

            const x = Math.max(0, Math.min(1, next.x));
            const y = Math.max(0, Math.min(1, next.y));

            arena.style.setProperty('--mx', `${Math.round(x * 100)}%`);
            arena.style.setProperty('--my', `${Math.round(y * 100)}%`);
        };

        const onMove = (e) => {
            if (!rect) {
                refreshRect();
            }
            if (!rect) {
                return;
            }

            next = {
                x: (e.clientX - rect.left) / Math.max(1, rect.width),
                y: (e.clientY - rect.top) / Math.max(1, rect.height),
            };

            if (!raf) {
                raf = window.requestAnimationFrame(apply);
            }
        };

        const onLeave = () => {
            next = null;
            rect = null;
            if (raf) {
                window.cancelAnimationFrame(raf);
                raf = 0;
            }
            arena.style.setProperty('--mx', '50%');
            arena.style.setProperty('--my', '35%');
        };

        const ro = new ResizeObserver(() => refreshRect());
        ro.observe(arena);

        arena.addEventListener('pointerenter', refreshRect, { passive: true });
        arena.addEventListener('pointermove', onMove, { passive: true });
        arena.addEventListener('pointerleave', onLeave, { passive: true });

        window.addEventListener(
            'beforeunload',
            () => {
                ro.disconnect();
                onLeave();
            },
            { once: true },
        );
    });
}

function initMobileNav() {
    const menu = qs('[data-mobile-nav]');
    if (!(menu instanceof HTMLElement)) {
        return;
    }

    let open = false;
    let lastToggleTouchAt = 0;

    const setOpen = (next) => {
        open = next;
        menu.classList.toggle('hidden', !open);
        const t = qs('[data-mobile-nav-toggle]');
        if (t) {
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    };

    const handleToggle = (e) => {
        const toggle = e.target instanceof Element ? e.target.closest('[data-mobile-nav-toggle]') : null;
        if (!toggle) {
            return;
        }
        if (e.type === 'touchstart') {
            e.preventDefault();
            lastToggleTouchAt = Date.now();
        } else if (e.type === 'click' && Date.now() - lastToggleTouchAt < 500) {
            return;
        }
        setOpen(!open);
    };

    document.addEventListener('touchstart', handleToggle, { passive: false });
    document.addEventListener('click', handleToggle);

    menu.addEventListener('click', (e) => {
        const link = e.target instanceof Element ? e.target.closest('a') : null;
        if (link) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (!open) {
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            setOpen(false);
            qs('[data-mobile-nav-toggle]')?.focus();
        }
    });

    document.addEventListener('click', (e) => {
        if (!open) {
            return;
        }

        const target = e.target instanceof Node ? e.target : null;
        if (!target) {
            return;
        }

        const toggle = qs('[data-mobile-nav-toggle]');
        const clickedToggle = toggle && toggle.contains(target);
        const clickedMenu = menu.contains(target);
        if (!clickedToggle && !clickedMenu) {
            setOpen(false);
        }
    });

    setOpen(false);
}

function initHashScroll() {
    const hash = window.location.hash ?? '';
    if (!hash || hash === '#') {
        return;
    }

    const id = decodeURIComponent(hash.slice(1));
    if (!id) {
        return;
    }

    const target = document.getElementById(id);
    if (!(target instanceof HTMLElement)) {
        return;
    }

    const behavior = isReducedMotion() ? 'auto' : 'smooth';
    const scroll = () => target.scrollIntoView({ behavior, block: 'start' });

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(scroll);
    });

    window.setTimeout(scroll, 180);
    window.setTimeout(scroll, 520);
}

function initProjectDialogs() {
    qsa('[data-project-dialog-open]').forEach((button) => {
        const dialog = document.getElementById(button.dataset.projectDialogOpen);
        if (!(dialog instanceof HTMLDialogElement)) return;
        button.addEventListener('click', () => dialog.showModal());
        dialog.addEventListener('click', (event) => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right ||
                event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        });
    });
}

// Contents lists follow the reader: the last section whose heading passed under the sticky nav is marked current.
function initToc() {
    qsa('[data-toc]').forEach((toc) => {
        const links = qsa('a[href^="#"]', toc);
        const targets = links.map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1))));
        if (!targets.some(Boolean)) {
            return;
        }
        let frame = 0;
        const update = () => {
            frame = 0;
            const line = (qs('.brand-nav')?.offsetHeight ?? 0) + 32;
            let current = -1;
            targets.forEach((target, index) => {
                if (target && target.getBoundingClientRect().top - line <= 1) {
                    current = index;
                }
            });
            // At the very bottom the last section counts as read even if its heading never reached the line.
            if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
                current = targets.length - 1;
            }
            links.forEach((link, index) => {
                if (index === current) {
                    link.setAttribute('aria-current', 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };
        const schedule = () => {
            if (!frame) {
                frame = window.requestAnimationFrame(update);
            }
        };
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        update();
    });
}

// Code component copy button: copies the code without its line numbers and confirms in place.
function initCodeCopy() {
    document.addEventListener('click', async (event) => {
        const button = event.target instanceof Element ? event.target.closest('[data-copy-code]') : null;
        const code = button?.closest('.brand-code')?.querySelector('pre code');
        if (!button || !code) {
            return;
        }
        const clone = code.cloneNode(true);
        clone.querySelectorAll('.hl-gutter').forEach((gutter) => gutter.remove());
        if (await copyText(clone.textContent, { literal: true })) {
            const label = button.textContent;
            button.textContent = button.dataset.copiedLabel ?? label;
            button.dataset.copied = '';
            window.setTimeout(() => {
                button.textContent = label;
                delete button.dataset.copied;
            }, 1600);
        }
    });
}

// Desktop nav menus: hover opens for mouse users; click, Enter and Space toggle; Escape and focus leaving close.
function initNavMenus() {
    qsa('[data-nav-menu]').forEach((menu) => {
        const trigger = qs('button[aria-controls]', menu);
        if (!(trigger instanceof HTMLButtonElement)) {
            return;
        }
        const setOpen = (open) => {
            menu.toggleAttribute('data-open', open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        trigger.addEventListener('click', (event) => {
            // A mouse click while hovering keeps the menu open instead of closing what hover just opened.
            if (event.detail > 0 && menu.matches(':hover')) {
                setOpen(true);
                return;
            }
            setOpen(trigger.getAttribute('aria-expanded') !== 'true');
        });
        menu.addEventListener('mouseenter', () => setOpen(true));
        menu.addEventListener('mouseleave', () => setOpen(false));
        menu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
                trigger.focus();
            }
        });
        menu.addEventListener('focusout', (event) => {
            if (!menu.contains(event.relatedTarget)) {
                setOpen(false);
            }
        });
        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target)) {
                setOpen(false);
            }
        });
    });
}

function initLocaleLinks() {
    qsa('[data-locale-switch]').forEach((link) => {
        link.addEventListener('click', () => {
            const destination = new URL(link.href);
            const id = decodeURIComponent((window.location.hash ?? '').slice(1));
            // Anchors are localized (#hakkimda / #about); the section names its twin in data-anchor-alt.
            const alt = id ? document.getElementById(id)?.dataset.anchorAlt : null;
            destination.hash = alt ? `#${alt}` : window.location.hash;
            link.href = destination.href;
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('touchstart', () => {}, { passive: true });
    safeInit('reveal', initReveal, () => {
        qsa('[data-reveal]').forEach((el) => el.classList.add('is-revealed'));
        document.documentElement.classList.remove('js');
    });
    safeInit('copyButtons', initCopyButtons);
    safeInit('commandPalette', initCommandPalette);
    safeInit('mobileNav', initMobileNav);
    safeInit('modals', initModals);
    safeInit('projectDialogs', initProjectDialogs);
    safeInit('localeLinks', initLocaleLinks);
    safeInit('imposterArena', initImposterArena);
    safeInit('hashScroll', initHashScroll);
    safeInit('navMenus', initNavMenus);
    safeInit('codeCopy', initCodeCopy);
    safeInit('toc', initToc);
});
