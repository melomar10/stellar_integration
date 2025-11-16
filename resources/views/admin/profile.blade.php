@extends('layouts.admin')
@section('title', 'Mi Perfil - Panel de Administración')
@section('page-title', 'Mi Perfil')

@section('content')
<div class="dashboard-container">
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Información del Perfil</h2>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div style="background: #efe; border: 1px solid #cfc; color: #3c3; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.profile.update') }}" style="max-width: 600px;">
                @csrf
                @method('PUT')

                <div style="margin-bottom: 1.5rem;">
                    <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Nombre</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name', $user->name) }}" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        class="@error('name') error @endif"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
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
                        value="{{ old('email', $user->email) }}" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        class="@error('email') error @endif"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                    @error('email')
                        <span style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="role" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Rol</label>
                    <div style="padding: 0.75rem; background: var(--light-gray); border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; color: var(--text-secondary);">
                        @php
                            $roleLabels = [
                                'admin' => 'Administrador',
                                'agent' => 'Agente',
                                'api' => 'API'
                            ];
                        @endphp
                        {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem;">El rol no puede ser modificado desde aquí.</p>
                </div>

                <div style="border-top: 2px solid var(--border-color); padding-top: 1.5rem; margin-top: 2rem; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">Cambiar Contraseña</h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">Deja estos campos en blanco si no deseas cambiar tu contraseña.</p>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="current_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Contraseña Actual</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                            class="@error('current_password') error @endif"
                            onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                            onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                        >
                        @error('current_password')
                            <span style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Nueva Contraseña</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                            class="@error('password') error @endif"
                            onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                            onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                        >
                        @error('password')
                            <span style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                        @enderror
                        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem;">Mínimo 8 caracteres</p>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="password_confirmation" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Confirmar Nueva Contraseña</label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                            onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                            onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                        >
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="background: var(--primary-color); color: white; padding: 0.75rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        Guardar Cambios
                    </button>
                    <a href="{{ route('admin.dashboard') }}" style="background: var(--light-gray); color: var(--text-primary); padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-size: 1rem; font-weight: 600; display: inline-block; transition: all 0.3s ease;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Información de la Cuenta</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.25rem;">ID de Usuario</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;">#{{ $user->id }}</p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.25rem;">Fecha de Registro</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.25rem;">Última Actualización</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .form-input.error,
    input.error {
        border-color: #c33 !important;
    }
    
    button[type="submit"]:hover {
        background: var(--primary-dark) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(57, 183, 127, 0.3);
    }
    
    button[type="submit"]:active {
        transform: translateY(0);
    }
</style>
@endpush
@endsection

