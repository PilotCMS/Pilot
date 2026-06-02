<?php

namespace App\Livewire\Admin\Datasources;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.datasources.index')
            ->layout('layouts.admin');
    }
}
