@extends('layouts.admin')
@section('title', 'Plantillas WhatsApp - Panel de Administración')
@section('page-title', 'Plantillas WhatsApp')

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

    {{-- Plantillas guardadas --}}
    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <h2 class="card-title">Plantillas guardadas</h2>
            <a href="{{ route('admin.wasapi.template-categories.index') }}"
               style="padding:.45rem .9rem;border:1px solid var(--border-color);border-radius:8px;text-decoration:none;font-size:.85rem;font-weight:600;color:var(--text);">
                Administrar categorías
            </a>
        </div>
        <div class="card-body">
            @if($savedTemplates->isEmpty())
                <p style="color:var(--text-secondary);margin:0;">
                    Aún no hay plantillas guardadas. Consulte las plantillas en Wasapi y seleccione las que desee utilizar.
                </p>
            @else
                <input type="search"
                       id="saved-templates-search"
                       placeholder="Buscar por nombre de plantilla..."
                       autocomplete="off"
                       style="width:100%;max-width:420px;padding:.65rem .85rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:1rem;font-size:.92rem;">

                <p id="saved-templates-empty" style="display:none;color:var(--text-secondary);margin:0 0 1rem;">
                    No hay plantillas guardadas que coincidan con la búsqueda.
                </p>

                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-color);text-align:left;">
                                <th style="padding:.75rem .5rem;">ID Wasapi</th>
                                <th style="padding:.75rem .5rem;">UUID</th>
                                <th style="padding:.75rem .5rem;">Nombre (template_id)</th>
                                <th style="padding:.75rem .5rem;">Estado</th>
                                <th style="padding:.75rem .5rem;">Categoría de uso</th>
                                <th style="padding:.75rem .5rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="saved-templates-list">
                            @foreach($savedTemplates as $template)
                                <tr class="template-row"
                                    data-template-name="{{ strtolower($template->template_id) }}"
                                    style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:.75rem .5rem;"><code>{{ $template->wasapi_id }}</code></td>
                                    <td style="padding:.75rem .5rem;"><code style="font-size:.78rem;">{{ $template->uuid ?: '—' }}</code></td>
                                    <td style="padding:.75rem .5rem;font-weight:600;">{{ $template->template_id }}</td>
                                    <td style="padding:.75rem .5rem;">
                                        <span style="background:#edf2f7;padding:.2rem .55rem;border-radius:99px;font-size:.75rem;font-weight:700;">
                                            {{ $template->status }}
                                        </span>
                                    </td>
                                    <td style="padding:.75rem .5rem;min-width:220px;">
                                        <form method="POST" action="{{ route('admin.wasapi.templates.assign-category') }}">
                                            @csrf
                                            <input type="hidden" name="template_id" value="{{ $template->id }}">
                                            <select name="category_id"
                                                    onchange="this.form.submit()"
                                                    style="width:100%;max-width:240px;padding:.45rem .6rem;border:1px solid var(--border-color);border-radius:6px;font-size:.85rem;">
                                                <option value="">Sin categoría</option>
                                                @foreach($categories as $category)
                                                    @php
                                                        $takenByOther = $category->template && $category->template->id !== $template->id;
                                                    @endphp
                                                    <option value="{{ $category->id }}"
                                                        {{ (int) $template->category_id === (int) $category->id ? 'selected' : '' }}
                                                        {{ $takenByOther ? 'disabled' : '' }}>
                                                        {{ $category->name }}{{ $takenByOther ? ' (asignada)' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td style="padding:.75rem .5rem;">
                                        <form method="POST"
                                              action="{{ route('admin.wasapi.templates.destroy', $template) }}"
                                              onsubmit="return confirm('¿Eliminar la plantilla «{{ $template->template_id }}» de la lista guardada?');"
                                              style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="padding:.35rem .75rem;border:1px solid #e53e3e;background:#fff;color:#e53e3e;border-radius:6px;cursor:pointer;font-size:.82rem;white-space:nowrap;">
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

    {{-- Consultar y seleccionar --}}
    <div class="content-card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <h2 class="card-title">Consultar plantillas en Wasapi</h2>
            <form method="POST" action="{{ route('admin.wasapi.templates.fetch') }}">
                @csrf
                <button type="submit" class="btn btn-secondary"
                        style="padding:.55rem 1rem;background:#edf2f7;border:1px solid var(--border-color);border-radius:8px;cursor:pointer;font-weight:600;">
                    Consultar plantillas
                </button>
            </form>
        </div>
        <div class="card-body">
            @if(empty($fetchedTemplates))
                <p style="color:var(--text-secondary);margin:0;">
                    Pulse «Consultar plantillas» para cargar las plantillas disponibles desde Wasapi.
                    Asegúrese de tener el token configurado en <a href="{{ route('admin.wasapi.index') }}">Wasapi / WhatsApp</a>.
                </p>
            @else
                <form method="POST" action="{{ route('admin.wasapi.templates.save') }}">
                    @csrf
                    <p style="margin:0 0 1rem;color:var(--text-secondary);font-size:.9rem;">
                        Seleccione las plantillas que desea utilizar. Solo se guardan <strong>id</strong>, <strong>uuid</strong>, <strong>template_id</strong> y <strong>status</strong>.
                    </p>

                    <input type="search"
                           id="fetched-templates-search"
                           placeholder="Buscar por nombre de plantilla..."
                           autocomplete="off"
                           style="width:100%;max-width:420px;padding:.65rem .85rem;border:2px solid var(--border-color);border-radius:8px;margin-bottom:1rem;font-size:.92rem;">

                    <p id="fetched-templates-empty" style="display:none;color:var(--text-secondary);margin:0 0 1rem;">
                        No hay plantillas que coincidan con la búsqueda.
                    </p>

                    <div id="fetched-templates-list" style="display:flex;flex-direction:column;gap:.65rem;margin-bottom:1.25rem;">
                        @foreach($fetchedTemplates as $template)
                            @php
                                $wasapiId = (int) ($template['id'] ?? 0);
                                $templateName = (string) ($template['template_id'] ?? '—');
                                $templateStatus = (string) ($template['status'] ?? '—');
                                $isChecked = in_array($wasapiId, $savedWasapiIds, true);
                            @endphp
                            @if($wasapiId > 0)
                                <label class="template-item"
                                       data-template-name="{{ strtolower($templateName) }}"
                                       style="display:flex;align-items:flex-start;gap:.75rem;padding:.85rem 1rem;border:1px solid var(--border-color);border-radius:8px;cursor:pointer;background:#fff;">
                                    <input type="checkbox"
                                           name="templates[]"
                                           value="{{ $wasapiId }}"
                                           {{ $isChecked ? 'checked' : '' }}
                                           style="margin-top:.25rem;width:1rem;height:1rem;flex-shrink:0;">
                                    <span style="flex:1;">
                                        <span style="display:block;font-weight:700;font-size:.95rem;">{{ $templateName }}</span>
                                        <span style="display:block;font-size:.82rem;color:var(--text-secondary);margin-top:.25rem;">
                                            ID: {{ $wasapiId }} · Estado: {{ $templateStatus }}
                                        </span>
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary"
                            style="padding:.65rem 1.25rem;background:var(--primary-color);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
                        Guardar plantillas seleccionadas
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    function filterTemplates(inputId, itemSelector, emptyId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const items = document.querySelectorAll(itemSelector);
        const emptyMsg = document.getElementById(emptyId);

        function applyFilter() {
            const query = input.value.trim().toLowerCase();
            let visible = 0;

            items.forEach(function (el) {
                const name = el.getAttribute('data-template-name') || '';
                const match = query === '' || name.includes(query);
                el.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            if (emptyMsg) {
                emptyMsg.style.display = (query !== '' && visible === 0) ? 'block' : 'none';
            }
        }

        input.addEventListener('input', applyFilter);
        applyFilter();
    }

    filterTemplates('saved-templates-search', '#saved-templates-list .template-row', 'saved-templates-empty');
    filterTemplates('fetched-templates-search', '#fetched-templates-list .template-item', 'fetched-templates-empty');
})();
</script>
@endsection
