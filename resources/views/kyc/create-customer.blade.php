<!DOCTYPE html>
<html>

<head>
    <title>Crear Cliente</title>
</head>

<body class="p-8">
    <h1>Completa tu registro</h1>
    <form action="{{ route('bridge.customers.create') }}" method="POST">
        @csrf

        <input type="text" name="signed_agreement_id"
            value="{{ session('signed_agreement_id') }}">

        <button type="submit"
            class="px-4 py-2 bg-green-600 text-white rounded">
            Crear Cliente
        </button>
    </form>
</body>

</html>