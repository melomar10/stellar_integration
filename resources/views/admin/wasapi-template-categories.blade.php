@extends('layouts.admin')
@section('title', 'Categorías de plantillas - Panel de Administración')
@section('page-title', 'Categorías de plantillas WhatsApp')

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
            <h2 class="card-title">Nueva categoría</h2>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary);font-size:.9rem;margin:0 0 1rem;">
                Cada categoría puede asociarse a una sola plantilla guardada. La asignación es opcional y se configura en
                <a href="{{ route('admin.wasapi.templates.index') }}">Plantillas WhatsApp</a>.
            </p>
            <form method="POST" action="{{ route('admin.wasapi.template-categories.store') }}" style="max-width:520px;">
                @csrf
                <label for="category-name" style="display:block;font-weight:600;margin-bottom:.5rem;">Nombre de la categoría</label>
                <input type="text"
                       id="category-name"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       maxlength="120"
                       placeholder="Ej. Recordatorio de pago"
                       style="width:100%;padding:.65rem .85rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:.75rem;">
                @error('name')
                    <p style="color:#e53e3e;font-size:.85rem;margin:0 0 .75rem;">{{ $message }}</p>
                @enderror
                <button type="submit" class="btn btn-primary"
                        style="padding:.65rem 1.25rem;background:var(--primary-color);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
                    Agregar categoría
                </button>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Categorías registradas</h2>
        </div>
        <div class="card-body">
            @if($categories->isEmpty())
                <p style="color:var(--text-secondary);margin:0;">No hay categorías. Agregue la primera arriba.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-color);text-align:left;">
                                <th style="padding:.75rem .5rem;">Categoría</th>
                                <th style="padding:.75rem .5rem;">Plantilla asignada</th>
                                <th style="padding:.75rem .5rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                <tr style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:.75rem .5rem;font-weight:600;">{{ $category->name }}</td>
                                    <td style="padding:.75rem .5rem;">
                                        @if($category->template)
                                            <code>{{ $category->template->template_id }}</code>
                                        @else
                                            <span style="color:var(--text-secondary);">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td style="padding:.75rem .5rem;">
                                        <form method="POST"
                                              action="{{ route('admin.wasapi.template-categories.destroy', $category) }}"
                                              onsubmit="return confirm('¿Eliminar la categoría «{{ $category->name }}»?');"
                                              style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="padding:.35rem .75rem;border:1px solid #e53e3e;background:#fff;color:#e53e3e;border-radius:6px;cursor:pointer;font-size:.82rem;">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
