<?php

namespace App\Livewire\Admin;

use App\Models\Activity;
use App\Models\Asset;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $space = Space::first();

        return view('livewire.admin.dashboard', [
            'space' => $space,
            'pagesCount' => Content::where('type', 'page')->count(),
            'assetsCount' => Asset::count(),
            'usersCount' => User::count(),
            'draftsCount' => Content::where('status', 'draft')->count(),
            'recentActivities' => Activity::with(['user', 'subject'])->latest()->take(20)->get(),
            'recentPages' => Content::where('type', 'page')
                ->where('updated_by', auth()->id())
                ->latest('updated_at')
                ->take(6)
                ->get(),
        ])->layout('layouts.admin');
    }
}
