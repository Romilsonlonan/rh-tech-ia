<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RH Tech IA - Documentação</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="text-xl font-bold bg-gradient-to-r from-sky-600 to-purple-600 bg-clip-text text-transparent">
                ⚡ RH Tech IA
            </a>
            <a href="/docs" class="text-sm text-gray-500 hover:text-sky-600">← Voltar</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8 flex gap-8">
        <aside class="w-64 flex-shrink-0">
            <h3 class="font-semibold text-gray-900 mb-4">{{ ucfirst($currentSection) }}</h3>
            <ul class="space-y-1">
                @foreach($sections[$currentSection] ?? [] as $page)
                    <li>
                        <a href="/docs/{{ $currentSection }}/{{ $page }}" 
                           class="block px-3 py-2 rounded-lg text-sm {{ $page === $currentPage ? 'bg-sky-100 text-sky-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                            {{ ucfirst($page) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <main class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <article class="prose max-w-none">
                {!! $content !!}
            </article>
        </main>
    </div>
</body>
</html>
