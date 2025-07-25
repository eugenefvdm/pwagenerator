<!DOCTYPE html>
<html>
<head>
    <title>PWA Asset Generator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/css/app.css')
</head>
<body class="p-8">
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">PWA Helpers for Laravel</h1>
    This is a set of helpers for developing PWA applications using Laravel and Filament.<br>
    <br>
    There is example code, and then there is a tool for generating icons and splash screens.<br>
    <br>
    The icon and splash screens may also be generated using Icon Genie:<br>
    <a href="https://quasar.dev/icongenie/installation" class="hover:underline">https://quasar.dev/icongenie/installation</a>
    <br>
    <br>
    

    <h2 class="text-xl font-bold mb-4"><a href='/markdown/example' class="hover:underline">Example Laravel and Filament Code
    </a></h2>

    <h2 class="text-xl font-bold mb-4">Icons and Splash Screens Generator</h1>

    <div class="mb-4">
        <label for="backgroundInput" class="block text-sm font-medium text-gray-700 mb-2">Background Color</label>
        <input type="text" id="backgroundInput" class="w-full p-2 border rounded" placeholder="Enter background color (e.g., #ffffff, transparent, red)" value="transparent">
    </div>

    <textarea id="svgInput" class="w-full h-32 p-2 mb-4 border rounded" placeholder="Paste your SVG code here..."></textarea>

    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-medium mb-2">Icons</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($iconSizes as $size)
                    <button onclick="generate('icon', {{ $size }})" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        {{ $size }}x{{ $size }}
                    </button>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="text-lg font-medium mb-2">Splash Screens</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($splashSizes as $size)
                    <button onclick="generate('splash', {{ $size['width'] }}, {{ $size['height'] }})" class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        {{ $size['width'] }}x{{ $size['height'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    function generate(type, width, height = width) {
        const svg = document.getElementById('svgInput').value;
        const background = document.getElementById('backgroundInput').value;
        if (!svg) return;

        fetch('/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ svg, type, width, height, background })
        })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = type === 'icon' ?
                    `icon-${width}x${width}.png` :
                    `splash-${width}x${height}.png`;
                a.click();
            });
    }
</script>
</body>
</html>
