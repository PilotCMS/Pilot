<?php

namespace App\Livewire\Admin\Blocks;

use App\Models\BlockType;
use Livewire\Component;

class Edit extends Component
{
    public BlockType $blockType;

    public $name = '';

    public $icon = '';

    public $isGlobal = false;

    public $schema = ['fields' => []];

    public $selectedFieldIndex = null;

    public function mount(BlockType $blockType)
    {
        $this->blockType = $blockType;
        $this->name = $blockType->name;
        $this->icon = $blockType->icon;
        $this->isGlobal = $blockType->is_global;
        $this->schema = $blockType->schema;
        $this->normalizeSchema();
        if (! empty($this->schema['fields'])) {
            $this->selectedFieldIndex = 0;
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'isGlobal' => 'boolean',
            'schema' => 'required|array',
        ];
    }

    public function addField()
    {
        $this->addFieldOfType('text');
    }

    public function addFieldOfType(string $type)
    {
        $this->schema['fields'][] = $this->defaultFieldForType($type);
        $this->selectedFieldIndex = count($this->schema['fields']) - 1;
    }

    public function removeField($index)
    {
        unset($this->schema['fields'][$index]);
        $this->schema['fields'] = array_values($this->schema['fields']);
        if ($this->selectedFieldIndex === $index) {
            $this->selectedFieldIndex = count($this->schema['fields']) ? 0 : null;
        }
    }

    public function moveFieldUp($index)
    {
        if ($index <= 0) {
            return;
        }

        $fields = $this->schema['fields'];
        [$fields[$index - 1], $fields[$index]] = [$fields[$index], $fields[$index - 1]];
        $this->schema['fields'] = array_values($fields);
        $this->selectedFieldIndex = $index - 1;
    }

    public function moveFieldDown($index)
    {
        if ($index >= count($this->schema['fields']) - 1) {
            return;
        }

        $fields = $this->schema['fields'];
        [$fields[$index + 1], $fields[$index]] = [$fields[$index], $fields[$index + 1]];
        $this->schema['fields'] = array_values($fields);
        $this->selectedFieldIndex = $index + 1;
    }

    public function selectField($index)
    {
        $this->selectedFieldIndex = $index;
    }

    public function addOption($fieldIndex)
    {
        $this->schema['fields'][$fieldIndex]['options'][] = ['value' => '', 'label' => ''];
    }

    public function removeOption($fieldIndex, $optionIndex)
    {
        unset($this->schema['fields'][$fieldIndex]['options'][$optionIndex]);
        $this->schema['fields'][$fieldIndex]['options'] = array_values($this->schema['fields'][$fieldIndex]['options']);
    }

    protected function normalizeSchema(): void
    {
        $fields = $this->schema['fields'] ?? [];
        foreach ($fields as $index => $field) {
            $type = $field['type'] ?? 'text';
            $fields[$index] = array_merge($this->defaultFieldForType($type), $field);
            if ($type !== 'select') {
                $fields[$index]['options'] = [];
            } elseif (empty($fields[$index]['options'])) {
                $fields[$index]['options'] = [['value' => '', 'label' => '']];
            }
        }
        $this->schema['fields'] = array_values($fields);
    }

    protected function defaultFieldForType(string $type): array
    {
        return [
            'type' => $type,
            'key' => '',
            'label' => '',
            'translatable' => false,
            'required' => false,
            'default' => $type === 'boolean' ? false : '',
            'placeholder' => '',
            'help' => '',
            'min' => null,
            'max' => null,
            'rows' => $type === 'textarea' ? 4 : 3,
            'options' => $type === 'select' ? [['value' => '', 'label' => '']] : [],
            'reference_type' => $type === 'reference' ? 'content' : null,
        ];
    }

    public function save()
    {
        $this->validate();

        $this->blockType->update([
            'name' => $this->name,
            'icon' => $this->icon,
            'is_global' => $this->isGlobal,
            'schema' => $this->schema,
        ]);

        return $this->redirect(route('admin.blocks.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.blocks.edit')
            ->layout('layouts.admin');
    }
}
