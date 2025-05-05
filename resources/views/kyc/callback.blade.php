<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado KYC</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md text-center">
        @if($status === 'active')
        <h1 class="text-2xl font-semibold text-green-600 mb-4">¡KYC completado con éxito!</h1>
        <p>Tu ID de cliente es <strong>{{ $customerId }}</strong>.</p>
        @elseif($status === 'pending')
        <h1 class="text-2xl font-semibold text-yellow-600 mb-4">KYC pendiente</h1>
        <p>Estamos procesando tu información. Te notificaremos cuando esté listo.</p>
        @else
        <h1 class="text-2xl font-semibold text-red-600 mb-4">KYC rechazado</h1>
        <p>Hubo un problema con tu verificación. Por favor contacta soporte.</p>
        @endif
        <a href="/" class="mt-6 inline-block px-4 py-2 bg-blue-500 text-white rounded">Ir al inicio</a>
    </div>
</body>

</html>