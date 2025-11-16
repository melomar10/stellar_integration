@extends('layouts.admin')
@section('title', 'Usuarios - Panel de Administración')
@section('page-title', 'Gestión de Usuarios')

@section('content')
<div class="dashboard-container">
    <div class="content-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title">Lista de Usuarios</h2>
            <a href="{{ route('admin.users.create') }}" style="background: var(--primary-color); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                + Nuevo Usuario
            </a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div style="background: #efe; border: 1px solid #cfc; color: #3c3; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div style="background: #fee; border: 1px solid #fcc; color: #c33; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                    {{ session('error') }}
                </div>
            @endif

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--light-gray); border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">ID</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Nombre</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Email</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Rol</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Creado</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem; color: var(--text-secondary);">{{ $user->id }}</td>
                                <td style="padding: 1rem; color: var(--text-primary); font-weight: 500;">{{ $user->name }}</td>
                                <td style="padding: 1rem; color: var(--text-secondary);">{{ $user->email }}</td>
                                <td style="padding: 1rem;">
                                    @php
                                        $roleColors = [
                                            'admin' => '#39B77F',
                                            'agent' => '#4299e1',
                                            'api' => '#ed8936'
                                        ];
                                        $roleLabels = [
                                            'admin' => 'Administrador',
                                            'agent' => 'Agente',
                                            'api' => 'API'
                                        ];
                                    @endphp
                                    <span style="background: {{ $roleColors[$user->role] ?? '#718096' }}; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                        {{ $roleLabels[$user->role] ?? $user->role }}
                                    </span>
                                </td>
                                <td style="padding: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                        <a href="{{ route('admin.users.edit', $user) }}" style="color: var(--primary-color); text-decoration: none; padding: 0.5rem; border-radius: 6px; transition: all 0.2s ease;" title="Editar">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @if ($user->id !== Auth::id())
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" style="background: none; border: none; color: #e53e3e; cursor: pointer; padding: 0.5rem; border-radius: 6px; transition: all 0.2s ease;" title="Eliminar">
                                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

