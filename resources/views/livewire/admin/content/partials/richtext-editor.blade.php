@props([
    'field',
    'value' => '',
    'fieldKey' => null,
    'repeaterIndex' => null,
    'subFieldKey' => null,
])

@php
    $fieldKey ??= $field['key'] ?? '';
    $placeholder = $field['placeholder'] ?? '';
    $rows = max((int) ($field['rows'] ?? 6), 4);
    $minHeight = max($rows * 28, 132);
    $isRepeaterField = $repeaterIndex !== null && $subFieldKey !== null;
@endphp

<div
    wire:ignore
    class="pilot-richtext rounded-lg border border-slate-200 bg-white shadow-sm transition-all focus-within:border-teal-500 focus-within:ring-1 focus-within:ring-teal-500"
    x-data="pilotRichTextEditor({
        value: @js((string) $value),
        placeholder: @js($placeholder),
        fieldKey: @js($fieldKey),
        repeaterIndex: @js($repeaterIndex),
        subFieldKey: @js($subFieldKey),
        isRepeaterField: @js($isRepeaterField),
    })"
    x-init="init()"
>
    <div class="pilot-richtext-toolbar flex flex-wrap items-center gap-1 border-b border-slate-100 bg-slate-50 p-1.5">
        <button type="button" x-on:click="formatBlock('p')" class="pilot-richtext-button" :class="{ 'is-active': isBlock('p') }" title="Paragraph" aria-label="Paragraph">
            <span class="text-[11px] font-semibold">P</span>
        </button>
        <button type="button" x-on:click="formatBlock('h2')" class="pilot-richtext-button" :class="{ 'is-active': isBlock('h2') }" title="Heading" aria-label="Heading">
            <span class="text-[11px] font-semibold">H2</span>
        </button>
        <button type="button" x-on:click="formatBlock('h3')" class="pilot-richtext-button" :class="{ 'is-active': isBlock('h3') }" title="Subheading" aria-label="Subheading">
            <span class="text-[11px] font-semibold">H3</span>
        </button>

        <span class="mx-1 h-4 w-px bg-slate-200" aria-hidden="true"></span>

        <button type="button" x-on:click="runCommand('bold')" class="pilot-richtext-button" :class="{ 'is-active': active.bold }" title="Bold" aria-label="Bold">
            <i class="ph-bold ph-text-b" aria-hidden="true"></i>
        </button>
        <button type="button" x-on:click="runCommand('italic')" class="pilot-richtext-button" :class="{ 'is-active': active.italic }" title="Italic" aria-label="Italic">
            <i class="ph-bold ph-text-italic" aria-hidden="true"></i>
        </button>
        <button type="button" x-on:click="runCommand('formatBlock', 'blockquote')" class="pilot-richtext-button" :class="{ 'is-active': isBlock('blockquote') }" title="Quote" aria-label="Quote">
            <i class="ph-bold ph-quotes" aria-hidden="true"></i>
        </button>

        <span class="mx-1 h-4 w-px bg-slate-200" aria-hidden="true"></span>

        <button type="button" x-on:click="runCommand('insertUnorderedList')" class="pilot-richtext-button" :class="{ 'is-active': active.ul }" title="Bulleted list" aria-label="Bulleted list">
            <i class="ph-bold ph-list-bullets" aria-hidden="true"></i>
        </button>
        <button type="button" x-on:click="runCommand('insertOrderedList')" class="pilot-richtext-button" :class="{ 'is-active': active.ol }" title="Numbered list" aria-label="Numbered list">
            <i class="ph-bold ph-list-numbers" aria-hidden="true"></i>
        </button>

        <span class="mx-1 h-4 w-px bg-slate-200" aria-hidden="true"></span>

        <button type="button" x-on:click="createLink()" class="pilot-richtext-button" :class="{ 'is-active': active.link }" title="Link" aria-label="Link">
            <i class="ph-bold ph-link" aria-hidden="true"></i>
        </button>
        <button type="button" x-on:click="runCommand('unlink')" class="pilot-richtext-button" title="Remove link" aria-label="Remove link">
            <i class="ph-bold ph-link-break" aria-hidden="true"></i>
        </button>

        <span class="ml-auto"></span>

        <button type="button" x-on:click="toggleSource()" class="pilot-richtext-button" :class="{ 'is-active': sourceMode }" title="HTML" aria-label="HTML">
            <i class="ph-bold ph-code" aria-hidden="true"></i>
        </button>
    </div>

    <div class="relative">
        <div
            x-show="! sourceMode"
            x-ref="editor"
            class="pilot-richtext-surface"
            style="min-height: {{ $minHeight }}px"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            x-bind:data-placeholder="placeholder"
            x-on:input="handleInput()"
            x-on:blur="flush()"
            x-on:keyup="refreshState()"
            x-on:mouseup="refreshState()"
            x-on:paste.prevent="handlePaste($event)"
        ></div>

        <textarea
            x-show="sourceMode"
            x-ref="source"
            x-model="html"
            x-on:input="queueSave()"
            x-on:blur="flush()"
            rows="{{ $rows }}"
            class="pilot-richtext-source"
            spellcheck="false"
        ></textarea>
    </div>
</div>
