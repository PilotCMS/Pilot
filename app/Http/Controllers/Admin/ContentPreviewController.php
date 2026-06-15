<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Contracts\View\View;
use Pilot\Laravel\Support\ContentRenderer;

class ContentPreviewController extends Controller
{
    public function __invoke(Content $content, ContentRenderer $renderer): View
    {
        $payload = $renderer->fromModel($content);

        return $renderer->pageView($payload, space: $content->space);
    }
}
