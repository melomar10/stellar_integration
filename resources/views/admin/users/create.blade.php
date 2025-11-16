@extends('layouts.admin')
@section('title', 'Crear Usuario - Panel de Administración')
@section('page-title', 'Crear Usuario')

@section('content')
<div class="dashboard-container">
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Crear Nuevo Usuario</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}" style="max-width: 600px;">
                @csrf

                <div style="margin-bottom: 1.5rem;">
                    <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Nombre</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;"
                        class="@error('name') error @endif"
                    >
                    @error('name')
                        <span style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;"
                        class="@error('email') error @endif"
                    >
                    @error('email')
                        <span style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;"
                        class="@error('password') error @endif"
                    >
                    @error('password')
                        <span style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="password_confirmation" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Confirmar Contraseña</label>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;"
                    >
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="role" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Rol</label>
                    <select 
                        id="role" 
                        name="role" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;"
                        class="@error('role') error @endif"
                    >
                        <option value="">Seleccionar rol</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                        <option value="agent" {{ old('role') === 'agent' ? 'selected' : '' }}>Agente</option>
                        <option value="api" {{ old('role') === 'api' ? 'selected' : '' }}>API</option>
                    </select>
                    @error('role')
                        <span style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="background: var(--primary-color); color: white; padding: 0.75rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        Crear Usuario
                    </button>
                    <a href="{{ route('admin.users.index') }}" style="background: var(--light-gray); color: var(--text-primary); padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-size: 1rem; font-weight: 600; display: inline-block; transition: all 0.3s ease;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

