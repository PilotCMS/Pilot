<div class="space-y-7">
    @foreach($blockType->schema['fields'] ?? [] as $field)
        @php
            $rawFieldValue = $data[$field['key']] ?? '';
            $isObjectList = is_array($rawFieldValue) && array_is_list($rawFieldValue) && ! empty($rawFieldValue) && collect($rawFieldValue)->every(fn ($item) => is_array($item));
            $objectKeys = $isObjectList
                ? collect($rawFieldValue)->flatMap(fn ($item) => array_keys($item))->unique()->values()
                : collect();
            $fieldValue = is_array($rawFieldValue) ? ($rawFieldValue['en'] ?? reset($rawFieldValue) ?: '') : $rawFieldValue;
            $typeLabel = $field['type'] ?? 'text';
        @endphp
        <div class="group">
            <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">{{ $field['label'] }}</label>
                <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">{{ $typeLabel }}</span>
            </div>

            @if($isObjectList)
                <div class="space-y-3">
                    @foreach($rawFieldValue as $idx => $item)
                        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                            <div class="mb-3 text-[10px] font-bold uppercase tracking-wide text-slate-400">Item {{ $idx + 1 }}</div>
                            <div class="space-y-3">
                                @foreach($objectKeys as $objectKey)
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $objectKey }}</label>
                                        <textarea rows="{{ $objectKey === 'body' ? 3 : 1 }}"
                                            wire:change="updateJsonObjectField(@js($field['key']), {{ $idx }}, @js($objectKey), $event.target.value)"
                                            class="w-full min-h-9 rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 shadow-sm outline-none transition-all focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                        >{{ $item[$objectKey] ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($field['type'] === 'text')
                <input type="text"
                    value="{{ $fieldValue }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm transition-all"
                />
            @elseif($field['type'] === 'textarea')
                <textarea rows="{{ $field['rows'] ?? 4 }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full min-h-[80px] p-3 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none resize-none shadow-sm transition-all"
                >{{ $fieldValue }}</textarea>
            @elseif($field['type'] === 'richtext')
                <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm bg-white focus-within:ring-1 focus-within:ring-teal-500 focus-within:border-teal-500 transition-all">
                    <div class="flex items-center gap-1 p-1.5 border-b border-slate-100 bg-slate-50">
                        <button type="button" class="p-1 text-slate-500 hover:bg-slate-200 rounded"><i class="ph-bold ph-text-b"></i></button>
                        <button type="button" class="p-1 text-slate-500 hover:bg-slate-200 rounded"><i class="ph-bold ph-text-italic"></i></button>
                        <div class="w-px h-4 bg-slate-300 mx-1"></div>
                        <button type="button" class="p-1 text-slate-500 hover:bg-slate-200 rounded"><i class="ph-bold ph-link"></i></button>
                    </div>
                    <textarea rows="{{ $field['rows'] ?? 6 }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                        class="w-full min-h-[100px] p-3 text-sm text-slate-600 outline-none resize-none"
                        spellcheck="false"
                    >{{ $fieldValue }}</textarea>
                </div>
            @elseif($field['type'] === 'number')
                <input type="number"
                    value="{{ $fieldValue !== '' ? $fieldValue : ($field['default'] ?? 0) }}"
                    min="{{ $field['min'] ?? '' }}"
                    max="{{ $field['max'] ?? '' }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm"
                />
            @elseif($field['type'] === 'boolean')
                @php $rawVal = $data[$field['key']] ?? false; $boolChecked = is_array($rawVal) ? !empty($rawVal) : (bool) $rawVal; @endphp
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox"
                        wire:change="updateField('{{ $field['key'] }}', $event.target.checked)"
                        {{ $boolChecked ? 'checked' : '' }}
                        class="rounded border-slate-200 text-teal-500 focus:ring-teal-500"
                    />
                    <span class="text-sm text-slate-600">Enabled</span>
                </label>
            @elseif($field['type'] === 'image')
                @php
                    $focalX = $data[$field['key'].'_focal_x'] ?? 50;
                    $focalY = $data[$field['key'].'_focal_y'] ?? 50;
                @endphp
                <div class="border-2 border-dashed border-slate-200 rounded-lg p-6 flex flex-col items-center justify-center text-center hover:bg-slate-50 hover:border-teal-400 cursor-pointer transition-all group/upload"
                     wire:click="$dispatch('open-asset-picker', { fieldKey: '{{ $field['key'] }}' })">
                    @if($fieldValue)
                        <div class="mb-2 rounded-lg overflow-hidden max-h-24 bg-slate-100">
                            <img src="{{ $fieldValue }}" alt="" class="max-h-24 object-cover" style="object-position: {{ $focalX }}% {{ $focalY }}%;" />
                        </div>
                        <span class="text-xs font-medium text-slate-600 truncate max-w-full">{{ $fieldValue }}</span>
                    @else
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2 group-hover/upload:bg-white group-hover/upload:text-teal-500 shadow-sm transition-colors">
                            <i class="ph ph-upload-simple text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-600">Drop image here</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">or click to browse</span>
                    @endif
                </div>
            @elseif($field['type'] === 'repeater')
                @php $repeaterItems = $data[$field['key']] ?? []; $repeaterItems = is_array($repeaterItems) ? $repeaterItems : []; @endphp
                <div class="space-y-2">
                    @foreach($repeaterItems as $idx => $item)
                        @php
                            $firstSub = $field['fields'][0] ?? null;
                            $subVal = $firstSub ? ($item[$firstSub['key']] ?? '') : '';
                            $subVal = is_array($subVal) ? ($subVal['en'] ?? reset($subVal) ?: '') : $subVal;
                            $itemLabel = $item['label'] ?? $item['name'] ?? null;
                            $itemLabel = is_array($itemLabel) ? ($itemLabel['en'] ?? reset($itemLabel) ?: '') : $itemLabel;
                            $displayTitle = $itemLabel ?: ($field['label'] . ' ' . ($idx + 1));
                        @endphp
                        <div class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-lg shadow-sm hover:border-teal-300 transition-colors cursor-pointer group/item relative overflow-hidden">
                            @if($idx === 0)<div class="absolute left-0 top-0 bottom-0 w-1 bg-teal-500"></div>@endif
                            <i class="ph ph-dots-six-vertical text-slate-300 cursor-move shrink-0" aria-hidden="true"></i>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-700">{{ $displayTitle }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ $subVal ?: 'Empty' }}</div>
                            </div>
                            <i class="ph ph-caret-right text-slate-400 group-hover/item:text-teal-500 shrink-0"></i>
                            <button type="button" wire:click="removeRepeaterItem('{{ $field['key'] }}', {{ $idx }})" class="w-7 h-7 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400 hover:text-red-500 shrink-0" title="Remove"><i class="ph ph-trash"></i></button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addRepeaterItem('{{ $field['key'] }}')" class="text-[10px] text-teal-600 font-bold hover:underline">
                        {{ in_array(strtolower($field['key'] ?? ''), ['buttons', 'button']) ? '+ Add Button' : '+ Add item' }}
                    </button>
                </div>
            @elseif($field['type'] === 'select')
                <div class="relative">
                <select wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm appearance-none cursor-pointer"
                >
                    <option value="">Select...</option>
                    @if(!empty($field['options']))
                        @foreach($field['options'] as $option)
                            <option value="{{ $option['value'] ?? '' }}" {{ $fieldValue === ($option['value'] ?? '') ? 'selected' : '' }}>{{ $option['label'] ?? $option['value'] ?? '' }}</option>
                        @endforeach
                    @elseif(isset($field['datasource']))
                        @php $datasource = \App\Models\Datasource::where('slug', $field['datasource'])->first(); $entries = $datasource ? $datasource->entries : collect(); @endphp
                        @foreach($entries as $entry)
                            <option value="{{ $entry->key }}" {{ $fieldValue === $entry->key ? 'selected' : '' }}>{{ $entry->value['en'] ?? $entry->key }}</option>
                        @endforeach
                    @endif
                </select>
                <i class="ph ph-caret-down absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                </div>
            @elseif($field['type'] === 'reference')
                <div class="relative">
                    <select wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                        class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm appearance-none cursor-pointer"
                    >
                        <option value="">Select content...</option>
                        @foreach($contentChoices as $contentChoice)
                            <option value="{{ $contentChoice->id }}" {{ (string) $fieldValue === (string) $contentChoice->id ? 'selected' : '' }}>
                                {{ $contentChoice->name }} /{{ $contentChoice->slug }}
                            </option>
                        @endforeach
                    </select>
                    <i class="ph ph-caret-down absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                </div>
            @else
                <input type="text"
                    value="{{ $fieldValue }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
                    class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm"
                />
            @endif

            @if(!empty($field['help']))
                <p class="mt-1.5 text-[10px] text-slate-400">{{ $field['help'] }}</p>
            @endif
        </div>
    @endforeach
</div>
