<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Contracts\View\View;

class ContentPreviewController extends Controller
{
    public function __invoke(Content $content): View
    {
        $content->load([
            'blocks' => fn ($query) => $query->whereNull('parent_block_id')->orderBy('position'),
            'blocks.children',
        ]);

        return view('admin.content.preview', [
            'content' => $content,
            'blocks' => $content->blocks,
        ]);
    }
}
