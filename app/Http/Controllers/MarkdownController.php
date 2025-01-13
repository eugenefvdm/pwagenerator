<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;

class MarkdownController extends Controller
{
    public function showMarkdown($fileName)
{
    $markdown = File::get(storage_path("markdown/{$fileName}.md"));
    $converter = new CommonMarkConverter([
        'html_input' => 'escape', // Secure HTML rendering
        'allow_unsafe_links' => false,
    ]);
    $html = $converter->convertToHtml($markdown);

    return view('markdown.show', ['content' => $html]);
}
}
