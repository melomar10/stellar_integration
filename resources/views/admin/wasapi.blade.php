@extends('layouts.admin')
@section('title', 'Wasapi - Panel de Administración')
@section('page-title', 'Wasapi / WhatsApp')

@section('content')
<div class="dashboard-container">
    @if(session('success'))
        <div class="alert alert-success" style="background:#f0fff4;border:1px solid #9ae6b4;color:#1c4532;padding:.85rem 1rem;border-radius:8px;margin-bottom:1rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error" style="background:#fff5f5;border:1px solid #feb2b2;color:#742a2a;padding:.85rem 1rem;border-radius:8px;margin-bottom:1rem;">
            {{ session('error') }}
        </div>
    @endif

    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Credenciales Wasapi</h2>
        </div>
        <div class="card-body">
            @if($usingEnvToken)
                <p style="color:var(--text-secondary);margin-bottom:1rem;font-size:.9rem;">
                    Actualmente se usa el token del archivo <code>.env</code> (WASAPI_API_KEY). Guarde credenciales aquí para administrarlas desde el panel.
                </p>
            @endif
            @if($hasToken && $maskedToken)
                <p style="margin-bottom:1rem;font-size:.9rem;">Token guardado: <code>{{ $maskedToken }}</code></p>
            @endif

            <form method="POST" action="{{ route('admin.wasapi.credentials') }}" style="max-width:640px;">
                @csrf
                <p style="margin-bottom:.75rem;font-weight:600;">Token Bearer</p>
                <input type="password" name="api_token" required autocomplete="off"
                       placeholder="460508|xxxxxxxx..."
                       style="width:100%;padding:.75rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:1rem;">
                @error('api_token')<p style="color:#e53e3e;font-size:.85rem;">{{ $message }}</p>@enderror

                <p style="margin-bottom:.75rem;font-weight:600;">Base URI (opcional)</p>
                <input type="url" name="base_uri" value="{{ old('base_uri', $settings->base_uri) }}"
                       style="width:100%;padding:.75rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:1rem;">

                <button type="submit" class="btn btn-primary" style="padding:.65rem 1.25rem;background:var(--primary-color);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
                    Guardar credenciales
                </button>
            </form>
        </div>
    </div>

    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <h2 class="card-title">Líneas de WhatsApp</h2>
            <form method="POST" action="{{ route('admin.wasapi.sync') }}">
                @csrf
                <button type="submit" class="btn btn-secondary"
                        style="padding:.55rem 1rem;background:#edf2f7;border:1px solid var(--border-color);border-radius:8px;cursor:pointer;font-weight:600;">
                    Consultar líneas en Wasapi
                </button>
            </form>
        </div>
        <div class="card-body">
            @if($lines->isEmpty())
                <p style="color:var(--text-secondary);">No hay líneas sincronizadas. Guarde el token y pulse «Consultar líneas en Wasapi».</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-color);text-align:left;">
                                <th style="padding:.75rem .5rem;">Predeterminada</th>
                                <th style="padding:.75rem .5rem;">Nombre</th>
                                <th style="padding:.75rem .5rem;">Número</th>
                                <th style="padding:.75rem .5rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lines as $line)
                                <tr style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:.75rem .5rem;">
                                        @if($line->is_default)
                                            <span style="background:var(--primary-color);color:#fff;padding:.2rem .55rem;border-radius:99px;font-size:.75rem;font-weight:700;">Default</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td style="padding:.75rem .5rem;font-weight:600;">{{ $line->display_name }}</td>
                                    <td style="padding:.75rem .5rem;">{{ $line->phone_number }}</td>
                                    <td style="padding:.75rem .5rem;">
                                        @if(!$line->is_default)
                                            <form method="POST" action="{{ route('admin.wasapi.default') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="line_id" value="{{ $line->id }}">
                                                <button type="submit" style="padding:.35rem .75rem;border:1px solid var(--primary-color);background:#fff;color:var(--primary-color);border-radius:6px;cursor:pointer;font-size:.82rem;">
                                                    Marcar predeterminada
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p style="margin-top:1rem;font-size:.85rem;color:var(--text-secondary);">
                    Solo una línea puede ser predeterminada. Al enviar mensajes sin <code>from_id</code>, se usa esa línea.
                </p>
            @endif
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Probar envío de mensaje</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.wasapi.test-message') }}" style="max-width:640px;">
                @csrf
                <p style="margin-bottom:.5rem;font-weight:600;">Número destino (wa_id, solo dígitos)</p>
                <input type="text" name="wa_id" required placeholder="18294428902"
                       value="{{ old('wa_id') }}"
                       style="width:100%;padding:.75rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:1rem;">

                <p style="margin-bottom:.5rem;font-weight:600;">Mensaje</p>
                <textarea name="message" required rows="3" placeholder="Esto es una prueba"
                          style="width:100%;padding:.75rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:1rem;">{{ old('message') }}</textarea>

                <p style="margin-bottom:.5rem;font-weight:600;">Línea emisora (from_id, opcional)</p>
                <select name="from_id" style="width:100%;padding:.75rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:1rem;">
                    <option value="">Usar línea predeterminada</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->wasapi_id }}" @selected(old('from_id') == $line->wasapi_id)>
                            {{ $line->display_name }} — {{ $line->phone_number }} (ID {{ $line->wasapi_id }})
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary" style="padding:.65rem 1.25rem;background:var(--primary-color);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
                    Enviar mensaje de prueba
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
