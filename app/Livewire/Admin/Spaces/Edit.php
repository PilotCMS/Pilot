<?php

namespace App\Livewire\Admin\Spaces;

use App\Models\Space;
use Illuminate\Support\Str;
use Livewire\Component;

class Edit extends Component
{
    public Space $space;

    public $name = '';

    public $slug = '';

    public function mount(Space $space)
    {
        $this->space = $space;
        $this->name = $space->name;
        $this->slug = $space->slug;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:spaces,slug,'.$this->space->id,
        ];
    }

    public function updatedName($value)
    {
        if ($this->slug === Str::slug($this->space->name)) {
            $this->slug = Str::slug($value);
        }
    }

    public function save()
    {
        $this->validate();

        $this->space->update([
            'name' => $this->name,
            'slug' => $this->slug,
        ]);

        return $this->redirect(route('admin.spaces.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.spaces.edit')
            ->layout('layouts.admin');
    }
}
