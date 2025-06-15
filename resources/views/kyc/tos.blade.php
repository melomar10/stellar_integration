<!DOCTYPE html>
<html>

<head>
    <title>Aceptar Términos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Términos de Servicio</h1>
            
            <div class="mb-6">
                <iframe 
                    src="{{ $tosUrl }}" 
                    class="w-full h-[600px] border border-gray-300 rounded-lg"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>

            <div class="text-center">
                <p class="text-gray-600 mb-4">Por favor, lee y acepta los términos de servicio en el iframe de arriba.</p>
                <p class="text-sm text-gray-500">Una vez que hayas aceptado los términos, serás redirigido automáticamente.</p>
            </div>
        </div>
    </div>
</body>

</html>