(() => {
  if (window.__tweakerExtensionLoaded) {
    return;
  }

  window.__tweakerExtensionLoaded = true;

  const ROOT_ID = 'dialkit-root';
  const HIGHLIGHT_ID = 'dialkit-highlight';
  const STYLE_OVERRIDE_ID = 'tweaker-style-overrides';
  const PSEUDO_OVERRIDE_ID = 'tweaker-pseudo-overrides';

  const PANEL_CSS = `
:host {
  --dialkit-bg: #0f0f14;
  --dialkit-surface: rgba(21, 22, 27, 0.96);
  --dialkit-border: rgba(255, 255, 255, 0.08);
  --dialkit-muted: rgba(255, 255, 255, 0.6);
  --dialkit-text: #f7f7fb;
  --dialkit-accent: #9cc3ff;
  --dialkit-accent-strong: #6ea4ff;
  --dialkit-shadow: 0 24px 60px rgba(5, 6, 12, 0.55);
  --dialkit-card: rgba(255, 255, 255, 0.06);
  all: initial;
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 2147483647;
  color: var(--dialkit-text);
  font-family: "Sora", "Space Grotesk", "Work Sans", sans-serif;
  pointer-events: none;
}

:host * {
  box-sizing: border-box;
}

.dialkit-panel {
  width: 360px;
  max-height: calc(100vh - 48px);
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 18px;
  border-radius: 26px;
  background: linear-gradient(145deg, rgba(32, 33, 40, 0.98), rgba(15, 16, 20, 0.95));
  border: 1px solid var(--dialkit-border);
  box-shadow: var(--dialkit-shadow);
  backdrop-filter: blur(18px);
  pointer-events: auto;
  overflow: auto;
}

.dialkit-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.dialkit-title {
  font-size: 18px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  font-weight: 600;
}

.dialkit-header-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.dialkit-button {
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.08);
  color: var(--dialkit-text);
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  cursor: pointer;
  transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.dialkit-button:hover {
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.16);
  transform: translateY(-1px);
}

.dialkit-button-ghost {
  background: transparent;
  color: var(--dialkit-muted);
}

.dialkit-selected {
  padding: 12px 14px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 13px;
  color: var(--dialkit-muted);
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.dialkit-selected-label {
  color: var(--dialkit-text);
  font-weight: 500;
}

.dialkit-selected-meta {
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
}

.dialkit-controls {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.dialkit-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.dialkit-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: var(--dialkit-muted);
  padding: 6px 4px;
}

.dialkit-section-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.dialkit-control-row {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 16px;
  background: var(--dialkit-card);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.dialkit-control-row[data-variant='stacked'] {
  grid-template-columns: 1fr;
  align-items: start;
  gap: 8px;
}

.dialkit-control-row[data-variant='stacked'] .dialkit-control-label {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.dialkit-control-label {
  font-size: 13px;
  color: var(--dialkit-muted);
}

.dialkit-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: var(--dialkit-text);
  padding: 8px 10px;
  border-radius: 12px;
  font-size: 12px;
}

.dialkit-input-inline {
  width: 140px;
  text-align: right;
}

.dialkit-input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.dialkit-select {
  width: 160px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: var(--dialkit-text);
  padding: 8px 10px;
  border-radius: 12px;
  font-size: 12px;
}

.dialkit-range {
  display: flex;
  align-items: center;
  gap: 10px;
}

.dialkit-range[data-variant='stacked'] {
  width: 100%;
  flex-direction: column;
  align-items: stretch;
  gap: 6px;
}

.dialkit-range input[type='range'] {
  width: 140px;
}

.dialkit-range[data-variant='stacked'] input[type='range'] {
  width: 100%;
}

.dialkit-range-value {
  min-width: 36px;
  text-align: right;
  font-size: 12px;
  color: var(--dialkit-text);
}

.dialkit-color {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dialkit-color input[type='color'] {
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  background: transparent;
}

.dialkit-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  overflow: hidden;
}

.dialkit-toggle button {
  border: none;
  background: transparent;
  color: var(--dialkit-muted);
  padding: 8px 10px;
  font-size: 12px;
  cursor: pointer;
}

.dialkit-toggle button[data-active='true'] {
  background: rgba(255, 255, 255, 0.14);
  color: var(--dialkit-text);
}

.dialkit-shadow,
.dialkit-filter-list,
.dialkit-selector-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.dialkit-shadow-card,
.dialkit-filter-row,
.dialkit-selector-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 10px;
  align-items: center;
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.05);
}

.dialkit-icon-button {
  width: 28px;
  height: 28px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(255, 255, 255, 0.08);
  color: var(--dialkit-text);
  font-weight: 600;
  cursor: pointer;
}

.dialkit-curve {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.dialkit-curve svg {
  width: 100%;
  height: 140px;
  background: rgba(255, 255, 255, 0.04);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.dialkit-curve-grid {
  stroke: rgba(255, 255, 255, 0.12);
  stroke-width: 1;
}

.dialkit-curve-path {
  stroke: var(--dialkit-accent);
  stroke-width: 2.5;
  fill: none;
}

.dialkit-curve-point {
  fill: var(--dialkit-accent-strong);
}

.dialkit-curve-inputs {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.dialkit-curve-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 11px;
  color: var(--dialkit-muted);
}

.dialkit-curve-field input {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: var(--dialkit-text);
  border-radius: 8px;
  padding: 6px;
  font-size: 11px;
}

.dialkit-source-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.dialkit-source-item {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.6);
  padding: 8px 10px;
  border-radius: 12px;
  border: 1px dashed rgba(255, 255, 255, 0.16);
}

.dialkit-source-item strong {
  color: var(--dialkit-text);
  font-weight: 500;
  display: block;
  margin-bottom: 4px;
}

.dialkit-overlay-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 12px;
}

.dialkit-overlay-pill {
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.08);
  font-size: 11px;
}
`;

  let isActive = false;
  let isPicking = true;
  let selectedElement = null;
  let lockedSelectors = [];
  let lockedElements = [];
  let overrides = new Map();
  let hoverElement = null;
  let applyMode = 'inline';
  let overlayEnabled = false;
  let pseudoStates = { hover: false, focus: false, active: false };

  const originalInlineStyles = new Map();
  const originalTextContent = new Map();
  const originalRuleTexts = new Map();

  const state = {
    panel: null,
    panelHost: null,
    highlight: null,
    controlsList: null,
    selectedLabel: null,
    selectedMeta: null,
    selectorInput: null,
    overlayBadge: null,
  };

  const ensureStyleTag = (id) => {
    let style = document.getElementById(id);
    if (!style) {
      style = document.createElement('style');
      style.id = id;
      document.head.appendChild(style);
    }

    return style;
  };

  const formatElementLabel = (element) => {
    if (!element) {
      return 'No element selected';
    }

    const parts = [element.tagName.toLowerCase()];
    if (element.id) {
      parts.push(`#${element.id}`);
    }
    if (element.classList.length > 0) {
      parts.push(`.${[...element.classList].slice(0, 3).join('.')}`);
      if (element.classList.length > 3) {
        parts.push('...');
      }
    }

    return parts.join('');
  };

  const isDialKitElement = (element) => {
    if (!element) {
      return false;
    }

    if (state.panelHost && state.panelHost.contains(element)) {
      return true;
    }

    return Boolean(element.closest?.(`#${ROOT_ID}`));
  };

  const ensureHighlight = () => {
    if (state.highlight) {
      return;
    }

    const highlight = document.createElement('div');
    highlight.id = HIGHLIGHT_ID;
    document.body.appendChild(highlight);
    state.highlight = highlight;
  };

  const updateHighlight = (element) => {
    if (!state.highlight) {
      return;
    }

    if (!element || element === document.documentElement || element === document.body) {
      state.highlight.style.opacity = '0';
      return;
    }

    const rect = element.getBoundingClientRect();
    state.highlight.style.opacity = '1';
    state.highlight.style.top = `${rect.top}px`;
    state.highlight.style.left = `${rect.left}px`;
    state.highlight.style.width = `${rect.width}px`;
    state.highlight.style.height = `${rect.height}px`;
  };

  const setOriginalStyleIfNeeded = (element) => {
    if (!originalInlineStyles.has(element)) {
      originalInlineStyles.set(element, element.getAttribute('style'));
    }
  };

  const setOriginalTextIfNeeded = (element) => {
    if (!originalTextContent.has(element)) {
      originalTextContent.set(element, element.textContent ?? '');
    }
  };

  const getSelectedElements = () => {
    const elements = new Set();

    lockedSelectors.forEach((selector) => {
      try {
        document.querySelectorAll(selector).forEach((node) => elements.add(node));
      } catch (error) {
        return;
      }
    });

    return Array.from(elements);
  };

  const applyOverridesInline = () => {
    lockedElements.forEach((element) => {
      setOriginalStyleIfNeeded(element);
      overrides.forEach((value, property) => {
        if (!value.trim()) {
          element.style.removeProperty(property);
        } else {
          element.style.setProperty(property, value);
        }
      });
    });
  };

  const applyOverridesStylesheet = () => {
    const style = ensureStyleTag(STYLE_OVERRIDE_ID);
    const rules = [];

    lockedSelectors.forEach((selector) => {
      if (!selector) {
        return;
      }
      const declarations = [];
      overrides.forEach((value, property) => {
        if (value.trim()) {
          declarations.push(`  ${property}: ${value};`);
        }
      });
      if (declarations.length > 0) {
        rules.push(`${selector} {\n${declarations.join('\n')}\n}`);
      }
    });

    style.textContent = rules.join('\n\n');
  };

  const applyOverrides = () => {
    if (lockedElements.length === 0 || overrides.size === 0) {
      return;
    }

    if (applyMode === 'stylesheet') {
      applyOverridesStylesheet();
    } else {
      applyOverridesInline();
    }
  };

  const setOverride = (property, value) => {
    overrides.set(property, value);
    applyOverrides();
  };

  const clearOverrides = () => {
    overrides.forEach((value, property) => {
      lockedElements.forEach((element) => {
        element.style.removeProperty(property);
      });
    });
    overrides = new Map();
    const style = document.getElementById(STYLE_OVERRIDE_ID);
    if (style) {
      style.textContent = '';
    }
  };

  const getComputed = () => {
    if (!selectedElement) {
      return null;
    }

    return window.getComputedStyle(selectedElement);
  };

  const readOverrideOrComputed = (property, fallback = '') => {
    if (overrides.has(property)) {
      return overrides.get(property);
    }

    const computed = getComputed();
    return computed ? computed.getPropertyValue(property).trim() : fallback;
  };

  const parseNumber = (value, fallback = 0) => {
    const parsed = Number.parseFloat(value);
    return Number.isNaN(parsed) ? fallback : parsed;
  };

  const splitShadowList = (value) => {
    if (!value || value === 'none') {
      return [];
    }

    return value.split(/,(?![^()]*\))/).map((item) => item.trim()).filter(Boolean);
  };

  const parseShadow = (value) => {
    const fallback = { x: 0, y: 0, blur: 20, spread: 0, color: '#000000', inset: false };
    if (!value || value === 'none') {
      return fallback;
    }

    const inset = value.includes('inset');
    const sanitized = value.replace('inset', '').trim();
    const parts = sanitized.split(/\s+/);
    const color = parts.find((part) => part.startsWith('#') || part.startsWith('rgb')) || '#000000';
    const numeric = parts.filter((part) => part !== color).map((part) => parseNumber(part, 0));

    return {
      x: numeric[0] ?? 0,
      y: numeric[1] ?? 0,
      blur: numeric[2] ?? 20,
      spread: numeric[3] ?? 0,
      color,
      inset,
    };
  };

  const composeShadow = ({ x, y, blur, spread, color, inset }) => {
    const parts = [`${x}px`, `${y}px`, `${blur}px`, `${spread}px`, color];
    if (inset) {
      parts.push('inset');
    }
    return parts.join(' ');
  };

  const getTransformState = () => {
    const value = readOverrideOrComputed('transform', 'none');
    if (!value || value === 'none') {
      return { x: 0, y: 0, scale: 1, rotate: 0 };
    }

    const state = { x: 0, y: 0, scale: 1, rotate: 0 };
    const translateMatch = value.match(/translate\(([^)]+)\)/);
    if (translateMatch) {
      const [x, y] = translateMatch[1].split(',').map((part) => parseNumber(part, 0));
      state.x = x ?? 0;
      state.y = y ?? 0;
    }

    const scaleMatch = value.match(/scale\(([^)]+)\)/);
    if (scaleMatch) {
      state.scale = parseNumber(scaleMatch[1], 1);
    }

    const rotateMatch = value.match(/rotate\(([^)]+)\)/);
    if (rotateMatch) {
      state.rotate = parseNumber(rotateMatch[1], 0);
    }

    return state;
  };

  const setTransformState = (state) => {
    const pieces = [];
    if (state.x !== 0 || state.y !== 0) {
      pieces.push(`translate(${state.x}px, ${state.y}px)`);
    }
    if (state.scale !== 1) {
      pieces.push(`scale(${state.scale})`);
    }
    if (state.rotate !== 0) {
      pieces.push(`rotate(${state.rotate}deg)`);
    }

    const value = pieces.length > 0 ? pieces.join(' ') : 'none';
    setOverride('transform', value);
  };

  const parseFilterList = (value) => {
    if (!value || value === 'none') {
      return [];
    }

    const items = value.match(/\w+\([^)]*\)/g);
    if (!items) {
      return [];
    }

    return items.map((item) => {
      const [name, rest] = item.split('(');
      return { name, value: rest.replace(')', '') };
    });
  };

  const composeFilterList = (items) => {
    if (items.length === 0) {
      return 'none';
    }

    return items.map((item) => `${item.name}(${item.value})`).join(' ');
  };

  const copyOverrides = async () => {
    if (overrides.size === 0) {
      return;
    }

    const lines = [];
    overrides.forEach((value, property) => {
      if (value.trim()) {
        lines.push(`${property}: ${value};`);
      }
    });

    if (lines.length === 0) {
      return;
    }

    try {
      await navigator.clipboard.writeText(lines.join('\n'));
    } catch (error) {
      console.warn('Tweaker: clipboard copy failed', error);
    }
  };

  const copyDiff = async () => {
    if (overrides.size === 0 || lockedSelectors.length === 0) {
      return;
    }

    const blocks = lockedSelectors.map((selector) => {
      const declarations = [];
      overrides.forEach((value, property) => {
        if (value.trim()) {
          declarations.push(`  ${property}: ${value};`);
        }
      });
      return `${selector} {\n${declarations.join('\n')}\n}`;
    });

    try {
      await navigator.clipboard.writeText(blocks.join('\n\n'));
    } catch (error) {
      console.warn('Tweaker: diff copy failed', error);
    }
  };

  const resetPageState = () => {
    originalInlineStyles.forEach((style, element) => {
      if (style === null) {
        element.removeAttribute('style');
      } else {
        element.setAttribute('style', style);
      }
    });

    originalTextContent.forEach((text, element) => {
      element.textContent = text;
    });

    originalRuleTexts.forEach((cssText, rule) => {
      try {
        rule.style.cssText = cssText;
      } catch (error) {
        return;
      }
    });

    originalInlineStyles.clear();
    originalTextContent.clear();
    originalRuleTexts.clear();
    overrides = new Map();

    const style = document.getElementById(STYLE_OVERRIDE_ID);
    if (style) {
      style.textContent = '';
    }

    renderControls();
  };

  const createLabelRow = (label, control, variant = 'inline') => {
    const row = document.createElement('div');
    row.className = 'dialkit-control-row';
    if (variant === 'stacked') {
      row.dataset.variant = 'stacked';
    }

    const text = document.createElement('span');
    text.className = 'dialkit-control-label';
    text.textContent = label;

    row.appendChild(text);
    row.appendChild(control);

    return row;
  };

  const createTextInput = (value, onChange) => {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'dialkit-input dialkit-input-inline';
    input.value = value;
    input.addEventListener('input', () => onChange(input.value));
    return input;
  };

  const createRangeInput = (value, min, max, step, onChange, labelMode = 'inline') => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-range';
    if (labelMode === 'stacked') {
      wrapper.dataset.variant = 'stacked';
    }

    const input = document.createElement('input');
    input.type = 'range';
    input.min = String(min);
    input.max = String(max);
    input.step = String(step);
    input.value = String(value);
    input.addEventListener('input', () => onChange(parseNumber(input.value, 0)));

    const display = document.createElement('span');
    display.className = 'dialkit-range-value';
    display.textContent = String(value);

    input.addEventListener('input', () => {
      display.textContent = input.value;
    });

    wrapper.appendChild(input);
    wrapper.appendChild(display);

    return wrapper;
  };

  const createColorInput = (value, onChange) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-color';

    const text = createTextInput(value, onChange);
    const input = document.createElement('input');
    input.type = 'color';
    input.value = value.startsWith('#') ? value : '#000000';
    input.addEventListener('input', () => {
      text.value = input.value;
      onChange(input.value);
    });

    text.addEventListener('change', () => {
      if (text.value.startsWith('#')) {
        input.value = text.value;
      }
    });

    wrapper.appendChild(text);
    wrapper.appendChild(input);

    return wrapper;
  };

  const createSelect = (value, options, onChange) => {
    const select = document.createElement('select');
    select.className = 'dialkit-select';

    options.forEach((option) => {
      const item = document.createElement('option');
      item.value = option;
      item.textContent = option;
      if (option === value) {
        item.selected = true;
      }
      select.appendChild(item);
    });

    select.addEventListener('change', () => onChange(select.value));

    return select;
  };

  const createToggle = (value, onChange) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-toggle';

    const off = document.createElement('button');
    off.type = 'button';
    off.textContent = 'Off';

    const on = document.createElement('button');
    on.type = 'button';
    on.textContent = 'On';

    const update = () => {
      off.dataset.active = value ? 'false' : 'true';
      on.dataset.active = value ? 'true' : 'false';
    };

    off.addEventListener('click', () => {
      value = false;
      update();
      onChange(false);
    });

    on.addEventListener('click', () => {
      value = true;
      update();
      onChange(true);
    });

    wrapper.appendChild(off);
    wrapper.appendChild(on);
    update();

    return wrapper;
  };

  const createCurveEditor = (value, onChange) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-curve';

    const canvas = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    canvas.setAttribute('viewBox', '0 0 100 100');

    const grid = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    grid.setAttribute('d', 'M0 50 H100 M50 0 V100');
    grid.setAttribute('class', 'dialkit-curve-grid');

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('class', 'dialkit-curve-path');

    const point1 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    const point2 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    point1.setAttribute('r', '4');
    point2.setAttribute('r', '4');
    point1.setAttribute('class', 'dialkit-curve-point');
    point2.setAttribute('class', 'dialkit-curve-point');

    const inputRow = document.createElement('div');
    inputRow.className = 'dialkit-curve-inputs';

    let [x1, y1, x2, y2] = value.split(',').map((part) => parseNumber(part.trim(), 0.25));
    if ([x1, y1, x2, y2].length !== 4) {
      x1 = 0.25;
      y1 = 0.1;
      x2 = 0.25;
      y2 = 1;
    }

    const update = () => {
      const p1x = x1 * 100;
      const p1y = 100 - y1 * 100;
      const p2x = x2 * 100;
      const p2y = 100 - y2 * 100;

      path.setAttribute('d', `M0 100 C ${p1x} ${p1y} ${p2x} ${p2y} 100 0`);
      point1.setAttribute('cx', String(p1x));
      point1.setAttribute('cy', String(p1y));
      point2.setAttribute('cx', String(p2x));
      point2.setAttribute('cy', String(p2y));

      onChange(`cubic-bezier(${x1.toFixed(2)}, ${y1.toFixed(2)}, ${x2.toFixed(2)}, ${y2.toFixed(2)})`);
    };

    const addNumber = (label, valueRef, setter) => {
      const group = document.createElement('div');
      group.className = 'dialkit-curve-field';

      const text = document.createElement('span');
      text.textContent = label;

      const input = document.createElement('input');
      input.type = 'number';
      input.step = '0.01';
      input.min = '0';
      input.max = '1';
      input.value = valueRef.toFixed(2);
      input.addEventListener('input', () => {
        const next = Math.min(1, Math.max(0, parseNumber(input.value, 0)));
        setter(next);
        input.value = next.toFixed(2);
        update();
      });

      group.appendChild(text);
      group.appendChild(input);
      inputRow.appendChild(group);
    };

    addNumber('x1', x1, (next) => { x1 = next; });
    addNumber('y1', y1, (next) => { y1 = next; });
    addNumber('x2', x2, (next) => { x2 = next; });
    addNumber('y2', y2, (next) => { y2 = next; });

    canvas.appendChild(grid);
    canvas.appendChild(path);
    canvas.appendChild(point1);
    canvas.appendChild(point2);

    wrapper.appendChild(canvas);
    wrapper.appendChild(inputRow);
    update();

    return wrapper;
  };

  const createSourcesSection = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-source-list';

    if (!selectedElement) {
      return wrapper;
    }

    const sources = [];
    document.styleSheets.forEach((sheet) => {
      let rules;
      try {
        rules = sheet.cssRules;
      } catch (error) {
        return;
      }

      if (!rules) {
        return;
      }

      Array.from(rules).forEach((rule) => {
        if (rule.type !== 1 || !rule.selectorText) {
          return;
        }

        try {
          if (selectedElement.matches(rule.selectorText)) {
            sources.push({ selector: rule.selectorText, href: sheet.href || 'inline' });
          }
        } catch (error) {
          return;
        }
      });
    });

    if (sources.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'dialkit-source-item';
      empty.textContent = 'No readable stylesheet sources found.';
      wrapper.appendChild(empty);
      return wrapper;
    }

    sources.slice(0, 6).forEach((source) => {
      const item = document.createElement('div');
      item.className = 'dialkit-source-item';

      const selector = document.createElement('strong');
      selector.textContent = source.selector;

      const href = document.createElement('span');
      href.textContent = source.href;

      item.appendChild(selector);
      item.appendChild(href);
      wrapper.appendChild(item);
    });

    return wrapper;
  };

  const createRuleEditor = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-filter-list';

    if (!selectedElement) {
      return wrapper;
    }

    const matches = [];
    document.styleSheets.forEach((sheet) => {
      let rules;
      try {
        rules = sheet.cssRules;
      } catch (error) {
        return;
      }

      if (!rules) {
        return;
      }

      Array.from(rules).forEach((rule) => {
        if (rule.type !== 1 || !rule.selectorText) {
          return;
        }

        try {
          if (selectedElement.matches(rule.selectorText)) {
            matches.push({ rule, href: sheet.href || 'inline' });
          }
        } catch (error) {
          return;
        }
      });
    });

    if (matches.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'dialkit-source-item';
      empty.textContent = 'No editable rules found.';
      wrapper.appendChild(empty);
      return wrapper;
    }

    matches.slice(0, 6).forEach(({ rule, href }) => {
      const row = document.createElement('div');
      row.className = 'dialkit-filter-row';

      const stack = document.createElement('div');
      stack.style.display = 'flex';
      stack.style.flexDirection = 'column';
      stack.style.gap = '6px';

      const title = document.createElement('strong');
      title.textContent = rule.selectorText;
      title.style.fontSize = '11px';
      title.style.color = 'rgba(255,255,255,0.8)';

      const meta = document.createElement('span');
      meta.textContent = href;
      meta.style.fontSize = '10px';
      meta.style.color = 'rgba(255,255,255,0.5)';

      const input = document.createElement('textarea');
      input.className = 'dialkit-input';
      input.rows = 2;
      input.value = rule.style.cssText;

      stack.appendChild(title);
      stack.appendChild(meta);
      stack.appendChild(input);

      const apply = document.createElement('button');
      apply.type = 'button';
      apply.className = 'dialkit-icon-button';
      apply.textContent = '+';
      apply.addEventListener('click', () => {
        if (!originalRuleTexts.has(rule)) {
          originalRuleTexts.set(rule, rule.style.cssText);
        }
        try {
          rule.style.cssText = input.value;
        } catch (error) {
          return;
        }
      });

      row.appendChild(stack);
      row.appendChild(apply);
      wrapper.appendChild(row);
    });

    return wrapper;
  };

  const createSelectorManager = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-selector-list';

    lockedSelectors.forEach((selector) => {
      const row = document.createElement('div');
      row.className = 'dialkit-selector-row';

      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'dialkit-input';
      input.value = selector;
      input.addEventListener('change', () => {
        const next = input.value.trim();
        const index = lockedSelectors.indexOf(selector);
        if (index >= 0 && next) {
          lockedSelectors[index] = next;
          refreshLockedElements();
          renderControls();
        }
      });

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'dialkit-icon-button';
      remove.textContent = '-';
      remove.addEventListener('click', () => {
        lockedSelectors = lockedSelectors.filter((item) => item !== selector);
        refreshLockedElements();
        renderControls();
      });

      row.appendChild(input);
      row.appendChild(remove);
      wrapper.appendChild(row);
    });

    if (lockedSelectors.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'dialkit-source-item';
      empty.textContent = 'No selectors locked yet.';
      wrapper.appendChild(empty);
    }

    const addRow = document.createElement('div');
    addRow.className = 'dialkit-selector-row';

    const addInput = document.createElement('input');
    addInput.type = 'text';
    addInput.className = 'dialkit-input';
    addInput.placeholder = 'Add selector...';

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'dialkit-icon-button';
    addButton.textContent = '+';
    addButton.addEventListener('click', () => {
      const next = addInput.value.trim();
      if (!next) {
        return;
      }
      lockedSelectors.push(next);
      addInput.value = '';
      refreshLockedElements();
      renderControls();
    });

    addRow.appendChild(addInput);
    addRow.appendChild(addButton);
    wrapper.appendChild(addRow);

    return wrapper;
  };

  const createFilterEditor = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-filter-list';

    const filters = parseFilterList(readOverrideOrComputed('filter', ''));

    const update = () => {
      setOverride('filter', composeFilterList(filters));
    };

    filters.forEach((filter, index) => {
      const row = document.createElement('div');
      row.className = 'dialkit-filter-row';

      const select = createSelect(filter.name, ['blur', 'brightness', 'contrast', 'grayscale', 'hue-rotate', 'invert', 'opacity', 'saturate', 'sepia'], (value) => {
        filters[index].name = value;
        update();
      });

      const input = createTextInput(filter.value, (value) => {
        filters[index].value = value;
        update();
      });

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'dialkit-icon-button';
      remove.textContent = '-';
      remove.addEventListener('click', () => {
        filters.splice(index, 1);
        update();
        renderControls();
      });

      const grid = document.createElement('div');
      grid.style.display = 'grid';
      grid.style.gridTemplateColumns = '1fr 1fr';
      grid.style.gap = '8px';
      grid.appendChild(select);
      grid.appendChild(input);

      row.appendChild(grid);
      row.appendChild(remove);
      wrapper.appendChild(row);
    });

    const add = document.createElement('button');
    add.type = 'button';
    add.className = 'dialkit-button dialkit-button-ghost';
    add.textContent = 'Add Filter';
    add.addEventListener('click', () => {
      filters.push({ name: 'blur', value: '4px' });
      update();
      renderControls();
    });

    wrapper.appendChild(add);

    return wrapper;
  };

  const createShadowEditor = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-shadow';

    const shadows = splitShadowList(readOverrideOrComputed('box-shadow', '')).map(parseShadow);

    if (shadows.length === 0) {
      shadows.push({ x: 0, y: 8, blur: 24, spread: 0, color: '#000000', inset: false });
    }

    const update = () => {
      setOverride('box-shadow', shadows.map(composeShadow).join(', '));
    };

    shadows.forEach((shadow, index) => {
      const card = document.createElement('div');
      card.className = 'dialkit-shadow-card';

      const stack = document.createElement('div');
      stack.style.display = 'flex';
      stack.style.flexDirection = 'column';
      stack.style.gap = '8px';

      const row = (label, input) => {
        stack.appendChild(createLabelRow(label, input, 'stacked'));
      };

      row('X', createRangeInput(shadow.x, -60, 60, 1, (value) => {
        shadow.x = value;
        update();
      }, 'stacked'));
      row('Y', createRangeInput(shadow.y, -60, 60, 1, (value) => {
        shadow.y = value;
        update();
      }, 'stacked'));
      row('Blur', createRangeInput(shadow.blur, 0, 80, 1, (value) => {
        shadow.blur = value;
        update();
      }, 'stacked'));
      row('Spread', createRangeInput(shadow.spread, -20, 40, 1, (value) => {
        shadow.spread = value;
        update();
      }, 'stacked'));
      row('Tint', createColorInput(shadow.color, (value) => {
        shadow.color = value;
        update();
      }), 'stacked');

      const insetToggle = createToggle(shadow.inset, (value) => {
        shadow.inset = value;
        update();
      });
      stack.appendChild(createLabelRow('Inset', insetToggle, 'stacked'));

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'dialkit-icon-button';
      remove.textContent = '-';
      remove.addEventListener('click', () => {
        shadows.splice(index, 1);
        update();
        renderControls();
      });

      card.appendChild(stack);
      card.appendChild(remove);
      wrapper.appendChild(card);
    });

    const add = document.createElement('button');
    add.type = 'button';
    add.className = 'dialkit-button dialkit-button-ghost';
    add.textContent = 'Add Shadow';
    add.addEventListener('click', () => {
      shadows.push({ x: 0, y: 8, blur: 24, spread: 0, color: '#000000', inset: false });
      update();
      renderControls();
    });

    wrapper.appendChild(add);
    return wrapper;
  };

  const createVariableEditor = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'dialkit-filter-list';

    const gatherInlineVars = (element) => {
      const vars = [];
      if (!element || !element.style) {
        return vars;
      }
      for (let i = 0; i < element.style.length; i += 1) {
        const prop = element.style.item(i);
        if (prop.startsWith('--')) {
          vars.push({ name: prop, value: element.style.getPropertyValue(prop).trim() });
        }
      }
      return vars;
    };

    const elementVars = gatherInlineVars(selectedElement);
    const rootVars = gatherInlineVars(document.documentElement);

    const renderVarRow = (vars, scopeLabel) => {
      vars.forEach((variable, index) => {
        const row = document.createElement('div');
        row.className = 'dialkit-filter-row';

        const name = createTextInput(variable.name, (value) => {
          vars[index].name = value;
        });
        const value = createTextInput(variable.value, (next) => {
          vars[index].value = next;
          if (scopeLabel === 'Root') {
            document.documentElement.style.setProperty(vars[index].name, next);
          } else if (selectedElement) {
            selectedElement.style.setProperty(vars[index].name, next);
          }
        });

        const grid = document.createElement('div');
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = '1fr 1fr';
        grid.style.gap = '8px';
        grid.appendChild(name);
        grid.appendChild(value);

        const badge = document.createElement('span');
        badge.className = 'dialkit-overlay-pill';
        badge.textContent = scopeLabel;

        row.appendChild(grid);
        row.appendChild(badge);
        wrapper.appendChild(row);
      });
    };

    renderVarRow(elementVars, 'Element');
    renderVarRow(rootVars, 'Root');

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'dialkit-button dialkit-button-ghost';
    addButton.textContent = 'Add Variable';
    addButton.addEventListener('click', () => {
      if (!selectedElement) {
        return;
      }
      selectedElement.style.setProperty('--new-var', '#ffffff');
      renderControls();
    });

    wrapper.appendChild(addButton);

    return wrapper;
  };

  const applyPseudoStateStyles = () => {
    const style = ensureStyleTag(PSEUDO_OVERRIDE_ID);
    const activeStates = Object.entries(pseudoStates)
      .filter(([, enabled]) => enabled)
      .map(([state]) => state);

    if (activeStates.length === 0 || lockedSelectors.length === 0) {
      style.textContent = '';
      lockedElements.forEach((element) => {
        element.removeAttribute('data-tweaker-hover');
        element.removeAttribute('data-tweaker-focus');
        element.removeAttribute('data-tweaker-active');
      });
      return;
    }

    lockedElements.forEach((element) => {
      element.toggleAttribute('data-tweaker-hover', pseudoStates.hover);
      element.toggleAttribute('data-tweaker-focus', pseudoStates.focus);
      element.toggleAttribute('data-tweaker-active', pseudoStates.active);
    });

    const rules = [];
    document.styleSheets.forEach((sheet) => {
      let cssRules;
      try {
        cssRules = sheet.cssRules;
      } catch (error) {
        return;
      }

      if (!cssRules) {
        return;
      }

      Array.from(cssRules).forEach((rule) => {
        if (rule.type !== 1 || !rule.selectorText) {
          return;
        }

        let selector = rule.selectorText;
        activeStates.forEach((state) => {
          selector = selector.replaceAll(`:${state}`, `[data-tweaker-${state}]`);
        });

        if (selector !== rule.selectorText) {
          rules.push(`${selector} { ${rule.style.cssText} }`);
        }
      });
    });

    style.textContent = rules.slice(0, 300).join('\n');
  };

  const renderControls = () => {
    if (!state.controlsList || !selectedElement) {
      return;
    }

    state.controlsList.innerHTML = '';

    const computedDisplay = readOverrideOrComputed('display');
    const isFlex = ['flex', 'inline-flex'].includes(computedDisplay);
    const isGrid = ['grid', 'inline-grid'].includes(computedDisplay);

    const flexControls = [
      {
        label: 'Direction',
        render: () => createSelect(readOverrideOrComputed('flex-direction'), ['row', 'row-reverse', 'column', 'column-reverse'], (value) => setOverride('flex-direction', value)),
      },
      {
        label: 'Justify',
        render: () => createSelect(readOverrideOrComputed('justify-content'), ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], (value) => setOverride('justify-content', value)),
      },
      {
        label: 'Align',
        render: () => createSelect(readOverrideOrComputed('align-items'), ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'], (value) => setOverride('align-items', value)),
      },
      {
        label: 'Wrap',
        render: () => createSelect(readOverrideOrComputed('flex-wrap'), ['nowrap', 'wrap', 'wrap-reverse'], (value) => setOverride('flex-wrap', value)),
      },
      {
        label: 'Gap',
        render: () => createTextInput(readOverrideOrComputed('gap'), (value) => setOverride('gap', value)),
      },
    ];

    const gridControls = [
      {
        label: 'Columns',
        render: () => createTextInput(readOverrideOrComputed('grid-template-columns'), (value) => setOverride('grid-template-columns', value)),
      },
      {
        label: 'Rows',
        render: () => createTextInput(readOverrideOrComputed('grid-template-rows'), (value) => setOverride('grid-template-rows', value)),
      },
      {
        label: 'Auto Flow',
        render: () => createSelect(readOverrideOrComputed('grid-auto-flow'), ['row', 'column', 'row dense', 'column dense'], (value) => setOverride('grid-auto-flow', value)),
      },
      {
        label: 'Justify Items',
        render: () => createSelect(readOverrideOrComputed('justify-items'), ['stretch', 'start', 'center', 'end'], (value) => setOverride('justify-items', value)),
      },
      {
        label: 'Align Items',
        render: () => createSelect(readOverrideOrComputed('align-items'), ['stretch', 'start', 'center', 'end'], (value) => setOverride('align-items', value)),
      },
      {
        label: 'Gap',
        render: () => createTextInput(readOverrideOrComputed('gap'), (value) => setOverride('gap', value)),
      },
    ];

    const sections = [
      {
        title: 'Selection',
        controls: [
          {
            label: 'Selectors',
            variant: 'stacked',
            render: () => createSelectorManager(),
          },
          {
            label: 'Apply Mode',
            render: () => createSelect(applyMode, ['inline', 'stylesheet'], (value) => {
              applyMode = value;
              applyOverrides();
            }),
          },
        ],
      },
      {
        title: 'Layout',
        controls: [
          {
            label: 'Width',
            render: () => createTextInput(readOverrideOrComputed('width'), (value) => setOverride('width', value)),
          },
          {
            label: 'Height',
            render: () => createTextInput(readOverrideOrComputed('height'), (value) => setOverride('height', value)),
          },
          {
            label: 'Padding',
            render: () => createTextInput(readOverrideOrComputed('padding'), (value) => setOverride('padding', value)),
          },
          {
            label: 'Margin',
            render: () => createTextInput(readOverrideOrComputed('margin'), (value) => setOverride('margin', value)),
          },
          {
            label: 'Display',
            render: () => createSelect(readOverrideOrComputed('display'), ['block', 'inline-block', 'flex', 'grid', 'inline', 'none'], (value) => setOverride('display', value)),
          },
          {
            label: 'Position',
            render: () => createSelect(readOverrideOrComputed('position'), ['static', 'relative', 'absolute', 'fixed', 'sticky'], (value) => setOverride('position', value)),
          },
          {
            label: 'Overlay',
            render: () => createToggle(overlayEnabled, (value) => {
              overlayEnabled = value;
              updateLayoutOverlay();
            }),
          },
        ],
      },
      {
        title: 'Flex/Grid',
        controls: isGrid ? gridControls : flexControls,
      },
      {
        title: 'Typography',
        controls: [
          {
            label: 'Font Size',
            render: () => createRangeInput(parseNumber(readOverrideOrComputed('font-size'), 16), 8, 64, 1, (value) => setOverride('font-size', `${value}px`)),
          },
          {
            label: 'Line Height',
            render: () => createRangeInput(parseNumber(readOverrideOrComputed('line-height'), 1.2), 0.8, 3, 0.05, (value) => setOverride('line-height', value.toFixed(2))),
          },
          {
            label: 'Font Weight',
            render: () => createSelect(readOverrideOrComputed('font-weight'), ['300', '400', '500', '600', '700', '800'], (value) => setOverride('font-weight', value)),
          },
          {
            label: 'Text Align',
            render: () => createSelect(readOverrideOrComputed('text-align'), ['left', 'center', 'right', 'justify'], (value) => setOverride('text-align', value)),
          },
        ],
      },
      {
        title: 'Color',
        controls: [
          {
            label: 'Text Color',
            render: () => createColorInput(readOverrideOrComputed('color', '#ffffff'), (value) => setOverride('color', value)),
          },
          {
            label: 'Background',
            render: () => createColorInput(readOverrideOrComputed('background-color', '#000000'), (value) => setOverride('background-color', value)),
          },
          {
            label: 'Border Color',
            render: () => createColorInput(readOverrideOrComputed('border-color', '#000000'), (value) => setOverride('border-color', value)),
          },
        ],
      },
      {
        title: 'Effects',
        controls: [
          {
            label: 'Opacity',
            render: () => createRangeInput(parseNumber(readOverrideOrComputed('opacity'), 1), 0, 1, 0.01, (value) => setOverride('opacity', value.toFixed(2))),
          },
          {
            label: 'Border Radius',
            render: () => createRangeInput(parseNumber(readOverrideOrComputed('border-radius'), 8), 0, 48, 1, (value) => setOverride('border-radius', `${value}px`)),
          },
          {
            label: 'Shadow',
            variant: 'stacked',
            render: () => createShadowEditor(),
          },
          {
            label: 'Filters',
            variant: 'stacked',
            render: () => createFilterEditor(),
          },
        ],
      },
      {
        title: 'Transform',
        controls: [
          {
            label: 'Offset X',
            render: () => {
              const transform = getTransformState();
              return createRangeInput(transform.x, -200, 200, 1, (value) => {
                transform.x = value;
                setTransformState(transform);
              });
            },
          },
          {
            label: 'Offset Y',
            render: () => {
              const transform = getTransformState();
              return createRangeInput(transform.y, -200, 200, 1, (value) => {
                transform.y = value;
                setTransformState(transform);
              });
            },
          },
          {
            label: 'Scale',
            render: () => {
              const transform = getTransformState();
              return createRangeInput(transform.scale, 0.1, 3, 0.01, (value) => {
                transform.scale = value;
                setTransformState(transform);
              });
            },
          },
          {
            label: 'Rotate',
            render: () => {
              const transform = getTransformState();
              return createRangeInput(transform.rotate, -180, 180, 1, (value) => {
                transform.rotate = value;
                setTransformState(transform);
              });
            },
          },
        ],
      },
      {
        title: 'Animation',
        controls: [
          {
            label: 'Duration',
            render: () => createRangeInput(parseNumber(readOverrideOrComputed('transition-duration'), 0.3), 0, 2, 0.01, (value) => setOverride('transition-duration', `${value}s`)),
          },
          {
            label: 'Timing',
            variant: 'stacked',
            render: () => {
              const value = readOverrideOrComputed('transition-timing-function', 'cubic-bezier(0.25, 0.1, 0.25, 1)');
              const raw = value.replace('cubic-bezier(', '').replace(')', '');
              return createCurveEditor(raw, (next) => setOverride('transition-timing-function', next));
            },
          },
        ],
      },
      {
        title: 'Variables',
        controls: [
          {
            label: 'Custom Props',
            variant: 'stacked',
            render: () => createVariableEditor(),
          },
        ],
      },
      {
        title: 'Rules',
        controls: [
          {
            label: 'Edit Rules',
            variant: 'stacked',
            render: () => createRuleEditor(),
          },
        ],
      },
      {
        title: 'Pseudo States',
        controls: [
          {
            label: 'Hover',
            render: () => createToggle(pseudoStates.hover, (value) => {
              pseudoStates.hover = value;
              applyPseudoStateStyles();
            }),
          },
          {
            label: 'Focus',
            render: () => createToggle(pseudoStates.focus, (value) => {
              pseudoStates.focus = value;
              applyPseudoStateStyles();
            }),
          },
          {
            label: 'Active',
            render: () => createToggle(pseudoStates.active, (value) => {
              pseudoStates.active = value;
              applyPseudoStateStyles();
            }),
          },
        ],
      },
      {
        title: 'Sources',
        controls: [
          {
            label: 'Styles',
            variant: 'stacked',
            render: () => createSourcesSection(),
          },
        ],
      },
    ];

    const filteredSections = sections.map((section) => {
      if (section.title === 'Flex/Grid' && !isFlex && !isGrid) {
        return null;
      }
      return section;
    }).filter(Boolean);

    filteredSections.forEach((section) => {
      const block = document.createElement('div');
      block.className = 'dialkit-section';

      const header = document.createElement('div');
      header.className = 'dialkit-section-header';
      header.textContent = section.title;

      const body = document.createElement('div');
      body.className = 'dialkit-section-body';

      section.controls.forEach((control) => {
        const controlElement = control.render();
        body.appendChild(createLabelRow(control.label, controlElement, control.variant));
      });

      block.appendChild(header);
      block.appendChild(body);
      state.controlsList.appendChild(block);
    });
  };

  const updateSelectedLabel = () => {
    if (!state.selectedLabel || !state.selectedMeta) {
      return;
    }

    state.selectedLabel.textContent = formatElementLabel(selectedElement);
    const selectorSummary = lockedSelectors.length > 0 ? lockedSelectors.join(', ') : 'No selector';
    state.selectedMeta.textContent = `Locked: ${selectorSummary}`;
  };

  const buildSelector = (element) => {
    if (!element) {
      return null;
    }

    if (element.id) {
      return `#${CSS.escape(element.id)}`;
    }

    const parts = [];
    let current = element;
    while (current && current !== document.body && parts.length < 4) {
      let selector = current.tagName.toLowerCase();
      if (current.classList.length > 0) {
        selector += `.${[...current.classList].slice(0, 2).map((className) => CSS.escape(className)).join('.')}`;
      }

      const parent = current.parentElement;
      if (parent) {
        const siblings = Array.from(parent.children).filter((child) => child.tagName === current.tagName);
        if (siblings.length > 1) {
          const index = siblings.indexOf(current) + 1;
          selector += `:nth-of-type(${index})`;
        }
      }

      parts.unshift(selector);
      if (parent && parent.id) {
        parts.unshift(`#${CSS.escape(parent.id)}`);
        break;
      }

      current = parent;
    }

    return parts.join(' > ');
  };

  const refreshLockedElements = () => {
    lockedElements = getSelectedElements();
    selectedElement = lockedElements[0] || null;
    updateSelectedLabel();
    applyOverrides();
    applyPseudoStateStyles();
    updateLayoutOverlay();
  };

  const lockSelection = (element, options = { append: false }) => {
    const selector = buildSelector(element);
    if (!selector) {
      return;
    }

    if (options.append) {
      if (!lockedSelectors.includes(selector)) {
        lockedSelectors.push(selector);
      }
    } else {
      lockedSelectors = [selector];
    }

    selectedElement = element;
    overrides = new Map();
    refreshLockedElements();
    renderControls();
    updateHighlight(selectedElement);
  };

  const updateLayoutOverlay = () => {
    let overlay = document.getElementById('tweaker-layout-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'tweaker-layout-overlay';
      overlay.style.position = 'fixed';
      overlay.style.zIndex = '2147483646';
      overlay.style.pointerEvents = 'none';
      overlay.style.fontFamily = 'Sora, sans-serif';
      document.body.appendChild(overlay);
    }

    overlay.innerHTML = '';

    if (!overlayEnabled || !selectedElement) {
      return;
    }

    const rect = selectedElement.getBoundingClientRect();
    const styles = getComputedStyle(selectedElement);
    const marginTop = parseNumber(styles.marginTop, 0);
    const marginRight = parseNumber(styles.marginRight, 0);
    const marginBottom = parseNumber(styles.marginBottom, 0);
    const marginLeft = parseNumber(styles.marginLeft, 0);
    const borderTop = parseNumber(styles.borderTopWidth, 0);
    const borderRight = parseNumber(styles.borderRightWidth, 0);
    const borderBottom = parseNumber(styles.borderBottomWidth, 0);
    const borderLeft = parseNumber(styles.borderLeftWidth, 0);
    const paddingTop = parseNumber(styles.paddingTop, 0);
    const paddingRight = parseNumber(styles.paddingRight, 0);
    const paddingBottom = parseNumber(styles.paddingBottom, 0);
    const paddingLeft = parseNumber(styles.paddingLeft, 0);

    const marginRect = {
      top: rect.top - marginTop,
      left: rect.left - marginLeft,
      width: rect.width + marginLeft + marginRight,
      height: rect.height + marginTop + marginBottom,
    };

    const borderRect = {
      top: rect.top,
      left: rect.left,
      width: rect.width,
      height: rect.height,
    };

    const paddingRect = {
      top: rect.top + borderTop,
      left: rect.left + borderLeft,
      width: rect.width - borderLeft - borderRight,
      height: rect.height - borderTop - borderBottom,
    };

    const contentRect = {
      top: paddingRect.top + paddingTop,
      left: paddingRect.left + paddingLeft,
      width: paddingRect.width - paddingLeft - paddingRight,
      height: paddingRect.height - paddingTop - paddingBottom,
    };

    const drawBox = (box, color) => {
      const element = document.createElement('div');
      element.style.position = 'absolute';
      element.style.top = `${box.top}px`;
      element.style.left = `${box.left}px`;
      element.style.width = `${Math.max(box.width, 0)}px`;
      element.style.height = `${Math.max(box.height, 0)}px`;
      element.style.background = color;
      element.style.border = '1px solid rgba(255,255,255,0.15)';
      return element;
    };
    const countTracks = (value) => {
      if (!value) {
        return 0;
      }
      const repeatMatch = value.match(/repeat\((\d+)/);
      if (repeatMatch) {
        return Number.parseInt(repeatMatch[1], 10);
      }
      return value.split(' ').filter((part) => part.trim() && part !== '/').length;
    };

    const label = document.createElement('div');
    label.style.position = 'absolute';
    label.style.top = `${rect.top - 28}px`;
    label.style.left = `${rect.left}px`;
    label.style.padding = '4px 8px';
    label.style.background = 'rgba(20, 20, 24, 0.85)';
    label.style.border = '1px solid rgba(255, 255, 255, 0.2)';
    label.style.borderRadius = '8px';
    label.style.color = '#fff';
    label.style.fontSize = '11px';
    label.textContent = `${Math.round(rect.width)} x ${Math.round(rect.height)}`;

    const marginBox = drawBox(marginRect, 'rgba(255, 165, 0, 0.2)');
    const borderBox = drawBox(borderRect, 'rgba(255, 215, 0, 0.16)');
    const paddingBox = drawBox(paddingRect, 'rgba(120, 200, 120, 0.2)');
    const contentBox = drawBox(contentRect, 'rgba(100, 160, 255, 0.22)');

    overlay.appendChild(marginBox);
    overlay.appendChild(borderBox);
    overlay.appendChild(paddingBox);
    overlay.appendChild(contentBox);
    overlay.appendChild(label);

    if (styles.display === 'grid') {
      const columns = countTracks(styles.gridTemplateColumns);
      const rows = countTracks(styles.gridTemplateRows);
      const grid = document.createElement('div');
      grid.style.position = 'absolute';
      grid.style.top = `${rect.top}px`;
      grid.style.left = `${rect.left}px`;
      grid.style.width = `${rect.width}px`;
      grid.style.height = `${rect.height}px`;
      grid.style.display = 'grid';
      grid.style.gridTemplateColumns = columns > 0 ? `repeat(${columns}, 1fr)` : '1fr';
      grid.style.gridTemplateRows = rows > 0 ? `repeat(${rows}, 1fr)` : '1fr';
      grid.style.gap = styles.gap || '0px';
      grid.style.border = '1px solid rgba(156, 195, 255, 0.6)';
      grid.style.boxSizing = 'border-box';

      const cellCount = Math.max(columns, 1) * Math.max(rows, 1);
      for (let i = 0; i < cellCount; i += 1) {
        const cell = document.createElement('div');
        cell.style.border = '1px dashed rgba(156, 195, 255, 0.4)';
        cell.style.background = 'rgba(156, 195, 255, 0.04)';
        grid.appendChild(cell);
      }

      overlay.appendChild(grid);
    }
  };

  const createPanel = () => {
    if (state.panel) {
      return;
    }

    const root = document.createElement('div');
    root.id = ROOT_ID;

    const shadow = root.attachShadow({ mode: 'open' });

    const style = document.createElement('style');
    style.textContent = PANEL_CSS;

    const panel = document.createElement('div');
    panel.className = 'dialkit-panel';

    const header = document.createElement('div');
    header.className = 'dialkit-header';

    const title = document.createElement('div');
    title.className = 'dialkit-title';
    title.textContent = 'Tweaker';

    const controls = document.createElement('div');
    controls.className = 'dialkit-header-actions';

    const pickButton = document.createElement('button');
    pickButton.type = 'button';
    pickButton.className = 'dialkit-button dialkit-button-ghost';
    pickButton.textContent = 'Pick';
    pickButton.addEventListener('click', () => {
      isPicking = !isPicking;
      pickButton.textContent = isPicking ? 'Pick' : 'Locked';
      pickButton.dataset.active = isPicking ? 'true' : 'false';
    });

    const resetButton = document.createElement('button');
    resetButton.type = 'button';
    resetButton.className = 'dialkit-button dialkit-button-ghost';
    resetButton.textContent = 'Reset';
    resetButton.addEventListener('click', resetPageState);

    const diffButton = document.createElement('button');
    diffButton.type = 'button';
    diffButton.className = 'dialkit-button dialkit-button-ghost';
    diffButton.textContent = 'Diff';
    diffButton.addEventListener('click', copyDiff);

    const copyButton = document.createElement('button');
    copyButton.type = 'button';
    copyButton.className = 'dialkit-button';
    copyButton.textContent = 'Copy';
    copyButton.addEventListener('click', copyOverrides);

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'dialkit-button';
    closeButton.textContent = 'Close';
    closeButton.addEventListener('click', () => {
      deactivate();
    });

    controls.appendChild(pickButton);
    controls.appendChild(resetButton);
    controls.appendChild(diffButton);
    controls.appendChild(copyButton);
    controls.appendChild(closeButton);
    header.appendChild(title);
    header.appendChild(controls);

    const selected = document.createElement('div');
    selected.className = 'dialkit-selected';

    const selectedLabel = document.createElement('span');
    selectedLabel.className = 'dialkit-selected-label';
    selectedLabel.textContent = 'No element selected';

    const selectedMeta = document.createElement('span');
    selectedMeta.className = 'dialkit-selected-meta';
    selectedMeta.textContent = 'No selector';

    selected.appendChild(selectedLabel);
    selected.appendChild(selectedMeta);

    const controlsList = document.createElement('div');
    controlsList.className = 'dialkit-controls';

    panel.appendChild(header);
    panel.appendChild(selected);
    panel.appendChild(controlsList);

    shadow.appendChild(style);
    shadow.appendChild(panel);
    document.body.appendChild(root);

    state.panel = root;
    state.panelHost = root;
    state.controlsList = controlsList;
    state.selectedLabel = selectedLabel;
    state.selectedMeta = selectedMeta;
  };

  const destroyPanel = () => {
    if (!state.panel) {
      return;
    }

    state.panel.remove();
    state.panel = null;
    state.panelHost = null;
    state.controlsList = null;
    state.selectedLabel = null;
    state.selectedMeta = null;
  };

  const onMouseMove = (event) => {
    if (!isActive || !isPicking) {
      return;
    }

    const target = document.elementFromPoint(event.clientX, event.clientY);
    if (!target || isDialKitElement(target)) {
      hoverElement = null;
      updateHighlight(null);
      return;
    }

    if (hoverElement !== target) {
      hoverElement = target;
      updateHighlight(target);
    }
  };

  const onClick = (event) => {
    if (!isActive || !isPicking) {
      return;
    }

    const target = event.target;
    if (!target || isDialKitElement(target)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    lockSelection(target, { append: event.shiftKey });
  };

  const onDoubleClick = (event) => {
    if (!isActive) {
      return;
    }

    const target = event.target;
    if (!target || isDialKitElement(target)) {
      return;
    }

    setOriginalTextIfNeeded(target);
    target.setAttribute('contenteditable', 'true');
    target.focus();

    const onBlur = () => {
      target.removeAttribute('contenteditable');
      target.removeEventListener('blur', onBlur);
    };

    target.addEventListener('blur', onBlur);
  };

  const onKeyDown = (event) => {
    if (!isActive) {
      return;
    }

    if (event.key === 'Escape') {
      deactivate();
      return;
    }

    if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === 'c') {
      isPicking = !isPicking;
      return;
    }

    if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === 'r') {
      resetPageState();
    }
  };

  const onViewportChange = () => {
    if (!isActive) {
      return;
    }

    if (selectedElement) {
      updateHighlight(selectedElement);
    }

    updateLayoutOverlay();
  };

  const activate = () => {
    if (isActive) {
      return;
    }

    isActive = true;
    isPicking = true;
    createPanel();
    ensureHighlight();
    updateSelectedLabel();

    window.addEventListener('mousemove', onMouseMove, true);
    window.addEventListener('click', onClick, true);
    window.addEventListener('dblclick', onDoubleClick, true);
    window.addEventListener('keydown', onKeyDown, true);
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange, true);
  };

  const deactivate = () => {
    if (!isActive) {
      return;
    }

    isActive = false;
    window.removeEventListener('mousemove', onMouseMove, true);
    window.removeEventListener('click', onClick, true);
    window.removeEventListener('dblclick', onDoubleClick, true);
    window.removeEventListener('keydown', onKeyDown, true);
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange, true);
    updateHighlight(null);
    if (state.highlight) {
      state.highlight.remove();
      state.highlight = null;
    }
    destroyPanel();
    overrides = new Map();
    selectedElement = null;
    lockedSelectors = [];
    lockedElements = [];
    hoverElement = null;
    updateLayoutOverlay();
  };

  chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
    if (message?.type === 'dialkit-toggle') {
      if (isActive) {
        deactivate();
      } else {
        activate();
      }

      sendResponse({ ok: true, active: isActive });
    }
  });
})();
