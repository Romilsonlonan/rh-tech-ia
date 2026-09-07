<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class DocsController extends Controller
{
    protected string $docsPath;

    public function __construct()
    {
        $this->docsPath = base_path('docs');
    }

    public function index()
    {
        return view('docs.index', [
            'sections' => $this->getSections()
        ]);
    }

    public function show(Request $request, string $section = 'api', string $page = 'index')
    {
        $filePath = "{$this->docsPath}/{$section}/{$page}.md";

        if (!File::exists($filePath)) {
            abort(404);
        }

        $content = $this->parseMarkdown(File::get($filePath));

        return view('docs.page', [
            'content' => $content,
            'currentSection' => $section,
            'currentPage' => $page,
            'sections' => $this->getSections()
        ]);
    }

    protected function getSections(): array
    {
        $sections = [];

        foreach (File::directories($this->docsPath) as $dir) {
            $name = basename($dir);
            $files = collect(File::files($dir))
                ->map(fn($f) => basename($f, '.md'))
                ->toArray();

            $sections[$name] = $files;
        }

        return $sections;
    }

    protected function parseMarkdown(string $markdown): string
    {
        $markdown = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $markdown);
        $markdown = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $markdown);
        $markdown = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/`([^`]+)`/', '<code>$1</code>', $markdown);
        $markdown = preg_replace('/^- (.+)$/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/(\n\n)/', '</p><p>', $markdown);
        $markdown = "<p>{$markdown}</p>";
        $markdown = preg_replace('/<p><h/', '<h', $markdown);
        $markdown = preg_replace('/<\/h(\d)><\/p>/', '</h$1>', $markdown);

        return $markdown;
    }
}
