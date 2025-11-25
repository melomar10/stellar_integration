@extends('layouts.admin')
@section('title', 'Clientes - Panel de Administración')
@section('page-title', 'Clientes')

@section('content')
<div class="dashboard-container">
    <div class="content-card">
        <div class="card-header" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <h2 class="card-title">Gestión de Clientes</h2>
            </div>
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
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label for="dateFrom" style="font-size: 0.9rem; color: var(--text-secondary); white-space: nowrap;">Desde:</label>
                    <input 
                        type="date" 
                        id="dateFrom" 
                        style="padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label for="dateTo" style="font-size: 0.9rem; color: var(--text-secondary); white-space: nowrap;">Hasta:</label>
                    <input 
                        type="date" 
                        id="dateTo" 
                        style="padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label for="stepNameFilter" style="font-size: 0.9rem; color: var(--text-secondary); white-space: nowrap;">Step:</label>
                    <select 
                        id="stepNameFilter" 
                        style="padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease; min-width: 200px; background: white; cursor: pointer;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                        <option value="">Todos los steps</option>
                        <option value="Saludo Inicial">Saludo Inicial</option>
                        <option value="Estoy en DO">Estoy en DO</option>
                        <option value="Estoy en US">Estoy en US</option>
                        <option value="Ver Tutorial">Ver Tutorial</option>
                        <option value="Reportar Problema">Reportar Problema</option>
                        <option value="Crear Cuenta">Crear Cuenta</option>
                        <option value="Tarifas y Costos">Tarifas y Costos</option>
                        <option value="Servicio al cliente">Servicio al cliente</option>
                        <option value="Función Domipago">Función Domipago</option>
                        <option value="Hablar con un representante">Hablar con un representante</option>
                        <option value="Enviar a Europa">Enviar a Europa</option>
                        <option value="Registro Enviar a Europa">Registro Enviar a Europa</option>
                        <option value="Registro Completo Enviar a Europa">Registro Completo Enviar a Europa</option>
                        <option value="Cerrar Conversación">Cerrar Conversación</option>
                        <option value="Obtener link de pago">Obtener link de pago</option>
                    </select>
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
                    id="clearFiltersBtn" 
                    style="background: var(--light-gray); color: var(--text-primary); padding: 0.75rem 1.5rem; border: 2px solid var(--border-color); border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#e0e0e0'; this.style.borderColor='var(--text-secondary)'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.background='var(--light-gray)'; this.style.borderColor='var(--border-color)'; this.style.transform='translateY(0)'"
                >
                    Limpiar Filtros
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
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-primary);">Tiene Cuenta</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="clientsTableBody">
                        <tr>
                            <td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">
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

<!-- Modal de Edición de Cliente -->
<div id="editClientModal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div style="background-color: white; border-radius: 12px; padding: 0; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: white; z-index: 10;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">Editar Cliente</h2>
            <button onclick="closeEditClientModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); padding: 0.25rem 0.5rem; border-radius: 6px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--light-gray)'; this.style.color='var(--text-primary)'" onmouseout="this.style.background='none'; this.style.color='var(--text-secondary)'">&times;</button>
        </div>
        <div style="padding: 1.5rem;">
            <div id="editClientModalLoading" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <p>Cargando datos del cliente...</p>
            </div>
            <div id="editClientModalError" style="display: none; background: #fee; border: 1px solid #fcc; color: #c33; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"></div>
            <form id="editClientForm" style="display: none;">
                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Nombre</label>
                    <input 
                        type="text" 
                        id="edit_name" 
                        name="name" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                    <span id="edit_name_error" style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: none;"></span>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_last_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Apellido</label>
                    <input 
                        type="text" 
                        id="edit_last_name" 
                        name="last_name" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                    <span id="edit_last_name_error" style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: none;"></span>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="edit_email" 
                        name="email" 
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                    <span id="edit_email_error" style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: none;"></span>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_phone" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Teléfono</label>
                    <input 
                        type="text" 
                        id="edit_phone" 
                        name="phone" 
                        required
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                    <span id="edit_phone_error" style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: none;"></span>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_card_number_id" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Cédula/Tarjeta de Identidad</label>
                    <input 
                        type="text" 
                        id="edit_card_number_id" 
                        name="card_number_id" 
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(57, 183, 127, 0.1)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                    <span id="edit_card_number_id_error" style="color: #c33; font-size: 0.85rem; margin-top: 0.25rem; display: none;"></span>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input 
                            type="checkbox" 
                            id="edit_status" 
                            name="status" 
                            value="1"
                            style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);"
                        >
                        <span style="font-weight: 600; color: var(--text-primary);">Cliente Activo</span>
                    </label>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button 
                        type="submit" 
                        style="background: var(--primary-color); color: white; padding: 0.75rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; flex: 1;"
                        onmouseover="this.style.background='var(--primary-dark)'; this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='var(--primary-color)'; this.style.transform='translateY(0)'"
                    >
                        Guardar Cambios
                    </button>
                    <button 
                        type="button" 
                        onclick="closeEditClientModal()"
                        style="background: var(--light-gray); color: var(--text-primary); padding: 0.75rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
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
        let currentDateFrom = '';
        let currentDateTo = '';
        let currentStepName = '';
        const perPage = 15;

        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');
        const stepNameFilter = document.getElementById('stepNameFilter');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const errorMessage = document.getElementById('errorMessage');
        const clientsTableBody = document.getElementById('clientsTableBody');
        const paginationContainer = document.getElementById('paginationContainer');

        // Verificar que todos los elementos existan
        if (!searchInput || !searchBtn || !dateFromInput || !dateToInput || !stepNameFilter || !loadingIndicator || !errorMessage || !clientsTableBody || !paginationContainer) {
            console.error('Error: No se encontraron todos los elementos necesarios del DOM');
            return;
        }

        // Función para cargar clientes
        function loadClients(page = 1, search = '', dateFrom = '', dateTo = '', stepName = '') {
            currentPage = page;
            currentSearch = search;
            currentDateFrom = dateFrom;
            currentDateTo = dateTo;
            currentStepName = stepName;
            
            loadingIndicator.style.display = 'block';
            errorMessage.style.display = 'none';
            clientsTableBody.innerHTML = '<tr><td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Cargando...</td></tr>';

            const params = new URLSearchParams({
                page: page,
                per_page: perPage
            });

            if (search) {
                params.append('search', search);
            }
            
            if (dateFrom) {
                params.append('date_from', dateFrom);
            }
            
            if (dateTo) {
                params.append('date_to', dateTo);
            }
            
            if (stepName) {
                params.append('step_name', stepName);
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
                    clientsTableBody.innerHTML = '<tr><td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No se encontraron clientes</td></tr>';
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

                const hasAccountBadge = client.has_account 
                ? '<span style="background: #39B77F; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">Sí</span>'
                : '<span style="background: #9ca3af; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">No</span>';

            return `
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 1rem; color: var(--text-secondary);">${client.id || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-primary); font-weight: 500;">${client.name || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-primary);">${client.last_name || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-secondary);">${client.email || '-'}</td>
                    <td style="padding: 1rem; color: var(--text-secondary);">${client.phone || '-'}</td>
                    <td style="padding: 1rem;">${statusBadge}</td>
                    <td style="padding: 1rem;">${hasAccountBadge}</td>
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

        // Escapar valores para evitar problemas con comillas
        const escapedSearch = currentSearch.replace(/'/g, "\\'");
        const escapedDateFrom = currentDateFrom.replace(/'/g, "\\'");
        const escapedDateTo = currentDateTo.replace(/'/g, "\\'");
        const escapedStepName = currentStepName.replace(/'/g, "\\'");

        // Botón Anterior
        if (data.current_page > 1) {
            paginationHTML += `
                <button 
                    onclick="loadClients(${data.current_page - 1}, '${escapedSearch}', '${escapedDateFrom}', '${escapedDateTo}', '${escapedStepName}')" 
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
            paginationHTML += `<button onclick="loadClients(1, '${escapedSearch}', '${escapedDateFrom}', '${escapedDateTo}', '${escapedStepName}')" style="padding: 0.5rem 0.75rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer;">1</button>`;
            if (startPage > 2) {
                paginationHTML += '<span style="padding: 0.5rem; color: var(--text-secondary);">...</span>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <button 
                    onclick="loadClients(${i}, '${escapedSearch}', '${escapedDateFrom}', '${escapedDateTo}', '${escapedStepName}')" 
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
            paginationHTML += `<button onclick="loadClients(${data.last_page}, '${escapedSearch}', '${escapedDateFrom}', '${escapedDateTo}', '${escapedStepName}')" style="padding: 0.5rem 0.75rem; border: 2px solid var(--border-color); background: white; border-radius: 6px; cursor: pointer;">${data.last_page}</button>`;
        }

        // Botón Siguiente
        if (data.current_page < data.last_page) {
            paginationHTML += `
                <button 
                    onclick="loadClients(${data.current_page + 1}, '${escapedSearch}', '${escapedDateFrom}', '${escapedDateTo}', '${escapedStepName}')" 
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
            clientsTableBody.innerHTML = '<tr><td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Error al cargar los clientes</td></tr>';
        }

        // Función para buscar
        function handleSearch() {
            const search = searchInput.value.trim();
            const dateFrom = dateFromInput.value;
            const dateTo = dateToInput.value;
            const stepName = stepNameFilter.value;
            loadClients(1, search, dateFrom, dateTo, stepName);
        }

        // Función para limpiar todos los filtros
        function clearFilters() {
            searchInput.value = '';
            dateFromInput.value = '';
            dateToInput.value = '';
            stepNameFilter.value = '';
            loadClients(1, '', '', '', '');
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
            const dateFrom = dateFromInput.value;
            const dateTo = dateToInput.value;
            const stepName = stepNameFilter.value;
            
            if (search) {
                params.append('search', search);
            }
            if (dateFrom) {
                params.append('date_from', dateFrom);
            }
            if (dateTo) {
                params.append('date_to', dateTo);
            }
            if (stepName) {
                params.append('step_name', stepName);
            }
            
            // Crear URL con parámetros y descargar
            const url = `/admin/clients/export${params.toString() ? '?' + params.toString() : ''}`;
            window.location.href = url;
            
            // Restaurar botón después de un breve delay
            setTimeout(() => {
                exportBtn.disabled = false;
                exportBtn.innerHTML = originalText;
            }, 3000);
        }

        // Event listeners
        searchBtn.addEventListener('click', handleSearch);
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
        }
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', exportToExcel);
        }
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });

        // Hacer funciones globales para los botones
        window.loadClients = loadClients;
        window.editClient = function(clientId) {
            window.openEditClientModal(clientId);
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

        // Variables para el modal de edición
        window.currentEditClientId = null;

        // Función para abrir el modal de edición (hacerla global)
        window.openEditClientModal = function(clientId) {
            window.currentEditClientId = clientId;
            const modal = document.getElementById('editClientModal');
            const modalLoading = document.getElementById('editClientModalLoading');
            const modalError = document.getElementById('editClientModalError');
            const modalForm = document.getElementById('editClientForm');

            modal.style.display = 'flex';
            modalLoading.style.display = 'block';
            modalError.style.display = 'none';
            modalForm.style.display = 'none';

            // Limpiar errores anteriores
            document.querySelectorAll('[id^="edit_"][id$="_error"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });

            // Cargar datos del cliente
            fetch(`/api/client/${clientId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                modalLoading.style.display = 'none';

                if (data.ok === false || !data.data) {
                    modalError.style.display = 'block';
                    modalError.textContent = data.message || 'Error al cargar los datos del cliente';
                    return;
                }

                const client = data.data;

                // Llenar el formulario
                document.getElementById('edit_name').value = client.name || '';
                document.getElementById('edit_last_name').value = client.last_name || '';
                document.getElementById('edit_email').value = client.email || '';
                document.getElementById('edit_phone').value = client.phone || '';
                document.getElementById('edit_card_number_id').value = client.card_number_id || '';
                document.getElementById('edit_status').checked = client.status === true || client.status === 1;

                modalForm.style.display = 'block';
            })
            .catch(error => {
                modalLoading.style.display = 'none';
                modalError.style.display = 'block';
                modalError.textContent = 'Error al cargar los datos del cliente: ' + error.message;
                console.error('Error:', error);
            });
        }

        // Función para cerrar el modal de edición
        window.closeEditClientModal = function() {
            document.getElementById('editClientModal').style.display = 'none';
            window.currentEditClientId = null;
        };

        // Función para guardar los cambios del cliente
        window.saveClientChanges = function() {
            if (!window.currentEditClientId) {
                return;
            }

            const modalError = document.getElementById('editClientModalError');
            const submitBtn = document.querySelector('#editClientForm button[type="submit"]');
            const originalText = submitBtn.textContent;

            // Limpiar errores anteriores
            modalError.style.display = 'none';
            document.querySelectorAll('[id^="edit_"][id$="_error"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });

            // Deshabilitar botón y mostrar loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

            const formData = {
                name: document.getElementById('edit_name').value,
                last_name: document.getElementById('edit_last_name').value,
                email: document.getElementById('edit_email').value,
                phone: document.getElementById('edit_phone').value,
                card_number_id: document.getElementById('edit_card_number_id').value,
                status: document.getElementById('edit_status').checked ? 1 : 0
            };

            fetch(`/api/client/${window.currentEditClientId}`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;

                if (data.ok === false) {
                    // Mostrar errores de validación
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.getElementById(`edit_${field}_error`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field][0];
                                errorElement.style.display = 'block';
                            }
                        });
                    } else {
                        modalError.style.display = 'block';
                        modalError.textContent = data.message || 'Error al actualizar el cliente';
                    }
                    return;
                }

                // Éxito: cerrar modal y recargar la tabla
                closeEditClientModal();
                loadClients(currentPage, currentSearch, currentDateFrom, currentDateTo, currentStepName);
                
                // Mostrar mensaje de éxito (opcional)
                const successMsg = document.createElement('div');
                successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #39B77F; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10001;';
                successMsg.textContent = 'Cliente actualizado exitosamente';
                document.body.appendChild(successMsg);
                setTimeout(() => {
                    successMsg.remove();
                }, 3000);
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                modalError.style.display = 'block';
                modalError.textContent = 'Error al actualizar el cliente: ' + error.message;
                console.error('Error:', error);
            });
        }

        // Registrar el evento del formulario de edición
        const editForm = document.getElementById('editClientForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                window.saveClientChanges();
            });
        }

        // Cerrar modal de edición al hacer clic fuera
        const existingOnClick = window.onclick;
        window.onclick = function(event) {
            // Ejecutar el handler existente si existe
            if (existingOnClick) {
                existingOnClick(event);
            }
            
            const editModal = document.getElementById('editClientModal');
            if (event.target === editModal) {
                window.closeEditClientModal();
            }
        };

        // Cargar clientes al iniciar
        loadClients(1, '', '', '', '');
    }
})();
</script>
@endpush
@endsection

