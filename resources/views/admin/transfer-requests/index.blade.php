@extends('layouts.admin')
@section('title', 'Solicitudes de transferencia - Panel de Administración')
@section('page-title', 'Solicitudes de transferencia')

@section('content')
@php
    use App\Http\Controllers\Admin\TransferRequestController as TransferAdmin;
    use App\Models\TransferRequest;
    $fmtMoney = fn ($n, $currency = 'USD') => number_format((float) $n, 2, '.', ',').' '.$currency;
@endphp
<div class="dashboard-container">
    @if(session('success'))
        <div style="background:#f0fff4;border:1px solid #9ae6b4;color:#1c4532;padding:.85rem 1rem;border-radius:8px;margin-bottom:1rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fff5f5;border:1px solid #feb2b2;color:#742a2a;padding:.85rem 1rem;border-radius:8px;margin-bottom:1rem;">
            {{ session('error') }}
        </div>
    @endif

    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Resumen</h2>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;">
                <div style="padding:1rem;border:1px solid var(--border-color);border-radius:10px;background:#f8fafc;">
                    <div style="font-size:.8rem;color:var(--text-secondary);">Total</div>
                    <div style="font-size:1.4rem;font-weight:700;">{{ $stats['total'] }}</div>
                </div>
                <div style="padding:1rem;border:1px solid #bee3f8;border-radius:10px;background:#ebf8ff;">
                    <div style="font-size:.8rem;color:#2c5282;">Solicitadas</div>
                    <div style="font-size:1.4rem;font-weight:700;">{{ $stats['solicitada'] }}</div>
                </div>
                <div style="padding:1rem;border:1px solid #9ae6b4;border-radius:10px;background:#f0fff4;">
                    <div style="font-size:.8rem;color:#276749;">Aprobadas</div>
                    <div style="font-size:1.4rem;font-weight:700;">{{ $stats['aprobado'] }}</div>
                </div>
                <div style="padding:1rem;border:1px solid #c6f6d5;border-radius:10px;background:#f0fff4;">
                    <div style="font-size:.8rem;color:#22543d;">Completadas</div>
                    <div style="font-size:1.4rem;font-weight:700;">{{ $stats['completada'] }}</div>
                </div>
                <div style="padding:1rem;border:1px solid #feb2b2;border-radius:10px;background:#fff5f5;">
                    <div style="font-size:.8rem;color:#742a2a;">Canceladas</div>
                    <div style="font-size:1.4rem;font-weight:700;">{{ $stats['cancelada'] }}</div>
                </div>
                <div style="padding:1rem;border:1px solid #fbd38d;border-radius:10px;background:#fffaf0;">
                    <div style="font-size:.8rem;color:#744210;">Rechazadas</div>
                    <div style="font-size:1.4rem;font-weight:700;">{{ $stats['rechazada'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Filtros</h2>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.transfer-requests.index') }}" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
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
                <div>
                    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.35rem;">Estado</label>
                    <select name="status" style="padding:.65rem;border:2px solid var(--border-color);border-radius:8px;min-width:160px;">
                        <option value="">Todos</option>
                        @foreach(TransferRequest::STATUSES as $st)
                            <option value="{{ $st }}" {{ $filters['status'] === $st ? 'selected' : '' }}>
                                {{ TransferAdmin::statusLabel($st) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1;min-width:200px;">
                    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.35rem;">Buscar</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                           placeholder="Teléfono, UUID o notas"
                           style="width:100%;padding:.65rem;border:2px solid var(--border-color);border-radius:8px;">
                </div>
                <button type="submit" style="padding:.65rem 1.25rem;background:var(--primary-color);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;">
                    Filtrar
                </button>
                <a href="{{ route('admin.transfer-requests.index') }}" style="padding:.65rem 1rem;color:var(--text-secondary);text-decoration:none;">Limpiar</a>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Solicitudes</h2>
        </div>
        <div class="card-body">
            @if($requests->isEmpty())
                <p style="color:var(--text-secondary);margin:0;">No hay solicitudes con los filtros seleccionados.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-color);text-align:left;">
                                <th style="padding:.75rem .5rem;">Remitente (sender)</th>
                                <th style="padding:.75rem .5rem;">Destinatario (receiver)</th>
                                <th style="padding:.75rem .5rem;">Monto</th>
                                <th style="padding:.75rem .5rem;">Estado</th>
                                <th style="padding:.75rem .5rem;">Creada</th>
                                <th style="padding:.75rem .5rem;">Notas</th>
                                <th style="padding:.75rem .5rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $req)
                                @php
                                    $badgeStyle = match($req->status) {
                                        TransferRequest::STATUS_SOLICITADA => 'background:#bee3f8;color:#2c5282;',
                                        TransferRequest::STATUS_APROBADO   => 'background:#c6f6d5;color:#22543d;',
                                        TransferRequest::STATUS_COMPLETADA => 'background:#9ae6b4;color:#1c4532;',
                                        TransferRequest::STATUS_CANCELADA  => 'background:#e2e8f0;color:#4a5568;',
                                        TransferRequest::STATUS_RECHAZADA  => 'background:#fed7d7;color:#742a2a;',
                                        default => 'background:#edf2f7;color:#2d3748;',
                                    };
                                @endphp
                                <tr style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:.75rem .5rem;">
                                        <div style="font-weight:600;">{{ $req->sender_phone }}</div>
                                        @if($req->sender_customer_id)
                                            <code style="font-size:.72rem;color:var(--text-secondary);">{{ $req->sender_customer_id }}</code>
                                        @endif
                                    </td>
                                    <td style="padding:.75rem .5rem;">
                                        <div style="font-weight:600;">{{ $req->receiver_phone }}</div>
                                        @if($req->receiver_customer_id)
                                            <code style="font-size:.72rem;color:var(--text-secondary);">{{ $req->receiver_customer_id }}</code>
                                        @endif
                                    </td>
                                    <td style="padding:.75rem .5rem;font-weight:700;">
                                        {{ $fmtMoney($req->amount, $req->currency) }}
                                    </td>
                                    <td style="padding:.75rem .5rem;">
                                        <span style="{{ $badgeStyle }}padding:.25rem .6rem;border-radius:99px;font-size:.75rem;font-weight:700;">
                                            {{ TransferAdmin::statusLabel($req->status) }}
                                        </span>
                                    </td>
                                    <td style="padding:.75rem .5rem;white-space:nowrap;">
                                        {{ $req->created_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td style="padding:.75rem .5rem;max-width:180px;">
                                        <span style="font-size:.85rem;color:var(--text-secondary);">
                                            {{ $req->notes ? \Illuminate\Support\Str::limit($req->notes, 60) : '—' }}
                                        </span>
                                    </td>
                                    <td style="padding:.75rem .5rem;white-space:nowrap;">
                                        @if($req->canBeCancelled())
                                            <form method="POST"
                                                  action="{{ route('admin.transfer-requests.cancel', $req) }}"
                                                  onsubmit="return confirm('¿Cancelar esta solicitud de transferencia?');"
                                                  style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        style="padding:.35rem .75rem;border:1px solid #e53e3e;background:#fff;color:#e53e3e;border-radius:6px;cursor:pointer;font-size:.82rem;">
                                                    Cancelar
                                                </button>
                                            </form>
                                        @else
                                            <span style="color:var(--text-secondary);font-size:.82rem;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:1.25rem;">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
