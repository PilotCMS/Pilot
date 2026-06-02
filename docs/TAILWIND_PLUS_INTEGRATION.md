# Tailwind Plus Elements Integration

Pilot uses [Tailwind Plus Elements](https://tailwindcss.com/plus) where it makes sense. Elements is a JavaScript component library that powers interactive UI blocks (tabs, dialogs, dropdowns, etc.) with custom HTML elements.

## Current Integration

### 1. Editor Right Panel Tabs
- **Component:** `<el-tab-group>`, `<el-tab-list>`, `<el-tab-panels>`
- **Location:** Content editor right sidebar (Content, Design, SEO, Settings, History)
- **Sync:** Tab selection synced with Livewire `rightPanelTab` via `wire:click` on each tab button
- **Styling:** Uses `selected-index` attribute for active tab; custom `data-[selected]` styles for visual indication

### 2. Block Library Modal
- **Component:** `<el-dialog>`, `<el-dialog-backdrop>`, `<el-dialog-panel>`
- **Location:** "Add Block" modal in the content editor
- **Sync:** Alpine `x-effect` syncs Livewire `blockLibraryOpen` with native `<dialog>` showModal/close
- **Close:** Cancel button and dialog backdrop/Escape close the modal and update Livewire state

## Other Opportunities

Components you could swap next (requires copying HTML from your [Tailwind Plus dashboard](https://tailwindcss.com/plus/ui-blocks/documentation)):

| Current | Tailwind Plus | Notes |
|---------|---------------|-------|
| Asset picker modal | `el-dialog` | AssetPickerModal Livewire component uses flux:modal |
| Locale select dropdown | `el-select` | Header dropdown |
| Status select (draft/published) | `el-select` | Settings tab |
| Publish dropdown (future schedule) | `el-dropdown` | Header publish button |
| Admin sidebar nav | Sidebar Navigation | Application UI category |
| Command palette (future) | `el-command-palette` | Global search/navigation |

## How to Add More Components

1. Go to [Tailwind Plus](https://tailwindcss.com/plus/ui-blocks/documentation)
2. Find the component (e.g. Application UI → Select Menus)
3. Copy the **vanilla HTML** example
4. Adapt the markup for Blade (replace static content with `@foreach`, `{{ }}`)
5. Wire up Livewire/Alpine for state sync where needed
6. Adjust Tailwind classes for Pilot's design tokens (zinc, primary, dark mode)

## Elements Reference

- **Tabs:** `el-tab-group`, `el-tab-list`, `el-tab-panels`
- **Dialog:** `el-dialog`, `el-dialog-backdrop`, `el-dialog-panel`, native `<dialog>`
- **Dropdown:** `el-dropdown`, `el-menu`
- **Select:** `el-select`, `el-options`, `el-option`, `el-selectedcontent`
- **Invoker Commands:** `command="show-modal"` + `commandfor="dialog-id"` for declarative triggers
