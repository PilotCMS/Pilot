(() => {
  if (window.__pilotFrontendEditorLoaded) {
    return;
  }

  window.__pilotFrontendEditorLoaded = true;

  const BLOCKS_BASE_URL = @json($blocksBaseUrl);
  const CSRF_TOKEN = @json($csrfToken);
  const DEFAULT_LOCALE = @json($locale);
  const ROOT_ID = 'pilot-frontend-editor-root';
  const HIGHLIGHT_ID = 'pilot-frontend-editor-highlight';
  const EDITABLE_SELECTOR = '[data-pilot-editable="block"]';

  const css = `
:host {
  all: initial;
  --pilot-bg: #0f172a;
  --pilot-surface: rgba(15, 23, 42, 0.96);
  --pilot-border: rgba(148, 163, 184, 0.28);
  --pilot-muted: #94a3b8;
  --pilot-text: #f8fafc;
  --pilot-accent: #14b8a6;
  --pilot-accent-strong: #0f766e;
  --pilot-danger: #fb7185;
  position: fixed;
  top: 18px;
  right: 18px;
  z-index: 2147483647;
  color: var(--pilot-text);
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  pointer-events: none;
}

:host * {
  box-sizing: border-box;
  letter-spacing: 0;
}

.panel {
  width: min(380px, calc(100vw - 36px));
  max-height: calc(100vh - 36px);
  display: flex;
  flex-direction: column;
  border: 1px solid var(--pilot-border);
  border-radius: 8px;
  background: var(--pilot-surface);
  box-shadow: 0 22px 56px rgba(15, 23, 42, 0.42);
  backdrop-filter: blur(16px);
  overflow: hidden;
  pointer-events: auto;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.18);
}

.title {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.title strong {
  font-size: 13px;
  font-weight: 700;
  color: var(--pilot-text);
}

.title span,
.muted {
  font-size: 11px;
  color: var(--pilot-muted);
}

.actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

button,
a.button {
  appearance: none;
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.08);
  color: var(--pilot-text);
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  line-height: 1;
  min-height: 30px;
  padding: 8px 10px;
  text-decoration: none;
}

button:hover,
a.button:hover {
  border-color: rgba(20, 184, 166, 0.8);
}

button[data-active="true"] {
  background: rgba(20, 184, 166, 0.22);
  border-color: rgba(20, 184, 166, 0.85);
}

.body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 180px;
  overflow: auto;
  padding: 14px;
}

.empty {
  border: 1px dashed rgba(148, 163, 184, 0.32);
  border-radius: 8px;
  color: var(--pilot-muted);
  font-size: 13px;
  line-height: 1.45;
  padding: 18px;
}

.selected {
  display: flex;
  flex-direction: column;
  gap: 4px;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.05);
  padding: 10px;
}

.selected strong {
  font-size: 13px;
}

.fields {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  color: #cbd5e1;
  font-size: 12px;
  font-weight: 650;
}

.field label code {
  color: var(--pilot-muted);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  font-size: 10px;
  font-weight: 500;
}

input,
textarea,
select {
  width: 100%;
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 6px;
  background: rgba(15, 23, 42, 0.9);
  color: var(--pilot-text);
  font: inherit;
  font-size: 13px;
  line-height: 1.4;
  outline: none;
  padding: 9px 10px;
}

textarea {
  min-height: 88px;
  resize: vertical;
}

input:focus,
textarea:focus,
select:focus {
  border-color: var(--pilot-accent);
  box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.18);
}

.checkbox {
  align-items: center;
  flex-direction: row;
  justify-content: space-between;
}

.checkbox input {
  width: 18px;
  height: 18px;
}

.footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border-top: 1px solid rgba(148, 163, 184, 0.18);
  padding: 12px 14px;
}

.status {
  color: var(--pilot-muted);
  font-size: 11px;
}

.status[data-kind="success"] {
  color: #5eead4;
}

.status[data-kind="error"] {
  color: var(--pilot-danger);
}

.collapsed {
  width: auto;
}

.collapsed .body,
.collapsed .footer {
  display: none;
}

@media (max-width: 640px) {
  :host {
    top: auto;
    right: 10px;
    bottom: 10px;
    left: 10px;
  }

  .panel {
    width: 100%;
    max-height: 72vh;
  }
}
`;

  const highlightCss = `
#${HIGHLIGHT_ID} {
  position: fixed;
  z-index: 2147483646;
  pointer-events: none;
  border: 2px solid #14b8a6;
  border-radius: 8px;
  box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.08), 0 12px 32px rgba(15, 23, 42, 0.18);
  opacity: 0;
  transition: opacity 120ms ease, transform 120ms ease, width 120ms ease, height 120ms ease;
}
`;

  const state = {
    active: true,
    collapsed: false,
    selectedElement: null,
    selectedBlock: null,
    fields: new Map(),
    saveTimer: null,
    host: null,
    root: null,
    panel: null,
    body: null,
    status: null,
    activeButton: null,
  };

  const locale = () => document.documentElement.lang || DEFAULT_LOCALE || 'en';

  const isEditorElement = (element) => state.host && state.host.contains(element);

  const ensureHighlight = () => {
    if (document.getElementById(HIGHLIGHT_ID)) {
      return;
    }

    const style = document.createElement('style');
    style.textContent = highlightCss;
    document.head.appendChild(style);

    const highlight = document.createElement('div');
    highlight.id = HIGHLIGHT_ID;
    document.body.appendChild(highlight);
  };

  const editableFrom = (target) => target?.closest?.(EDITABLE_SELECTOR) ?? null;

  const setStatus = (message, kind = '') => {
    if (!state.status) {
      return;
    }

    state.status.textContent = message;
    state.status.dataset.kind = kind;
  };

  const updateHighlight = (element) => {
    const highlight = document.getElementById(HIGHLIGHT_ID);

    if (!highlight || !element || !state.active) {
      if (highlight) {
        highlight.style.opacity = '0';
      }
      return;
    }

    const rect = element.getBoundingClientRect();
    highlight.style.opacity = '1';
    highlight.style.transform = `translate(${rect.left}px, ${rect.top}px)`;
    highlight.style.width = `${rect.width}px`;
    highlight.style.height = `${rect.height}px`;
  };

  const fieldValue = (field, data, rawData) => {
    const value = data[field.key] ?? rawData[field.key] ?? field.default ?? '';

    if (Array.isArray(value) || (value && typeof value === 'object')) {
      return JSON.stringify(value, null, 2);
    }

    return value ?? '';
  };

  const createInput = (field, value) => {
    if (field.type === 'textarea' || field.type === 'richtext') {
      const textarea = document.createElement('textarea');
      textarea.value = value;
      textarea.rows = field.type === 'richtext' ? 7 : 4;
      return textarea;
    }

    if (field.type === 'number') {
      const input = document.createElement('input');
      input.type = 'number';
      input.value = value;
      if (field.min !== undefined) {
        input.min = field.min;
      }
      if (field.max !== undefined) {
        input.max = field.max;
      }
      return input;
    }

    if (field.type === 'boolean') {
      const input = document.createElement('input');
      input.type = 'checkbox';
      input.checked = value === true || value === 'true' || value === 1 || value === '1';
      return input;
    }

    if (field.type === 'select' && Array.isArray(field.options)) {
      const select = document.createElement('select');
      const empty = document.createElement('option');
      empty.value = '';
      empty.textContent = 'Select...';
      select.appendChild(empty);

      field.options.forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value ?? '';
        optionElement.textContent = option.label ?? option.value ?? '';
        optionElement.selected = String(optionElement.value) === String(value);
        select.appendChild(optionElement);
      });

      return select;
    }

    if (field.type === 'repeater') {
      const textarea = document.createElement('textarea');
      textarea.value = value;
      textarea.rows = 8;
      textarea.spellcheck = false;
      return textarea;
    }

    const input = document.createElement('input');
    input.type = field.type === 'image' ? 'url' : 'text';
    input.value = value;
    input.placeholder = field.placeholder ?? '';
    return input;
  };

  const getInputValue = (field, input) => {
    if (field.type === 'boolean') {
      return input.checked;
    }

    if (field.type === 'number') {
      return input.value === '' ? null : Number(input.value);
    }

    if (field.type === 'repeater') {
      try {
        return JSON.parse(input.value || '[]');
      } catch (error) {
        setStatus(`Invalid JSON for ${field.label ?? field.key}`, 'error');
        throw error;
      }
    }

    return input.value;
  };

  const renderFields = (block) => {
    state.fields.clear();
    state.body.innerHTML = '';

    const selected = document.createElement('div');
    selected.className = 'selected';
    selected.innerHTML = `
      <strong>${block.name}</strong>
      <span class="muted">${block.type} · block #${block.id}</span>
    `;
    state.body.appendChild(selected);

    const fields = block.schema?.fields ?? [];

    if (fields.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'empty';
      empty.textContent = 'This block type does not have editable schema fields.';
      state.body.appendChild(empty);
      return;
    }

    const list = document.createElement('div');
    list.className = 'fields';

    fields.forEach((field) => {
      if (!field.key) {
        return;
      }

      const wrapper = document.createElement('div');
      wrapper.className = field.type === 'boolean' ? 'field checkbox' : 'field';

      const label = document.createElement('label');
      label.innerHTML = `<span>${field.label ?? field.key}</span><code>${field.type ?? 'text'}</code>`;

      const input = createInput(field, fieldValue(field, block.data ?? {}, block.rawData ?? {}));
      input.dataset.fieldKey = field.key;

      input.addEventListener('input', () => setStatus('Unsaved changes'));
      input.addEventListener('change', () => queueSave());
      input.addEventListener('blur', () => saveNow());

      if (field.type === 'boolean') {
        wrapper.append(label, input);
      } else {
        wrapper.append(label, input);
      }

      list.appendChild(wrapper);
      state.fields.set(field.key, { field, input });
    });

    state.body.appendChild(list);

    if (block.content?.editUrl) {
      const fullEditor = document.createElement('a');
      fullEditor.href = block.content.editUrl;
      fullEditor.className = 'button';
      fullEditor.textContent = 'Open full editor';
      state.body.appendChild(fullEditor);
    }
  };

  const fetchBlock = async (blockId) => {
    setStatus('Loading block...');
    const response = await fetch(`${BLOCKS_BASE_URL}/${blockId}?locale=${encodeURIComponent(locale())}`, {
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      throw new Error(`Failed to load block ${blockId}`);
    }

    const payload = await response.json();
    return payload.block;
  };

  const selectBlock = async (element) => {
    const blockId = element.dataset.pilotBlockId;

    if (!blockId) {
      return;
    }

    state.selectedElement = element;
    updateHighlight(element);

    try {
      const block = await fetchBlock(blockId);
      state.selectedBlock = block;
      renderFields(block);
      setStatus('Ready');
    } catch (error) {
      setStatus(error.message, 'error');
    }
  };

  const queueSave = () => {
    setStatus('Unsaved changes');
    clearTimeout(state.saveTimer);
    state.saveTimer = setTimeout(saveNow, 550);
  };

  const saveNow = async () => {
    if (!state.selectedBlock || state.fields.size === 0) {
      return;
    }

    clearTimeout(state.saveTimer);
    const fields = {};

    for (const [key, binding] of state.fields.entries()) {
      fields[key] = getInputValue(binding.field, binding.input);
    }

    setStatus('Saving...');

    const response = await fetch(`${BLOCKS_BASE_URL}/${state.selectedBlock.id}`, {
      method: 'PATCH',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
      },
      body: JSON.stringify({ fields, locale: locale() }),
    });

    if (!response.ok) {
      setStatus('Save failed', 'error');
      return;
    }

    const payload = await response.json();
    state.selectedBlock.data = payload.block.data;
    setStatus('Saved', 'success');

    window.setTimeout(() => {
      window.location.reload();
    }, 250);
  };

  const renderEmpty = () => {
    state.body.innerHTML = '';
    const empty = document.createElement('div');
    empty.className = 'empty';
    empty.textContent = 'Click a highlighted CMS block to edit its fields in context. Use Edit mode to browse without intercepting page clicks.';
    state.body.appendChild(empty);
  };

  const toggleActive = () => {
    state.active = !state.active;
    state.activeButton.dataset.active = String(state.active);
    state.activeButton.textContent = state.active ? 'Edit mode' : 'Browse mode';
    updateHighlight(state.active ? state.selectedElement : null);
  };

  const toggleCollapsed = () => {
    state.collapsed = !state.collapsed;
    state.panel.classList.toggle('collapsed', state.collapsed);
  };

  const buildPanel = () => {
    const host = document.createElement('div');
    host.id = ROOT_ID;
    const root = host.attachShadow({ mode: 'open' });

    const style = document.createElement('style');
    style.textContent = css;

    const panel = document.createElement('div');
    panel.className = 'panel';
    panel.innerHTML = `
      <div class="header">
        <div class="title">
          <strong>Pilot editor</strong>
          <span>Front-end CMS editing</span>
        </div>
        <div class="actions">
          <button type="button" data-action="active" data-active="true">Edit mode</button>
          <button type="button" data-action="collapse">Hide</button>
        </div>
      </div>
      <div class="body"></div>
      <div class="footer">
        <span class="status">Ready</span>
        <button type="button" data-action="save">Save</button>
      </div>
    `;

    root.append(style, panel);
    document.body.appendChild(host);

    state.host = host;
    state.root = root;
    state.panel = panel;
    state.body = root.querySelector('.body');
    state.status = root.querySelector('.status');
    state.activeButton = root.querySelector('[data-action="active"]');

    root.querySelector('[data-action="active"]').addEventListener('click', toggleActive);
    root.querySelector('[data-action="collapse"]').addEventListener('click', toggleCollapsed);
    root.querySelector('[data-action="save"]').addEventListener('click', saveNow);

    renderEmpty();
  };

  document.addEventListener('mouseover', (event) => {
    if (isEditorElement(event.target)) {
      return;
    }

    const editable = editableFrom(event.target);

    if (editable) {
      updateHighlight(editable);
    }
  });

  document.addEventListener('click', (event) => {
    if (!state.active || isEditorElement(event.target)) {
      return;
    }

    const editable = editableFrom(event.target);

    if (!editable) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    selectBlock(editable);
  }, true);

  window.addEventListener('resize', () => updateHighlight(state.selectedElement));
  window.addEventListener('scroll', () => updateHighlight(state.selectedElement), true);

  document.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key.toLowerCase() === 'e') {
      event.preventDefault();
      toggleActive();
    }
  });

  ensureHighlight();
  buildPanel();
})();
