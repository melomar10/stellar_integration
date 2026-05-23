@extends('layouts.admin')
@section('title', 'Órdenes Alfred - Panel de Administración')
@section('page-title', 'Órdenes / Transacciones')

@section('content')
@php
    use App\Http\Controllers\Admin\AlfredOrderController as OrderAdmin;
    $fmtMoney = fn ($n) => number_format((float) $n, 2, '.', ',');
@endphp
<div class="dashboard-container">
    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Resumen</h2>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
                <div style="padding:1rem;border:1px solid var(--border-color);border-radius:10px;background:#f8fafc;">
                    <div style="font-size:.8rem;color:var(--text-secondary);margin-bottom:.25rem;">Total transacciones</div>
                    <div style="font-size:1.5rem;font-weight:700;">{{ $stats['total_count'] }}</div>
                </div>
                <div style="padding:1rem;border:1px solid #9ae6b4;border-radius:10px;background:#f0fff4;">
                    <div style="font-size:.8rem;color:#276749;margin-bottom:.25rem;">Completadas</div>
                    <div style="font-size:1.1rem;font-weight:700;">{{ $stats['completed_count'] }} órdenes</div>
                    <div style="font-size:1.25rem;font-weight:700;color:#276749;">{{ $fmtMoney($stats['completed_amount']) }}</div>
                </div>
                <div style="padding:1rem;border:1px solid #f6e05e;border-radius:10px;background:#fffff0;">
                    <div style="font-size:.8rem;color:#744210;margin-bottom:.25rem;">Sin completar</div>
                    <div style="font-size:1.1rem;font-weight:700;">{{ $stats['not_completed_count'] }} órdenes</div>
                    <div style="font-size:1.25rem;font-weight:700;color:#744210;">{{ $fmtMoney($stats['not_completed_amount']) }}</div>
                </div>
                <div style="padding:1rem;border:1px solid var(--border-color);border-radius:10px;">
                    <div style="font-size:.8rem;color:var(--text-secondary);margin-bottom:.25rem;">Monto total (filtro actual)</div>
                    <div style="font-size:1.5rem;font-weight:700;">{{ $fmtMoney($stats['total_amount']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Filtros</h2>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders.index') }}" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
                <div>
                    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.35rem;">Desde</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                           style="padding:.65rem;border:2px solid var(--border-color);border-radius:8px;">
                </div>
                <div>
                    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.35rem;">Hasta</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                           style="padding:.65rem;border:2px solid var(--border-color);border-radius:8px;">
                </div>
                <div style="flex:1;min-width:220px;">
                    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.35rem;">Buscar por nombre (envía / recibe)</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Nombre del remitente o destinatario"
                           style="width:100%;padding:.65rem;border:2px solid var(--border-color);border-radius:8px;">
                </div>
                <button type="submit" style="padding:.65rem 1.25rem;background:var(--primary-color);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;">
                    Filtrar
                </button>
                <a href="{{ route('admin.orders.index') }}" style="padding:.65rem 1rem;color:var(--text-secondary);text-decoration:none;">Limpiar</a>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Órdenes realizadas</h2>
        </div>
        <div class="card-body">
            @if($orders->isEmpty())
                <p style="color:var(--text-secondary);">No hay órdenes con los filtros seleccionados.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-color);text-align:left;">
                                <th style="padding:.75rem .5rem;">Envía</th>
                                <th style="padding:.75rem .5rem;">Recibe</th>
                                <th style="padding:.75rem .5rem;">Monto</th>
                                <th style="padding:.75rem .5rem;">Estado</th>
                                <th style="padding:.75rem .5rem;">Creada</th>
                                <th style="padding:.75rem .5rem;">Completada</th>
                                <th style="padding:.75rem .5rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                @php
                                    $statusClass = match($order->status) {
                                        'completed' => 'background:#c6f6d5;color:#22543d;',
                                        'confirmed' => 'background:#bee3f8;color:#2c5282;',
                                        'processed' => 'background:#feebc8;color:#7b341e;',
                                        default => 'background:#e2e8f0;color:#2d3748;',
                                    };
                                @endphp
                                <tr style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:.75rem .5rem;font-weight:600;">{{ OrderAdmin::accountDisplayName($order->senderAccount) }}</td>
                                    <td style="padding:.75rem .5rem;font-weight:600;">{{ OrderAdmin::accountDisplayName($order->receiverAccount) }}</td>
                                    <td style="padding:.75rem .5rem;">{{ $order->formattedAmount() }}</td>
                                    <td style="padding:.75rem .5rem;">
                                        <span style="display:inline-block;padding:.2rem .55rem;border-radius:99px;font-size:.75rem;font-weight:700;{{ $statusClass }}">
                                            {{ OrderAdmin::statusLabel($order->status) }}
                                        </span>
                                    </td>
                                    <td style="padding:.75rem .5rem;white-space:nowrap;">
                                        {{ ($order->alfred_created_at ?? $order->created_at)?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td style="padding:.75rem .5rem;white-space:nowrap;">
                                        {{ $order->alfred_completed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td style="padding:.75rem .5rem;">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                           style="padding:.35rem .85rem;background:var(--primary-color);color:#fff;border-radius:6px;text-decoration:none;font-size:.82rem;font-weight:600;">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:1.25rem;">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
