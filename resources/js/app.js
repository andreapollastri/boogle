import Alpine from 'alpinejs';
import { exceptionMarkdown, exceptionPrompt } from './exception-export';

window.Alpine = Alpine;

const isTyping = (event) => {
    const el = event.target;
    return el instanceof HTMLElement && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName));
};

async function copyText(text) {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return;
    }

    // Fallback for plain-HTTP installs, where the async clipboard API is unavailable.
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    ta.remove();
}

window.copyText = copyText;

/**
 * <button x-data="copy('text')" @click="run()"> … <span x-text="label"></span>
 */
Alpine.data('copy', (text, idle = 'Copy', done = 'Copied') => ({
    label: idle,
    copied: false,
    async run(value = null) {
        await copyText(value ?? (typeof text === 'function' ? text() : text));
        this.copied = true;
        this.label = done;
        setTimeout(() => {
            this.copied = false;
            this.label = idle;
        }, 1800);
    },
}));

/**
 * Copy the exception on the current page as Markdown or as an AI prompt.
 */
Alpine.data('exceptionExport', () => ({
    copied: null,
    data() {
        const el = document.getElementById('exception-data');
        return el ? JSON.parse(el.textContent) : {};
    },
    async markdown() {
        await copyText(exceptionMarkdown(this.data()));
        this.flash('md');
    },
    async prompt() {
        await copyText(exceptionPrompt(this.data()));
        this.flash('prompt');
    },
    flash(kind) {
        this.copied = kind;
        setTimeout(() => (this.copied = null), 1800);
    },
}));

/**
 * ⌘K / Ctrl+K palette: jump to a project, a page, or search exceptions everywhere.
 */
Alpine.data('commandPalette', () => ({
    open: false,
    query: '',
    active: 0,
    projects: [],
    pages: [],
    searchUrl: '',
    init() {
        const el = document.getElementById('palette-data');
        if (el) {
            const data = JSON.parse(el.textContent);
            this.projects = data.projects;
            this.pages = data.pages;
            this.searchUrl = data.searchUrl;
        }
        window.addEventListener('open-palette', () => this.show());
    },
    show() {
        this.open = true;
        this.query = '';
        this.active = 0;
        this.$nextTick(() => this.$refs.input?.focus());
    },
    hide() {
        this.open = false;
    },
    get results() {
        const q = this.query.trim().toLowerCase();
        const match = (...values) => !q || values.some((v) => v && v.toLowerCase().includes(q));

        const items = [];
        this.projects
            .filter((p) => match(p.title, p.host, p.group))
            .slice(0, 8)
            .forEach((p) => items.push({ type: 'project', ...p }));

        if (q) {
            this.projects
                .filter((p) => match(p.title))
                .slice(0, 3)
                .forEach((p) => items.push({ type: 'exceptions', title: `${p.title} — exceptions`, url: p.exceptions_url, open: p.open }));
        }

        this.pages.filter((p) => match(p.title)).forEach((p) => items.push({ type: 'page', ...p }));

        if (q) {
            items.push({
                type: 'search',
                title: `Search exceptions for “${this.query.trim()}”`,
                url: `${this.searchUrl}?q=${encodeURIComponent(this.query.trim())}`,
            });
        }

        return items;
    },
    move(step) {
        const count = this.results.length;
        if (!count) return;
        this.active = (this.active + step + count) % count;
        this.$nextTick(() => this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
    },
    go(item = null) {
        const target = item ?? this.results[this.active];
        if (target) window.location.href = target.url;
    },
}));

/**
 * Selection + bulk actions on the exception list.
 */
Alpine.data('exceptionList', (ids = []) => ({
    selected: [],
    ids,
    get allSelected() {
        return this.ids.length > 0 && this.selected.length === this.ids.length;
    },
    toggleAll() {
        this.selected = this.allSelected ? [] : [...this.ids];
    },
    bulk(action) {
        if (action === 'delete' && !confirm(`Delete ${this.selected.length} exception(s)? This cannot be undone.`)) return;
        this.$refs.bulkAction.value = action;
        this.$refs.bulkForm.submit();
    },
    openRow(event, url) {
        if (event.target.closest('a, button, input, label')) return;
        if (event.metaKey || event.ctrlKey) {
            window.open(url, '_blank');
            return;
        }
        window.location.href = url;
    },
}));

// Global keyboard shortcuts.
document.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        window.dispatchEvent(new CustomEvent('open-palette'));
        return;
    }

    if (isTyping(event) || event.metaKey || event.ctrlKey || event.altKey) return;

    if (event.key === '/') {
        const search = document.querySelector('[data-shortcut="search"]');
        event.preventDefault();
        search ? search.focus() : window.dispatchEvent(new CustomEvent('open-palette'));
        return;
    }

    const shortcut = { j: 'older', k: 'newer', u: 'back' }[event.key];
    const target = shortcut && document.querySelector(`[data-shortcut="${shortcut}"]`);
    if (target) {
        event.preventDefault();
        target.click();
    }
});

Alpine.start();
