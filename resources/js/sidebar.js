// Sidebar behaviour: collapsible rail, collapsible nav groups and the
// Ctrl/Cmd+K menu search. All of it works off the markup rendered by
// layouts/sidebar.blade.php, so permission / menu-visibility rules stay in
// PHP - the search only ever sees links the user is actually allowed to use.

const COLLAPSED_KEY = 'sidebar-collapsed';
const GROUPS_KEY = 'sidebar-groups';

// localStorage can throw (private windows, blocked site data); the sidebar
// must still work without it.
const storage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // ignore
        }
    },
};

function readGroups() {
    try {
        return JSON.parse(storage.get(GROUPS_KEY)) ?? {};
    } catch {
        return {};
    }
}

function collectMenuItems() {
    const seen = new Set();
    const items = [];

    // The desktop sidebar is always in the DOM (just hidden on small screens),
    // so reading it avoids duplicates from the mobile drawer copy.
    document.querySelectorAll('#desktop-sidebar [data-nav-item]').forEach((link) => {
        if (seen.has(link.href)) return;
        seen.add(link.href);

        items.push({
            label: link.dataset.label,
            href: link.href,
            icon: link.dataset.icon || '',
            group: link.closest('[data-nav-group]')?.dataset.label ?? 'General',
            current: link.getAttribute('aria-current') === 'page',
        });
    });

    return items;
}

export function registerSidebar(Alpine) {
    Alpine.store('layout', {
        collapsed: document.documentElement.dataset.sidebar === 'collapsed',

        toggle() {
            this.collapsed = !this.collapsed;
            // The attribute drives the sidebar width in CSS (set before first
            // paint by an inline script, so a saved rail doesn't flash open).
            document.documentElement.dataset.sidebar = this.collapsed ? 'collapsed' : 'expanded';
            storage.set(COLLAPSED_KEY, this.collapsed ? '1' : '0');
        },
    });

    // One instance per rendered sidebar. The mobile drawer is never a rail.
    Alpine.data('sidebar', (mobile = false) => ({
        mobile,
        modifier: /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘' : 'Ctrl',

        get rail() {
            return !this.mobile && Alpine.store('layout').collapsed;
        },

        showTip(el) {
            if (!this.rail) return;

            const box = el.getBoundingClientRect();
            window.dispatchEvent(new CustomEvent('rail-tip', {
                detail: { text: el.dataset.label, top: box.top + box.height / 2, left: box.right + 10 },
            }));
        },

        hideTip() {
            window.dispatchEvent(new CustomEvent('rail-tip', { detail: null }));
        },
    }));

    Alpine.data('navGroup', (key, active) => ({
        open: true,
        id: `nav-group-${key}`,

        init() {
            const saved = readGroups();

            // A group containing the current page is always open so you can see
            // where you are; otherwise restore what the user last chose.
            this.open = active || !(key in saved) || saved[key];
        },

        toggle() {
            this.open = !this.open;
            storage.set(GROUPS_KEY, JSON.stringify({ ...readGroups(), [key]: this.open }));
        },
    }));

    Alpine.data('menuSearch', () => ({
        visible: false,
        query: '',
        items: [],
        active: 0,

        get results() {
            const terms = this.query.trim().toLowerCase().split(/\s+/).filter(Boolean);
            if (!terms.length) return this.items;

            return this.items
                .map((item) => {
                    const label = item.label.toLowerCase();
                    const haystack = `${label} ${item.group.toLowerCase()}`;

                    if (!terms.every((term) => haystack.includes(term))) return null;

                    // Matches in the item's own name outrank matches via its group.
                    return { item, score: terms.every((term) => label.includes(term)) ? 0 : 1 };
                })
                .filter(Boolean)
                .sort((a, b) => a.score - b.score)
                .map(({ item }) => item);
        },

        open() {
            this.items = collectMenuItems();
            this.query = '';
            this.active = 0;
            this.visible = true;
            this.$nextTick(() => this.$refs.input?.focus());
        },

        close() {
            this.visible = false;
        },

        toggleFromShortcut(event) {
            event.preventDefault();
            this.visible ? this.close() : this.open();
        },

        move(step) {
            const count = this.results.length;
            if (!count) return;

            this.active = (this.active + step + count) % count;
            this.$nextTick(() => this.$refs.list?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
        },

        go() {
            const target = this.results[this.active];
            if (target) window.location.href = target.href;
        },
    }));
}
