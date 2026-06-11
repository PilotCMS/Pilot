<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('Appearance Settings')]
class extends Component {
    //
}; ?>

<section class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ __('Account') }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ __('Manage your Pilot CMS account settings') }}</p>
        </div>
    </header>

    <main class="flex-1 min-h-0 overflow-y-auto">
        <div class="w-full px-6 sm:px-8 py-8">
            <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </x-pages::settings.layout>
        </div>
    </main>
</section>
