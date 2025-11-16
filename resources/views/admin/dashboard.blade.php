@extends('layouts.admin')
@section('title', 'Dashboard - Panel de Administración')
@section('page-title', 'Dashboard')

@section('content')
<div class="dashboard-container">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(57, 183, 127, 0.1);">
                <svg fill="none" stroke="#39B77F" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value">{{ number_format($totalClients ?? 0) }}</h3>
                <p class="stat-label">Total Clientes</p>
            </div>
        </div>

      <!--  <div class="stat-card">
            <div class="stat-icon" style="background: rgba(57, 183, 127, 0.1);">
                <svg fill="none" stroke="#39B77F" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value">567</h3>
                <p class="stat-label">Flujos Activos</p>
            </div>
        </div> -->

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(57, 183, 127, 0.1);">
                <svg fill="none" stroke="#39B77F" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value">890</h3>
                <p class="stat-label">Transferencias</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(57, 183, 127, 0.1);">
                <svg fill="none" stroke="#39B77F" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value">$12,345</h3>
                <p class="stat-label">Ingresos Totales</p>
            </div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="content-grid">
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Actividad Reciente</h2>
            </div>
            <div class="card-body">
                <p>Contenido de actividad reciente aquí...</p>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Estadísticas</h2>
            </div>
            <div class="card-body">
                <p>Gráficos y estadísticas aquí...</p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon svg {
    width: 32px;
    height: 32px;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.content-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
}

.card-body {
    padding: 1.5rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
@endsection