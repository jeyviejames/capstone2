// Dormitory Management System - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
});

// Initialize all components
function initializeComponents() {
    initializeDropdowns();
    initializeModals();
    initializeForms();
    initializeAlerts();
    initializeSidebar();
    initializeDataTables();
}

// Dropdown functionality
function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.user-dropdown');
    
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.user-info');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (trigger && menu) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAllDropdowns();
                menu.classList.toggle('show');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', closeAllDropdowns);
}

function closeAllDropdowns() {
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    dropdownMenus.forEach(menu => menu.classList.remove('show'));
}

// Modal functionality
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.modal-close');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => closeModal(modal));
        }
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
    
    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    if (typeof modal === 'string') {
        modal = document.getElementById(modal);
    }
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Form validation and handling
function initializeForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearFieldError(input));
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.getAttribute('name') || field.getAttribute('id');
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        errorMessage = 'This field is required';
        isValid = false;
    }
    
    // Email validation
    else if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            errorMessage = 'Please enter a valid email address';
            isValid = false;
        }
    }
    
    // Phone number validation
    else if (field.type === 'tel' && value) {
        const phoneRegex = /^[0-9]{10,15}$/;
        if (!phoneRegex.test(value.replace(/\D/g, ''))) {
            errorMessage = 'Please enter a valid phone number';
            isValid = false;
        }
    }
    
    // Student ID validation (6 digits)
    else if (fieldName === 'student_id' && value) {
        if (!/^\d{6}$/.test(value)) {
            errorMessage = 'Student ID must be exactly 6 digits';
            isValid = false;
        }
    }
    
    // LRN validation (12 digits)
    else if (fieldName === 'lrn' && value) {
        if (!/^\d{12}$/.test(value)) {
            errorMessage = 'LRN must be exactly 12 digits';
            isValid = false;
        }
    }
    
    // Password validation
    else if (field.type === 'password' && value) {
        if (value.length < 6) {
            errorMessage = 'Password must be at least 6 characters long';
            isValid = false;
        }
    }
    
    // Password confirmation
    else if (fieldName === 'confirm_password' && value) {
        const passwordField = document.querySelector('[name="password"]');
        if (passwordField && value !== passwordField.value) {
            errorMessage = 'Passwords do not match';
            isValid = false;
        }
    }
    
    // Show/hide error message
    if (isValid) {
        clearFieldError(field);
    } else {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Alert functionality
function initializeAlerts() {
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    
    alerts.forEach(alert => {
        const delay = parseInt(alert.getAttribute('data-auto-dismiss')) || 5000;
        setTimeout(() => {
            dismissAlert(alert);
        }, delay);
    });
    
    // Add close buttons to alerts
    const alertsWithoutClose = document.querySelectorAll('.alert:not(.alert-dismissible)');
    alertsWithoutClose.forEach(alert => {
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = () => dismissAlert(alert);
        alert.appendChild(closeBtn);
    });
}

function showAlert(type, message, autoHide = true) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade-in`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="dismissAlert(this.parentElement)">&times;</button>
    `;
    
    const container = document.querySelector('.content') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    if (autoHide) {
        setTimeout(() => dismissAlert(alertDiv), 5000);
    }
}

function dismissAlert(alert) {
    if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    }
}

// Sidebar functionality
function initializeSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Set active navigation item
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Data table functionality
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        addTableFeatures(table);
    });
}

function addTableFeatures(table) {
    // Add search functionality
    const searchInput = table.parentElement.querySelector('.table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterTable(table, this.value);
        });
    }
    
    // Add sorting to headers
    const headers = table.querySelectorAll('th[data-sortable]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            sortTable(table, this);
        });
    });
    
    // Add pagination if needed
    const paginationContainer = table.parentElement.querySelector('.pagination-container');
    if (paginationContainer) {
        initializePagination(table, paginationContainer);
    }
}

function filterTable(table, searchTerm) {
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

function sortTable(table, header) {
    const index = Array.from(header.parentElement.children).indexOf(header);
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const isNumeric = header.hasAttribute('data-type') && header.getAttribute('data-type') === 'number';
    const isAscending = !header.classList.contains('sort-asc');
    
    // Remove existing sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add new sort class
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    
    rows.sort((a, b) => {
        const aValue = a.children[index].textContent.trim();
        const bValue = b.children[index].textContent.trim();
        
        let comparison = 0;
        if (isNumeric) {
            comparison = parseFloat(aValue) - parseFloat(bValue);
        } else {
            comparison = aValue.localeCompare(bValue);
        }
        
        return isAscending ? comparison : -comparison;
    });
    
    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));
}

// AJAX helper functions
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    if (mergedOptions.body && typeof mergedOptions.body === 'object') {
        mergedOptions.body = JSON.stringify(mergedOptions.body);
    }
    
    return fetch(url, mergedOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            showAlert('danger', 'An error occurred. Please try again.');
            throw error;
        });
}

function submitForm(form, successCallback = null) {
    const formData = new FormData(form);
    const url = form.getAttribute('action') || window.location.href;
    
    // Show loading state
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (successCallback) {
                successCallback(data);
            } else if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            showAlert('danger', data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Form submission failed:', error);
        showAlert('danger', 'An error occurred. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Utility functions
function formatDate(date, format = 'Y-m-d') {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    switch (format) {
        case 'Y-m-d':
            return `${year}-${month}-${day}`;
        case 'd/m/Y':
            return `${day}/${month}/${year}`;
        case 'M d, Y':
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${months[date.getMonth()]} ${day}, ${year}`;
        default:
            return date.toLocaleDateString();
    }
}

function formatTime(date) {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }
    return date.toLocaleTimeString();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Page refresh helper
function refreshPage() {
    window.location.reload();
}

// Confirmation dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// File upload helper
function handleFileUpload(input, allowedTypes = [], maxSize = 5242880) { // 5MB default
    const file = input.files[0];
    if (!file) return true;
    
    // Check file type
    if (allowedTypes.length > 0) {
        const fileType = file.type.toLowerCase();
        const fileName = file.name.toLowerCase();
        const isAllowed = allowedTypes.some(type => 
            fileType.includes(type) || fileName.endsWith(type)
        );
        
        if (!isAllowed) {
            showAlert('danger', `Please select a valid file type: ${allowedTypes.join(', ')}`);
            input.value = '';
            return false;
        }
    }
    
    // Check file size
    if (file.size > maxSize) {
        const maxSizeMB = (maxSize / 1024 / 1024).toFixed(1);
        showAlert('danger', `File size must be less than ${maxSizeMB}MB`);
        input.value = '';
        return false;
    }
    
    return true;
}

// Auto-refresh functionality for real-time updates
function startAutoRefresh(interval = 30000) {
    setInterval(() => {
        // Only refresh if the page is visible
        if (!document.hidden) {
            // Refresh specific data without full page reload
            refreshDashboardData();
        }
    }, interval);
}

function refreshDashboardData() {
    // This would be implemented based on specific dashboard needs
    const dashboardElements = document.querySelectorAll('[data-auto-refresh]');
    dashboardElements.forEach(element => {
        const url = element.getAttribute('data-refresh-url');
        if (url) {
            makeRequest(url)
                .then(data => {
                    element.innerHTML = data.html;
                })
                .catch(() => {
                    // Silently fail for auto-refresh
                });
        }
    });
}

// Export functions for global use
window.DormitorySystem = {
    openModal,
    closeModal,
    showAlert,
    dismissAlert,
    makeRequest,
    submitForm,
    formatDate,
    formatTime,
    confirmAction,
    handleFileUpload,
    refreshPage
};