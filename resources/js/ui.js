// Shared UI behaviour that is driven by markup, so individual views stay
// declarative:
//   <form data-confirm="Delete this?">   - styled confirmation dialog before submit
//   <button data-confirm="...">          - same, scoped to one submit button
//   (every form)                         - submit button shows a spinner and is
//                                          disabled while the request is in flight
//   <form data-no-loading>               - opt out of the loading state

const CONFIRM_TONES = [
    [/^\s*(delete|remove)/i, { label: 'Delete', tone: 'danger' }],
    [/^\s*reset/i, { label: 'Reset', tone: 'danger' }],
    [/^\s*archive/i, { label: 'Archive', tone: 'danger' }],
    [/(delete|cannot be undone|replaces the entire)/i, { label: 'Continue', tone: 'danger' }],
];

function describeConfirm(message) {
    for (const [pattern, result] of CONFIRM_TONES) {
        if (pattern.test(message)) return result;
    }
    return { label: 'Continue', tone: 'primary' };
}

const SPINNER = '<svg data-loading-spinner class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">'
    + '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>'
    + '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';

const LOADING_TIMEOUT_MS = 60000;

function submitButtons(form) {
    return [...form.querySelectorAll('button:not([type="button"]):not([type="reset"]), input[type="submit"]')];
}

function lockForm(form, submitter) {
    const primary = submitter ?? submitButtons(form)[0];

    submitButtons(form).forEach((button) => {
        if (button.disabled) return;

        button.disabled = true;
        button.dataset.loadingLocked = '';

        if (button === primary && button.tagName === 'BUTTON') {
            button.setAttribute('aria-busy', 'true');
            button.insertAdjacentHTML('afterbegin', SPINNER);
        }
    });

    setTimeout(() => unlockForm(form), LOADING_TIMEOUT_MS);
}

function unlockForm(form) {
    form.querySelectorAll('[data-loading-locked]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.removeAttribute('data-loading-locked');
        button.querySelector('[data-loading-spinner]')?.remove();
    });
}

export function registerUi(Alpine) {
    Alpine.data('confirmDialog', () => ({
        visible: false,
        message: '',
        label: 'Continue',
        tone: 'primary',
        onConfirm: null,

        open({ message, onConfirm }) {
            const { label, tone } = describeConfirm(message);

            this.message = message;
            this.label = label;
            this.tone = tone;
            this.onConfirm = onConfirm;
            this.visible = true;
            this.$nextTick(() => this.$refs.confirmButton?.focus());
        },

        cancel() {
            this.visible = false;
            this.onConfirm = null;
        },

        confirm() {
            const callback = this.onConfirm;

            this.visible = false;
            this.onConfirm = null;
            callback?.();
        },
    }));

    // Forms we have already confirmed; lets the re-submit below pass through.
    const approved = new WeakSet();

    // Capture phase so the form's own handlers (Alpine, inline) don't run
    // until the user has actually confirmed.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (approved.has(form)) return;

        const submitter = event.submitter ?? null;
        const message = submitter?.dataset.confirm ?? form.dataset.confirm;
        if (!message) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        const proceed = () => {
            approved.add(form);
            form.requestSubmit(submitter ?? undefined);
            // requestSubmit dispatches synchronously; clear in case validation blocked it.
            setTimeout(() => approved.delete(form), 0);
        };

        if (document.querySelector('[data-confirm-dialog]')) {
            window.dispatchEvent(new CustomEvent('confirm-request', { detail: { message, onConfirm: proceed } }));
        } else if (window.confirm(message)) {
            proceed();
        }
    }, true);

    // Bubble phase: skip anything a handler already cancelled (validation,
    // JS-driven forms like face login / check-in).
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        if ('noLoading' in form.dataset || form.target === '_blank') return;

        const submitter = event.submitter ?? null;

        // Disable after the browser has built the form data, otherwise the
        // clicked button's name/value would be dropped from the request.
        setTimeout(() => lockForm(form, submitter), 0);
    });

    // Back/forward cache restores the page with buttons still locked.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        document.querySelectorAll('form').forEach(unlockForm);
    });
}
