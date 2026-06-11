<?php

namespace App\Livewire\Admin\Content;

use App\Models\BlockType;
use App\Models\Content;
use Livewire\Component;

class BlockEditor extends Component
{
    public $block;

    public BlockType $blockType;

    public $data = [];

    public function mount($block, $blockType)
    {
        $this->block = $block;
        $this->blockType = $blockType;
        $this->data = $block['data'] ?? [];
    }

    public function updateField($key, $value)
    {
        $this->data[$key] = $value;
        $this->dispatch('block-updated', $this->block['id'], $key, $value);
    }

    public function addRepeaterItem(string $key): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];
        $newItem = [];
        foreach ($this->blockType->schema['fields'] ?? [] as $field) {
            if (($field['key'] ?? '') === $key && isset($field['fields'])) {
                foreach ($field['fields'] as $sub) {
                    $newItem[$sub['key']] = ($sub['translatable'] ?? false) ? ['en' => ''] : '';
                }
                break;
            }
        }
        $items[] = $newItem;
        $this->data[$key] = $items;
        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function removeRepeaterItem(string $key, int $index): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];
        array_splice($items, $index, 1);
        $this->data[$key] = $items;
        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function updateRepeaterField(string $key, int $index, string $subKey, $value): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];
        if (! isset($items[$index])) {
            $items[$index] = [];
        }
        $items[$index][$subKey] = $value;
        $this->data[$key] = $items;
        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function updateJsonObjectField(string $key, int $index, string $objectKey, $value): void
    {
        $items = $this->data[$key] ?? [];
        $items = is_array($items) ? $items : [];

        if (! isset($items[$index]) || ! is_array($items[$index])) {
            $items[$index] = [];
        }

        $items[$index][$objectKey] = $value;
        $this->data[$key] = $items;
        $this->dispatch('block-updated', $this->block['id'], $key, $items);
    }

    public function render()
    {
        return view('livewire.admin.content.block-editor', [
            'contentChoices' => Content::query()
                ->where('type', 'page')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }
}
