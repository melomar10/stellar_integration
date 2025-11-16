@extends('layouts.admin')
@section('title', 'Clientes - Panel de Administración')
@section('page-title', 'Clientes')

@section('content')
<div class="dashboard-container">
    <div class="content-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2 class="card-title">Gestión de Clientes</h2>
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
            </div>
        </div>
        <div class="card-body">
            <div id="loadingIndicator" style="display: none; text-align: center; padding: 2rem; color: var(--text-secondary);">
                <p>Cargando clientes...</p>
            </div>

            <div id="errorMessage" style="display: none; background: #fee; border: 1px solid #fcc; color: #c33; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"></div>

            <div style="overflow-x: auto;">
                <table id="clientsTable" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--light-gray); border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">ID</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Nombre</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Apellido</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Email</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Teléfono</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Estado</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="clientsTableBody">
                        <tr>
                            <td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                Cargando clientes...
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

<!-- Modal de Flujo -->
<div id="flowModal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div style="background-color: white; border-radius: 12px; padding: 0; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: white; z-index: 10;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">Línea de Tiempo del Flujo</h2>
            <button onclick="closeFlowModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); padding: 0.25rem 0.5rem; border-radius: 6px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--light-gray)'; this.style.color='var(--text-primary)'" onmouseout="this.style.background='none'; this.style.color='var(--text-secondary)'">&times;</button>
        </div>
        <div id="loadMoreStepsBtn" style="display: none; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); text-align: center; background: white; position: sticky; top: 73px; z-index: 9;">
            <button 
                id="loadMoreStepsButton"
                onclick="loadMoreSteps()"
                style="background: var(--primary-color); color: white; padding: 0.75rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                onmouseover="this.style.background='var(--primary-dark)'; this.style.transform='translateY(-1px)'"
                onmouseout="this.style.background='var(--primary-color)'; this.style.transform='translateY(0)'"
            >
                Ver anteriores
            </button>
        </div>
        <div style="padding: 1.5rem;">
            <div id="flowModalLoading" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <p>Cargando flujos...</p>
            </div>
            <div id="flowModalError" style="display: none; background: #fee; border: 1px solid #fcc; color: #c33; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"></div>
            <div id="flowModalContent"></div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline-container {
    position: relative;
    padding: 1rem 0;
}

.timeline-item {
    position: relative;
    padding-left: 3rem;
    margin-bottom: 2rem;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0.25rem;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--border-color);
    border: 3px solid white;
    box-shadow: 0 0 0 2px var(--border-color);
    z-index: 2;
}

.timeline-marker.active {
    background: var(--primary-color);
    box-shadow: 0 0 0 2px var(--primary-color);
}

.timeline-line {
    position: absolute;
    left: 7px;
    top: 1.25rem;
    width: 2px;
    height: calc(100% + 1rem);
    background: var(--border-color);
    z-index: 1;
}

.timeline-content {
    background: var(--light-gray);
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s ease;
}

.timeline-content:hover {
    background: #e8f5e9;
    transform: translateX(4px);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.timeline-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    flex: 1;
}

.timeline-date {
    font-size: 0.85rem;
    color: var(--text-secondary);
    white-space: nowrap;
}

.timeline-description {
    margin: 0.5rem 0 0 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
    line-height: 1.5;
}

.timeline-badge {
    display: inline-block;
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.5rem;
    text-transform: capitalize;
}

@media (max-width: 768px) {
    .timeline-item {
        padding-left: 2.5rem;
    }
    
    .timeline-header {
        flex-direction: column;
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
        const clientsTableBody = document.getElementById('clientsTableBody');
        const paginationContainer = document.getElementById('paginationContainer');

        // Verificar que todos los elementos existan
        if (!searchInput || !searchBtn || !loadingIndicator || !errorMessage || !clientsTableBody || !paginationContainer) {
            console.error('Error: No se encontraron todos los elementos necesarios del DOM');
            return;
        }

        // Función para cargar clientes
        function loadClients(page = 1, search = '') {
            currentPage = page;
            currentSearch = search;
            
            loadingIndicator.style.display = 'block';
            errorMessage.style.display = 'none';
            clientsTableBody.innerHTML = '<tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Cargando...</td></tr>';

            const params = new URLSearchParams({
                page: page,
                per_page: perPage
            });

            if (search) {
                params.append('search', search);
            }

            fetch(`/api/client/all?${params.toString()}`, {
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
                    showError(data.message || 'Error al cargar los clientes');
                    return;
                }

                // Manejar diferentes formatos de respuesta
                const clients = data.data || [];
                const paginationData = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    per_page: data.per_page || perPage,
                    total: data.total || 0,
                    from: data.from || 0,
                    to: data.to || 0
                };

                if (Array.isArray(clients) && clients.length > 0) {
                    renderClients(clients);
                    renderPagination(paginationData);
                } else {
                    clientsTableBody.innerHTML = '<tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No se encontraron clientes</td></tr>';
                    paginationContainer.innerHTML = '';
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                console.error('Error completo:', error);
                showError('Error al cargar los clientes: ' + error.message);
            });
        }

        // Función para renderizar clientes
        function renderClients(clients) {
            clientsTableBody.innerHTML = clients.map(client => {
                const statusBadge = client.status 
                ? '<span style="background: #39B77F; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">Activo</span>'
                : '<span style="background: #e53e3e; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">Inactivo</span>';

            return `
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 1rem; color: var(--text-secondary);">${client.id || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-primary); font-weight: 500;">${client.name || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-primary);">${client.last_name || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-secondary);">${client.email || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-secondary);">${client.phone || '-'}</td>
                    <td style="padding: 1rem;">${statusBadge}</td>
                    <td style="padding: 1rem; text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <button 
                                onclick="editClient(${client.id})" 
                                style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background='var(--primary-dark)'; this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.background='var(--primary-color)'; this.style.transform='translateY(0)'"
                            >
                                Editar
                            </button>
                            <button 
                                onclick="viewFlow(${client.id})" 
                                style="background: #4299e1; color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background='#3182ce'; this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.background='#4299e1'; this.style.transform='translateY(0)'"
                            >
                                Flujo
                            </button>
                        </div>
                    </td>
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
                    onclick="loadClients(${data.current_page - 1}, '${currentSearch}')" 
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
            paginationHTML += `<button onclick="loadClients(1, '${currentSearch}')" style="padding: 0.5rem 0.75rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer;">1</button>`;
            if (startPage > 2) {
                paginationHTML += '<span style="padding: 0.5rem; color: var(--text-secondary);">...</span>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <button 
                    onclick="loadClients(${i}, '${currentSearch}')" 
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
            paginationHTML += `<button onclick="loadClients(${data.last_page}, '${currentSearch}')" style="padding: 0.5rem 0.75rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer;">${data.last_page}</button>`;
        }

        // Botón Siguiente
        if (data.current_page < data.last_page) {
            paginationHTML += `
                <button 
                    onclick="loadClients(${data.current_page + 1}, '${currentSearch}')" 
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
            clientsTableBody.innerHTML = '<tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Error al cargar los clientes</td></tr>';
        }

        // Función para buscar
        function handleSearch() {
            const search = searchInput.value.trim();
            loadClients(1, search);
        }

        // Event listeners
        searchBtn.addEventListener('click', handleSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });

        // Hacer funciones globales para los botones
        window.loadClients = loadClients;
        window.editClient = function(clientId) {
            // TODO: Implementar edición de cliente
            alert('Editar cliente ID: ' + clientId);
            console.log('Editar cliente:', clientId);
        };
        window.viewFlow = function(clientId) {
            openFlowModal(clientId);
        };

        // Variables globales para el modal de flujo (fuera del scope de init para que sean accesibles)
        window.flowModalState = {
            currentClientId: null,
            loadedSteps: [],
            currentFlowPage: 1,
            totalSteps: 0,
            hasMoreSteps: false
        };

        // Función para abrir el modal y cargar los flujos
        function openFlowModal(clientId) {
            window.flowModalState.currentClientId = clientId;
            window.flowModalState.loadedSteps = [];
            window.flowModalState.currentFlowPage = 1;
            window.flowModalState.totalSteps = 0;
            window.flowModalState.hasMoreSteps = false;

            const modal = document.getElementById('flowModal');
            const modalContent = document.getElementById('flowModalContent');
            const modalLoading = document.getElementById('flowModalLoading');
            const modalError = document.getElementById('flowModalError');
            const loadMoreBtn = document.getElementById('loadMoreStepsBtn');
            
            modal.style.display = 'flex';
            modalLoading.style.display = 'block';
            modalError.style.display = 'none';
            modalContent.innerHTML = '';
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';

            // Cargar inicialmente 3 steps
            loadFlowSteps(clientId, 1, 3, true);
        }

        // Función para cargar steps del flujo
        function loadFlowSteps(clientId, page = 1, perPage = 10, isInitial = false) {
            const modalContent = document.getElementById('flowModalContent');
            const modalLoading = document.getElementById('flowModalLoading');
            const modalError = document.getElementById('flowModalError');
            const loadMoreBtn = document.getElementById('loadMoreStepsBtn');
            const loadMoreButton = document.getElementById('loadMoreStepsButton');

            if (isInitial) {
                modalLoading.style.display = 'block';
                modalError.style.display = 'none';
            } else {
                if (loadMoreButton) {
                    loadMoreButton.disabled = true;
                    loadMoreButton.textContent = 'Cargando...';
                }
            }

            fetch(`/api/flows/client-step-by-flow/${clientId}?page=${page}&per_page=${perPage}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                modalLoading.style.display = 'none';
                
                // El endpoint devuelve datos paginados
                const steps = data.data || [];
                const totalSteps = data.total || 0;
                const lastPage = data.last_page || 1;
                
                if (isInitial) {
                    // Los steps vienen ordenados descendente (más recientes primero) del API
                    // Los guardamos tal cual
                    window.flowModalState.loadedSteps = [...steps];
                    if (steps.length > 0) {
                        renderTimeline(window.flowModalState.loadedSteps);
                    } else {
                        modalContent.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No hay flujos registrados para este cliente.</p>';
                    }
                } else {
                    // Los nuevos steps de la página siguiente son más antiguos que los ya cargados
                    // Los agregamos al principio porque son más antiguos
                    // Luego renderTimeline los ordenará correctamente por fecha
                    console.log('Agregando steps anteriores. Steps nuevos:', steps.length, 'Steps totales antes:', window.flowModalState.loadedSteps.length);
                    
                    // Guardar la posición actual del scroll antes de agregar nuevos steps
                    const modalContent = document.getElementById('flowModalContent');
                    const scrollPosition = modalContent ? modalContent.scrollTop : 0;
                    
                    window.flowModalState.loadedSteps = [...steps, ...window.flowModalState.loadedSteps];
                    console.log('Steps totales después:', window.flowModalState.loadedSteps.length);
                    
                    renderTimeline(window.flowModalState.loadedSteps);
                    
                    // Hacer scroll hacia arriba para mostrar los nuevos steps cargados
                    setTimeout(() => {
                        const modal = document.getElementById('flowModal');
                        if (modal) {
                            // El contenedor con scroll es el segundo div (el que tiene overflow-y: auto)
                            const scrollableDiv = modal.querySelector('div > div[style*="overflow-y"]');
                            if (scrollableDiv) {
                                scrollableDiv.scrollTop = 0;
                            }
                        }
                    }, 150);
                }

                // Verificar si hay más steps para cargar
                window.flowModalState.hasMoreSteps = window.flowModalState.currentFlowPage < lastPage;
                window.flowModalState.totalSteps = totalSteps;
                if (loadMoreBtn && loadMoreButton) {
                    if (window.flowModalState.hasMoreSteps) {
                        loadMoreBtn.style.display = 'block';
                        loadMoreButton.disabled = false;
                        loadMoreButton.textContent = 'Ver anteriores';
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                }

                window.flowModalState.currentFlowPage = page;
            })
            .catch(error => {
                modalLoading.style.display = 'none';
                modalError.style.display = 'block';
                modalError.textContent = 'Error al cargar los flujos: ' + error.message;
                const loadMoreButton = document.getElementById('loadMoreStepsButton');
                if (loadMoreButton) {
                    loadMoreButton.disabled = false;
                    loadMoreButton.textContent = 'Ver anteriores';
                }
                console.error('Error:', error);
            });
        }

        // Función para cargar más steps (hacerla global)
        window.loadMoreSteps = function() {
            const state = window.flowModalState;
            console.log('loadMoreSteps llamado. Estado:', {
                currentClientId: state.currentClientId,
                currentFlowPage: state.currentFlowPage,
                hasMoreSteps: state.hasMoreSteps,
                loadedStepsCount: state.loadedSteps.length
            });
            if (state.currentClientId && state.hasMoreSteps) {
                console.log('Cargando página:', state.currentFlowPage);
                loadFlowSteps(state.currentClientId, state.currentFlowPage , 10, false);
            } else {
                console.log('No se puede cargar más. currentClientId:', state.currentClientId, 'hasMoreSteps:', state.hasMoreSteps);
            }
        };

        // Función para renderizar la línea de tiempo
        function renderTimeline(steps) {
            const modalContent = document.getElementById('flowModalContent');
            
            // Ordenar steps por fecha (más antiguos primero para la línea de tiempo)
            const sortedSteps = [...steps].sort((a, b) => {
                const dateA = new Date(a.created_at);
                const dateB = new Date(b.created_at);
                return dateA - dateB;
            });
            
            console.log('Renderizando timeline. Total steps:', sortedSteps.length);

            let timelineHTML = '<div class="timeline-container">';
            
            sortedSteps.forEach((step, index) => {
                const date = new Date(step.created_at);
                const formattedDate = date.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // El último step es el más reciente (último en el array ordenado)
                const isLast = index === sortedSteps.length - 1;
                
                timelineHTML += `
                    <div class="timeline-item">
                        <div class="timeline-marker ${isLast ? 'active' : ''}"></div>
                        ${!isLast ? '<div class="timeline-line"></div>' : ''}
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h4 class="timeline-title">${step.name || 'Sin nombre'}</h4>
                                <span class="timeline-date">${formattedDate}</span>
                            </div>
                            ${step.description ? `<p class="timeline-description">${step.description}</p>` : ''}
                            ${step.type ? `<span class="timeline-badge">${step.type}</span>` : ''}
                        </div>
                    </div>
                `;
            });

            timelineHTML += '</div>';
            modalContent.innerHTML = timelineHTML;
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('flowModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };

        // Cerrar modal con botón
        window.closeFlowModal = function() {
            document.getElementById('flowModal').style.display = 'none';
        };

        // Cargar clientes al iniciar
        loadClients(1, '');

        // Hacer funciones globales para los botones
        window.loadClients = loadClients;
        window.editClient = function(clientId) {
            // TODO: Implementar edición de cliente
            alert('Editar cliente ID: ' + clientId);
            console.log('Editar cliente:', clientId);
        };
        window.viewFlow = function(clientId) {
            openFlowModal(clientId);
        };
    }
})();
</script>
@endpush
@endsection

