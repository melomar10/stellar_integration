@extends('layouts.admin')
@section('title', 'Detalle orden - Panel de Administración')
@section('page-title', 'Detalle de transacción')

@section('content')
@php
    use App\Http\Controllers\Admin\AlfredOrderController as OrderAdmin;
    $statusClass = match($order->status) {
        'completed' => 'background:#c6f6d5;color:#22543d;',
        'confirmed' => 'background:#bee3f8;color:#2c5282;',
        'processed' => 'background:#feebc8;color:#7b341e;',
        default => 'background:#e2e8f0;color:#2d3748;',
    };
@endphp
<div class="dashboard-container">
    <p style="margin-bottom:1rem;">
        <a href="{{ route('admin.orders.index', request()->only(['date_from', 'date_to', 'search'])) }}" style="color:var(--primary-color);text-decoration:none;font-weight:600;">← Volver al listado</a>
    </p>

    <div class="content-card" style="margin-bottom:1.5rem;">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
            <h2 class="card-title">Orden {{ $order->alfred_order_id }}</h2>
            <span style="padding:.35rem .85rem;border-radius:99px;font-size:.8rem;font-weight:700;{{ $statusClass }}">
                {{ OrderAdmin::statusLabel($order->status) }}
            </span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.25rem;">
                <div>
                    <h3 style="font-size:.85rem;color:var(--text-secondary);margin-bottom:.5rem;">Participantes</h3>
                    <p><strong>Envía:</strong> {{ OrderAdmin::accountDisplayName($order->senderAccount) }}</p>
                    <p style="font-size:.85rem;color:var(--text-secondary);">Customer: {{ $order->sender_customer_id }}</p>
                    @if($order->senderAccount?->phone)
                        <p style="font-size:.85rem;">Tel: +{{ $order->senderAccount->phone }}</p>
                    @endif
                    <p style="margin-top:.75rem;"><strong>Recibe:</strong> {{ OrderAdmin::accountDisplayName($order->receiverAccount) }}</p>
                    <p style="font-size:.85rem;color:var(--text-secondary);">Customer: {{ $order->receiver_customer_id }}</p>
                    @if($order->receiverAccount?->phone)
                        <p style="font-size:.85rem;">Tel: +{{ $order->receiverAccount->phone }}</p>
                    @endif
                </div>
                <div>
                    <h3 style="font-size:.85rem;color:var(--text-secondary);margin-bottom:.5rem;">Montos y fechas</h3>
                    <p><strong>Monto:</strong> {{ $order->formattedAmount() }}</p>
                    <p><strong>Creada:</strong> {{ ($order->alfred_created_at ?? $order->created_at)?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? '—' }}</p>
                    <p><strong>Actualizada (Alfred):</strong> {{ $order->alfred_updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? '—' }}</p>
                    <p><strong>Completada:</strong> {{ $order->alfred_completed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? '—' }}</p>
                    <p><strong>Registro local:</strong> {{ $order->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</p>
                </div>
                <div>
                    <h3 style="font-size:.85rem;color:var(--text-secondary);margin-bottom:.5rem;">Referencias</h3>
                    <p style="word-break:break-all;"><strong>Quote ID:</strong> {{ $order->quote_id }}</p>
                    <p style="word-break:break-all;"><strong>Order ID Alfred:</strong> {{ $order->alfred_order_id }}</p>
                    <p><strong>API status:</strong> {{ $order->api_status ?? '—' }}</p>
                    <p><strong>Sub status:</strong> {{ $order->sub_status ?? '—' }}</p>
                    <p><strong>Escrow:</strong> {{ $order->use_escrow ? 'Sí' : 'No' }}</p>
                </div>
            </div>

            @if($order->error_message)
                <div style="margin-top:1rem;padding:.85rem;background:#fff5f5;border:1px solid #feb2b2;border-radius:8px;color:#742a2a;">
                    <strong>Error:</strong> {{ $order->error_message }}
                    @if($order->error_code) ({{ $order->error_code }}) @endif
                </div>
            @endif

            @if(!empty($order->payment_instructions))
                <div style="margin-top:1.5rem;">
                    <h3 style="font-size:.9rem;font-weight:700;margin-bottom:.5rem;">Instrucciones de pago</h3>
                    <pre style="background:#f7fafc;padding:1rem;border-radius:8px;overflow:auto;font-size:.8rem;border:1px solid var(--border-color);">{{ json_encode($order->payment_instructions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if(!empty($order->metadata))
                <div style="margin-top:1.5rem;">
                    <h3 style="font-size:.9rem;font-weight:700;margin-bottom:.5rem;">Metadata</h3>
                    <pre style="background:#f7fafc;padding:1rem;border-radius:8px;overflow:auto;font-size:.8rem;border:1px solid var(--border-color);">{{ json_encode($order->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
