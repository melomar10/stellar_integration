@extends('layouts.admin')
@section('title', 'Lista de Espera - Panel de Administración')
@section('page-title', 'Lista de Espera')

@section('content')
<div class="dashboard-container">
    <div class="content-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2 class="card-title">Gestión de Lista de Espera</h2>
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="position: relative; flex: 1; min-width: 250px;">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Buscar por nombre o teléfono..." 
                        style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                    <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; color: var(--text-secondary); pointer-events: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button 
                    id="searchBtn" 
                    style="background: var(--primary-color); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                    onmouseover="this.style.background='var(--primary-dark)'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.background='var(--primary-color)'; this.style.transform='translateY(0)'"
                >
                    Buscar
                </button>
                <button 
                    id="exportBtn" 
                    style="background: #10b981; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;"
                    onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'"
                >
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exportar Excel
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="loadingIndicator" style="display: none; text-align: center; padding: 2rem; color: var(--text-secondary);">
                <p>Cargando lista de espera...</p>
            </div>

            <div id="errorMessage" style="display: none; background: #fee; border: 1px solid #fcc; color: #c33; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"></div>

            <div style="overflow-x: auto;">
                <table id="waitingListTable" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--light-gray); border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">ID</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Nombre</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Apellido</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Email</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Teléfono</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Estado</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Fecha de Registro</th>
                        </tr>
                    </thead>
                    <tbody id="waitingListTableBody">
                        <tr>
                            <td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                Cargando lista de espera...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="paginationContainer" style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <!-- La paginación se generará dinámicamente -->
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        let currentPage = 1;
        let currentSearch = '';
        const perPage = 15;

        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const errorMessage = document.getElementById('errorMessage');
        const waitingListTableBody = document.getElementById('waitingListTableBody');
        const paginationContainer = document.getElementById('paginationContainer');

        // Verificar que todos los elementos existan
        if (!searchInput || !searchBtn || !loadingIndicator || !errorMessage || !waitingListTableBody || !paginationContainer) {
            console.error('Error: No se encontraron todos los elementos necesarios del DOM');
            return;
        }

        // Función para cargar la lista de espera
        function loadWaitingList(page = 1, search = '') {
            currentPage = page;
            currentSearch = search;
            
            loadingIndicator.style.display = 'block';
            errorMessage.style.display = 'none';
            waitingListTableBody.innerHTML = '<tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Cargando...</td></tr>';

            const params = new URLSearchParams({
                page: page,
                per_page: perPage
            });

            if (search) {
                params.append('search', search);
            }

            fetch(`/api/waiting-list?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                loadingIndicator.style.display = 'none';
                console.log('Datos recibidos:', data);
                
                if (data.ok === false) {
                    showError(data.message || 'Error al cargar la lista de espera');
                    return;
                }

                // Manejar diferentes formatos de respuesta
                const items = data.data || [];
                const paginationData = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    per_page: data.per_page || perPage,
                    total: data.total || 0,
                    from: data.from || 0,
                    to: data.to || 0
                };

                if (Array.isArray(items) && items.length > 0) {
                    renderWaitingList(items);
                    renderPagination(paginationData);
                } else {
                    waitingListTableBody.innerHTML = '<tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No se encontraron registros en la lista de espera</td></tr>';
                    paginationContainer.innerHTML = '';
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                console.error('Error completo:', error);
                showError('Error al cargar la lista de espera: ' + error.message);
            });
        }

        // Función para renderizar la lista de espera
        function renderWaitingList(items) {
            waitingListTableBody.innerHTML = items.map(item => {
                const client = item.client || {};
                const statusBadge = client.status 
                    ? '<span style="background: #39B77F; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">Activo</span>'
                    : '<span style="background: #e53e3e; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">Inactivo</span>';

                const createdAt = item.created_at ? new Date(item.created_at).toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : '-';

                return `
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem; color: var(--text-secondary);">${item.id || '-'}</td>
                        <td style="padding: 1rem; color: var(--text-primary); font-weight: 500;">${client.name || '-'}</td>
                        <td style="padding: 1rem; color: var(--text-primary);">${client.last_name || '-'}</td>
                        <td style="padding: 1rem; color: var(--text-secondary);">${client.email || '-'}</td>
                        <td style="padding: 1rem; color: var(--text-secondary);">${client.phone || '-'}</td>
                        <td style="padding: 1rem;">${statusBadge}</td>
                        <td style="padding: 1rem; color: var(--text-secondary);">${createdAt}</td>
                    </tr>
                `;
            }).join('');
        }

        // Función para renderizar paginación
        function renderPagination(data) {
            if (data.last_page <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            let paginationHTML = '<div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">';

            // Botón Anterior
            if (data.current_page > 1) {
                paginationHTML += `
                    <button 
                        onclick="loadWaitingList(${data.current_page - 1}, '${currentSearch}')" 
                        style="padding: 0.5rem 1rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.borderColor='var(--primary-color)'; this.style.color='var(--primary-color)'"
                        onmouseout="this.style.borderColor='var(--border-color)'; this.style.color='inherit'"
                    >
                        Anterior
                    </button>
                `;
            }

            // Números de página
            const startPage = Math.max(1, data.current_page - 2);
            const endPage = Math.min(data.last_page, data.current_page + 2);

            if (startPage > 1) {
                paginationHTML += `<button onclick="loadWaitingList(1, '${currentSearch}')" style="padding: 0.5rem 0.75rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer;">1</button>`;
                if (startPage > 2) {
                    paginationHTML += '<span style="padding: 0.5rem; color: var(--text-secondary);">...</span>';
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button 
                        onclick="loadWaitingList(${i}, '${currentSearch}')" 
                        style="padding: 0.5rem 0.75rem; border: 2px solid ${i === data.current_page ? 'var(--primary-color)' : 'var(--border-color)'}; background: ${i === data.current_page ? 'var(--primary-color)' : 'white'}; color: ${i === data.current_page ? 'white' : 'inherit'}; border-radius: 6px; cursor: pointer; font-weight: ${i === data.current_page ? '600' : '400'}; transition: all 0.2s ease;"
                        onmouseover="${i !== data.current_page ? "this.style.borderColor='var(--primary-color)'; this.style.color='var(--primary-color)'" : ''}"
                        onmouseout="${i !== data.current_page ? "this.style.borderColor='var(--border-color)'; this.style.color='inherit'" : ''}"
                    >
                        ${i}
                    </button>
                `;
            }

            if (endPage < data.last_page) {
                if (endPage < data.last_page - 1) {
                    paginationHTML += '<span style="padding: 0.5rem; color: var(--text-secondary);">...</span>';
                }
                paginationHTML += `<button onclick="loadWaitingList(${data.last_page}, '${currentSearch}')" style="padding: 0.5rem 0.75rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer;">${data.last_page}</button>`;
            }

            // Botón Siguiente
            if (data.current_page < data.last_page) {
                paginationHTML += `
                    <button 
                        onclick="loadWaitingList(${data.current_page + 1}, '${currentSearch}')" 
                        style="padding: 0.5rem 1rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.borderColor='var(--primary-color)'; this.style.color='var(--primary-color)'"
                        onmouseout="this.style.borderColor='var(--border-color)'; this.style.color='inherit'"
                    >
                        Siguiente
                    </button>
                `;
            }

            paginationHTML += `<span style="padding: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Mostrando ${data.from || 0} - ${data.to || 0} de ${data.total || 0}</span>`;
            paginationHTML += '</div>';

            paginationContainer.innerHTML = paginationHTML;
        }

        // Función para mostrar errores
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            waitingListTableBody.innerHTML = '<tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Error al cargar la lista de espera</td></tr>';
        }

        // Función para buscar
        function handleSearch() {
            const search = searchInput.value.trim();
            loadWaitingList(1, search);
        }

        // Función para exportar a Excel
        function exportToExcel() {
            const exportBtn = document.getElementById('exportBtn');
            const originalText = exportBtn.innerHTML;
            
            // Deshabilitar botón y mostrar loading
            exportBtn.disabled = true;
            exportBtn.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Exportando...';
            
            // Construir parámetros de filtro
            const params = new URLSearchParams();
            
            const search = searchInput.value.trim();
            
            if (search) {
                params.append('search', search);
            }
            
            // Crear URL con parámetros y descargar
            const url = `/admin/waiting-list/export${params.toString() ? '?' + params.toString() : ''}`;
            window.location.href = url;
            
            // Restaurar botón después de un breve delay
            setTimeout(() => {
                exportBtn.disabled = false;
                exportBtn.innerHTML = originalText;
            }, 3000);
        }

        // Event listeners
        searchBtn.addEventListener('click', handleSearch);
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', exportToExcel);
        }
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });

        // Hacer función global para los botones de paginación
        window.loadWaitingList = loadWaitingList;

        // Cargar lista de espera al iniciar
        loadWaitingList(1, '');
    }
})();
</script>
@endpush
@endsection
