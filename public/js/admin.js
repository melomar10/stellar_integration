/**
 * Admin Panel JavaScript
 * Maneja la funcionalidad del sidebar, menús y componentes interactivos
 */

(function() {
    'use strict';

    // ============================================
    // Variables Globales
    // ============================================
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');

    // ============================================
    // Sidebar Toggle (Desktop)
    // ============================================
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            // Guardar estado en localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Restaurar estado del sidebar desde localStorage
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }

    // ============================================
    // Mobile Menu Toggle
    // ============================================
    if (mobileMenuToggle && sidebar && sidebarOverlay) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });

        // Cerrar sidebar al hacer clic en overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        // Cerrar sidebar al hacer clic en un enlace (móvil)
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    }

    // ============================================
    // User Dropdown Menu
    // ============================================
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuToggle.classList.toggle('active');
            userDropdown.classList.toggle('active');
        });

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userMenuToggle.classList.remove('active');
                userDropdown.classList.remove('active');
            }
        });
    }

    // ============================================
    // Responsive Sidebar Behavior
    // ============================================
    function handleResize() {
        if (window.innerWidth <= 1024) {
            // En móvil/tablet, siempre ocultar sidebar por defecto
            if (sidebar) {
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('active');
            }
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('active');
            }
        } else {
            // En desktop, restaurar estado del sidebar
            if (sidebar) {
                sidebar.classList.remove('active');
            }
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }
    }

    // Ejecutar al cargar y al redimensionar
    window.addEventListener('resize', handleResize);
    handleResize();

    // ============================================
    // Active Navigation Link Highlight
    // ============================================
    function highlightActiveNav() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            if (currentPath === linkPath || currentPath.startsWith(linkPath + '/')) {
                link.classList.add('active');
            }
        });
    }

    // Ejecutar al cargar
    highlightActiveNav();

    // ============================================
    // Smooth Scroll para contenido
    // ============================================
    const adminContent = document.querySelector('.admin-content');
    if (adminContent) {
        adminContent.style.scrollBehavior = 'smooth';
    }

    // ============================================
    // Prevenir comportamiento por defecto en enlaces activos
    // ============================================
    const activeLinks = document.querySelectorAll('.nav-link.active');
    activeLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.classList.contains('active')) {
                // Permitir navegación normal
                return true;
            }
        });
    });

    // ============================================
    // Keyboard Navigation Support
    // ============================================
    document.addEventListener('keydown', function(e) {
        // ESC para cerrar sidebar en móvil
        if (e.key === 'Escape') {
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
                document.body.style.overflow = '';
            }
            
            // ESC para cerrar dropdown
            if (userDropdown && userDropdown.classList.contains('active')) {
                userMenuToggle.classList.remove('active');
                userDropdown.classList.remove('active');
            }
        }
    });

    // ============================================
    // Console log para debugging (remover en producción)
    // ============================================
    console.log('Admin Panel JS loaded successfully');

})();

