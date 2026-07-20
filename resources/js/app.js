import '@tailwindplus/elements';
import { createIcons, icons } from 'lucide';

let lucideRescanTimer;

const renderLucideIcons = () => {
    clearTimeout(lucideRescanTimer);

    lucideRescanTimer = setTimeout(() => {
        createIcons({ icons });
    }, 16);
};

document.addEventListener('DOMContentLoaded', renderLucideIcons);
document.addEventListener('livewire:navigated', renderLucideIcons);

new MutationObserver(renderLucideIcons).observe(document.documentElement, {
    childList: true,
    subtree: true,
});

const toastEventMessages = {
    'block-type-deleted': ['Block type deleted', 'success'],
    'cms-settings-reset': ['Settings reset to defaults', 'success'],
    'cms-settings-saved': ['Settings saved', 'success'],
    'content-deleted': ['Content deleted', 'success'],
    'datasource-created': ['Datasource created', 'success'],
    'datasource-deleted': ['Datasource deleted', 'success'],
    'datasource-entry-created': ['Entry created', 'success'],
    'datasource-entry-deleted': ['Entry deleted', 'success'],
    'datasource-entry-updated': ['Entry saved', 'success'],
    'datasource-updated': ['Datasource saved', 'success'],
    'password-updated': ['Password updated', 'success'],
    'profile-updated': ['Profile saved', 'success'],
    'space-deleted': ['Space deleted', 'success'],
    'user-created': ['User created', 'success'],
    'user-deleted': ['User deleted', 'success'],
    'user-updated': ['User saved', 'success'],
};

let toastSequence = 0;
let suppressNextAutosave = false;

const normalizeToast = (detail = {}) => {
    if (typeof detail === 'string') {
        return { message: detail };
    }

    return Array.isArray(detail) ? (detail[0] || {}) : detail;
};

const showToast = (options = {}) => {
    const region = document.getElementById('pilot-toast-region');
    const { message, type = 'success', duration = 3500 } = normalizeToast(options);

    if (! region || ! message) {
        return;
    }

    const toast = document.createElement('div');
    const toastId = `pilot-toast-${++toastSequence}`;
    const icon = type === 'error' ? 'circle-alert' : type === 'warning' ? 'triangle-alert' : 'circle-check';

    toast.id = toastId;
    toast.className = `pilot-toast pilot-toast--${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.innerHTML = `
        <i data-lucide="${icon}" class="pilot-toast__icon" aria-hidden="true"></i>
        <span class="pilot-toast__message"></span>
        <button type="button" class="pilot-toast__close" aria-label="Dismiss notification">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
    `;
    toast.querySelector('.pilot-toast__message').textContent = message;

    const dismiss = () => {
        toast.classList.add('pilot-toast--leaving');
        setTimeout(() => toast.remove(), 180);
    };

    toast.querySelector('.pilot-toast__close').addEventListener('click', dismiss);
    region.append(toast);
    renderLucideIcons();
    requestAnimationFrame(() => toast.classList.add('pilot-toast--visible'));

    while (region.children.length > 3) {
        region.firstElementChild?.remove();
    }

    if (duration > 0) {
        setTimeout(dismiss, duration);
    }
};

window.PilotToast = { show: showToast };

document.addEventListener('toast', (event) => {
    const options = normalizeToast(event.detail);
    suppressNextAutosave = Boolean(options.suppressAutosave);
    showToast(options);
});

document.addEventListener('published', () => {
    suppressNextAutosave = true;
    showToast({ message: 'Published successfully' });
});

document.addEventListener('saved', () => {
    if (suppressNextAutosave) {
        suppressNextAutosave = false;
        return;
    }

    showToast({ message: 'Changes autosaved' });
});

document.addEventListener('error', (event) => {
    const detail = normalizeToast(event.detail);
    showToast({ message: detail.message || 'Something went wrong', type: 'error', duration: 5000 });
});

Object.entries(toastEventMessages).forEach(([eventName, [message, type]]) => {
    document.addEventListener(eventName, () => showToast({ message, type }));
});

const showSessionToast = () => {
    const region = document.getElementById('pilot-toast-region');

    if (! region?.dataset.sessionToast) {
        return;
    }

    try {
        const toast = JSON.parse(region.dataset.sessionToast);
        showToast(typeof toast === 'string' ? { message: toast } : toast);
    } catch {
        // A malformed flash message should never interrupt page navigation.
    }

    region.dataset.sessionToast = '';
};

document.addEventListener('DOMContentLoaded', showSessionToast);
document.addEventListener('livewire:navigated', showSessionToast);

let focusNavigationWasTab = false;

const textInputTypes = new Set(['', 'password', 'search', 'tel', 'text', 'url']);

const moveCaretToFieldEnd = (field) => {
    if (field.matches?.('[contenteditable="true"]')) {
        if (! field.textContent) {
            return;
        }

        const range = document.createRange();
        const selection = window.getSelection();

        range.selectNodeContents(field);
        range.collapse(false);
        selection.removeAllRanges();
        selection.addRange(range);

        return;
    }

    if (field instanceof HTMLTextAreaElement) {
        if (field.value === '') {
            return;
        }

        field.setSelectionRange(field.value.length, field.value.length);

        return;
    }

    if (! (field instanceof HTMLInputElement) || ! textInputTypes.has(field.type)) {
        return;
    }

    if (field.value === '') {
        return;
    }

    field.setSelectionRange(field.value.length, field.value.length);
};

document.addEventListener('keydown', (event) => {
    focusNavigationWasTab = event.key === 'Tab';
}, true);

document.addEventListener('pointerdown', () => {
    focusNavigationWasTab = false;
}, true);

document.addEventListener('focusin', (event) => {
    if (! focusNavigationWasTab || ! event.target.closest?.('.cms-shell')) {
        return;
    }

    requestAnimationFrame(() => {
        if (document.activeElement !== event.target) {
            return;
        }

        moveCaretToFieldEnd(event.target);
    });
}, true);

const registerPilotRichTextEditor = () => {
    window.Alpine.data('pilotRichTextEditor', (config) => ({
        html: config.value || '',
        lastSavedHtml: null,
        placeholder: config.placeholder || '',
        fieldKey: config.fieldKey,
        repeaterIndex: config.repeaterIndex,
        subFieldKey: config.subFieldKey,
        isRepeaterField: Boolean(config.isRepeaterField),
        sourceMode: false,
        saveTimer: null,
        active: {
            bold: false,
            italic: false,
            link: false,
            ol: false,
            ul: false,
            block: 'p',
        },

        init() {
            this.html = this.normalizeInitialHtml(this.html);
            this.lastSavedHtml = this.html;
            this.$refs.editor.innerHTML = this.html;
            this.refreshState();
        },

        normalizeInitialHtml(value) {
            const trimmed = String(value || '').trim();

            if (trimmed === '') {
                return '';
            }

            if (/<[a-z][\s\S]*>/i.test(trimmed)) {
                return this.sanitizeHtml(trimmed);
            }

            return this.plainTextToHtml(trimmed);
        },

        handleInput() {
            this.html = this.sanitizeHtml(this.$refs.editor.innerHTML);
            this.queueSave();
            this.refreshState();
        },

        handlePaste(event) {
            const clipboard = event.clipboardData || window.clipboardData;
            const html = clipboard?.getData('text/html');
            const text = clipboard?.getData('text/plain') || '';
            const content = html ? this.sanitizeHtml(html) : this.plainTextToHtml(text);

            this.insertHtml(content);
            this.handleInput();
        },

        runCommand(command, value = null) {
            this.focusEditor();
            document.execCommand(command, false, value);
            this.handleInput();
        },

        formatBlock(tag) {
            this.runCommand('formatBlock', tag);
        },

        createLink() {
            this.focusEditor();

            const existingLink = this.closestTag('a');
            const currentHref = existingLink?.getAttribute('href') || '';
            const href = window.prompt('Paste a URL', currentHref);

            if (href === null) {
                return;
            }

            const cleanHref = href.trim();

            if (cleanHref === '') {
                this.runCommand('unlink');
                return;
            }

            this.runCommand('createLink', cleanHref);
        },

        toggleSource() {
            if (this.sourceMode) {
                this.html = this.sanitizeHtml(this.html);
                this.$refs.editor.innerHTML = this.html;
                this.sourceMode = false;
                this.queueSave();
                this.$nextTick(() => this.focusEditor());
                return;
            }

            this.html = this.sanitizeHtml(this.$refs.editor.innerHTML);
            this.sourceMode = true;
            this.$nextTick(() => this.$refs.source.focus());
        },

        queueSave() {
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.flush(), 450);
        },

        flush() {
            clearTimeout(this.saveTimer);
            this.html = this.sanitizeHtml(this.sourceMode ? this.html : this.$refs.editor.innerHTML);

            if (! this.sourceMode) {
                this.$refs.editor.innerHTML = this.html;
            }

            if (this.html === this.lastSavedHtml) {
                return;
            }

            this.lastSavedHtml = this.html;

            if (this.isRepeaterField) {
                this.$wire.updateRepeaterField(this.fieldKey, this.repeaterIndex, this.subFieldKey, this.html);
                return;
            }

            this.$wire.updateField(this.fieldKey, this.html);
        },

        focusEditor() {
            if (this.sourceMode) {
                this.toggleSource();
            }

            this.$refs.editor.focus();
        },

        insertHtml(html) {
            this.focusEditor();
            document.execCommand('insertHTML', false, html);
        },

        refreshState() {
            this.active.bold = document.queryCommandState('bold');
            this.active.italic = document.queryCommandState('italic');
            this.active.ol = document.queryCommandState('insertOrderedList');
            this.active.ul = document.queryCommandState('insertUnorderedList');
            this.active.link = Boolean(this.closestTag('a'));
            this.active.block = this.currentBlock();
        },

        isBlock(tag) {
            return this.active.block === tag;
        },

        currentBlock() {
            const block = this.closestTag('h2,h3,blockquote,li,p,div');
            const tag = block?.tagName?.toLowerCase() || 'p';

            return tag === 'div' || tag === 'li' ? 'p' : tag;
        },

        closestTag(selector) {
            const selection = window.getSelection();

            if (! selection || selection.rangeCount === 0) {
                return null;
            }

            const node = selection.anchorNode?.nodeType === Node.TEXT_NODE
                ? selection.anchorNode.parentElement
                : selection.anchorNode;

            if (! node || ! this.$refs.editor.contains(node)) {
                return null;
            }

            return node.closest(selector);
        },

        plainTextToHtml(text) {
            return String(text || '')
                .split(/\n{2,}/)
                .map((paragraph) => paragraph.trim())
                .filter(Boolean)
                .map((paragraph) => `<p>${this.escapeHtml(paragraph).replace(/\n/g, '<br>')}</p>`)
                .join('');
        },

        sanitizeHtml(html) {
            const template = document.createElement('template');
            template.innerHTML = String(html || '');
            const allowedTags = new Set(['A', 'B', 'BLOCKQUOTE', 'BR', 'EM', 'H2', 'H3', 'I', 'LI', 'OL', 'P', 'STRONG', 'U', 'UL']);
            const allowedAttrs = new Set(['href', 'target', 'rel']);

            template.content.querySelectorAll('*').forEach((node) => {
                if (! allowedTags.has(node.tagName)) {
                    node.replaceWith(...Array.from(node.childNodes));
                    return;
                }

                Array.from(node.attributes).forEach((attribute) => {
                    if (! allowedAttrs.has(attribute.name)) {
                        node.removeAttribute(attribute.name);
                    }
                });

                if (node.tagName === 'A') {
                    const href = node.getAttribute('href') || '';

                    if (! /^(https?:|mailto:|tel:|\/|#)/i.test(href)) {
                        node.removeAttribute('href');
                    }

                    node.setAttribute('rel', 'noopener noreferrer');
                }
            });

            return template.innerHTML
                .replace(/<p>(\s|&nbsp;|<br>)*<\/p>/gi, '')
                .trim();
        },

        escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value;

            return div.innerHTML;
        },
    }));
};

if (window.Alpine) {
    registerPilotRichTextEditor();
} else {
    document.addEventListener('alpine:init', registerPilotRichTextEditor);
}
