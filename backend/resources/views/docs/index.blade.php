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
            <span class="text-sm text-gray-500">Documentação</span>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8 flex gap-8">
        <aside class="w-64 flex-shrink-0">
            <h3 class="font-semibold text-gray-900 mb-4">Seções</h3>
            <ul class="space-y-2">
                @foreach($sections as $section => $pages)
                    <li>
                        <a href="/docs/{{ $section }}" 
                           class="block px-3 py-2 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                            {{ ucfirst($section) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <main class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Bem-vindo à Documentação</h1>
            <p class="text-gray-600 mb-4">Selecione uma seção no menu lateral para acessar a documentação.</p>
            
            <div class="grid grid-cols-2 gap-4 mt-8">
                @foreach($sections as $section => $pages)
                    <a href="/docs/{{ $section }}" class="p-4 border border-gray-200 rounded-lg hover:border-sky-500 transition-colors">
                        <h4 class="font-semibold text-gray-900">{{ ucfirst($section) }}</h4>
                        <p class="text-sm text-gray-500 mt-1">{{ count($pages) }} página(s)</p>
                    </a>
                @endforeach
            </div>
        </main>
    </div>
</body>
</html>
