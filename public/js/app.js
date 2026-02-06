/**
 * Sistema de Reservas de Salón
 * Client-side JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initAlerts();
    initFormValidation();
    initTableSearch();
});

/**
 * Auto-dismiss alerts after 5 seconds
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert-success, .alert-error');
    
    alerts.forEach(alert => {
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.style.cssText = 'background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-left: auto; opacity: 0.7;';
        closeBtn.onclick = () => fadeOut(alert);
        alert.appendChild(closeBtn);
        
        // Auto-dismiss after 5 seconds for success alerts
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => fadeOut(alert), 5000);
        }
    });
}

/**
 * Fade out and remove element
 */
function fadeOut(element) {
    element.style.transition = 'opacity 0.3s, transform 0.3s';
    element.style.opacity = '0';
    element.style.transform = 'translateY(-10px)';
    setTimeout(() => element.remove(), 300);
}

/**
 * Form validation enhancements
 */
function initFormValidation() {
    // Registration form password matching
    const confirmPassword = document.getElementById('confirm_password');
    const password = document.getElementById('password');
    
    if (confirmPassword && password) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Time validation for reservation form
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    
    if (startTime && endTime) {
        const validateTimes = () => {
            if (startTime.value && endTime.value) {
                if (startTime.value >= endTime.value) {
                    endTime.setCustomValidity('La hora de fin debe ser posterior a la hora de inicio');
                    endTime.style.borderColor = 'var(--accent-error)';
                } else {
                    endTime.setCustomValidity('');
                    endTime.style.borderColor = '';
                }
            }
        };
        
        startTime.addEventListener('change', validateTimes);
        endTime.addEventListener('change', validateTimes);
    }
    
    // Date validation - no past dates
    const reservationDate = document.getElementById('reservation_date');
    if (reservationDate) {
        reservationDate.min = new Date().toISOString().split('T')[0];
    }
}

/**
 * Simple table search functionality
 */
function initTableSearch() {
    const table = document.getElementById('reservationsTable');
    if (!table) return;
    
    // Create search input
    const searchContainer = document.createElement('div');
    searchContainer.style.marginBottom = '1rem';
    searchContainer.innerHTML = `
        <input 
            type="search" 
            id="tableSearch" 
            class="form-input" 
            placeholder="🔍 Buscar reservas..." 
            style="max-width: 300px;"
        >
    `;
    
    table.parentNode.insertBefore(searchContainer, table);
    
    const searchInput = document.getElementById('tableSearch');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

/**
 * Confirm delete actions
 */
function confirmDelete(message = '¿Está seguro de eliminar esta reserva?') {
    return confirm(message);
}

/**
 * Format date to locale string
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-DO', options);
}

/**
 * Format time to 12-hour format
 */
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}
