<?php

namespace App\Livewire\Admin\Content;

use App\Models\Content;
use App\Support\Cms\ContentSyncFingerprint;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContentSyncPoller extends Component
{
    public int $contentId;

    public ?string $syncKey = null;

    public function mount(int $contentId): void
    {
        $this->contentId = $contentId;
        $content = Content::query()->find($contentId);
        $this->syncKey = $content ? ContentSyncFingerprint::make($content) : null;
    }

    public function poll(): void
    {
        $content = Content::query()->find($this->contentId);

        if (! $content) {
            return;
        }

        $syncKey = ContentSyncFingerprint::make($content);

        if ($syncKey === $this->syncKey) {
            return;
        }

        $this->syncKey = $syncKey;
        $this->dispatch('content-external-change-detected');
    }

    public function render(): View
    {
        return view('livewire.admin.content.content-sync-poller');
    }
}
