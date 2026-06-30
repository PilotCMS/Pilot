@php
    $selectedInternalLink = collect($contentChoices)->first(fn ($contentChoice) => $this->relativeContentUrl($contentChoice) === $value);
@endphp

<div class="relative mt-2">
    <select
        @if(isset($repeaterIndex) && isset($subFieldKey))
            wire:change="updateRepeaterField(@js($fieldKey), {{ $repeaterIndex }}, @js($subFieldKey), $event.target.value)"
        @else
            wire:change="updateField(@js($fieldKey), $event.target.value)"
        @endif
        class="w-full appearance-none rounded-lg border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs text-slate-600 shadow-sm outline-none transition-all hover:bg-white focus:border-teal-500 focus:bg-white focus:ring-1 focus:ring-teal-500"
    >
        <option value="">{{ $selectedInternalLink ? 'Linked to '.$selectedInternalLink->name : 'Internal page...' }}</option>
        @foreach($contentChoices as $contentChoice)
            @php $relativeUrl = $this->relativeContentUrl($contentChoice); @endphp
            <option value="{{ $relativeUrl }}" {{ $value === $relativeUrl ? 'selected' : '' }}>
                {{ $contentChoice->name }} {{ $relativeUrl }}{{ $contentChoice->status !== 'published' ? ' ('.$contentChoice->status.')' : '' }}
            </option>
        @endforeach
    </select>
    <i class="ph ph-link absolute left-2.5 top-2.5 text-slate-400"></i>
    <i class="ph ph-caret-down pointer-events-none absolute right-2.5 top-2.5 text-slate-400"></i>
</div>
