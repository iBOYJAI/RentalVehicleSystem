/**
 * Rental Vehicle Management System - Main JavaScript
 * All functionality in one offline file
 */

// Chart.js Library (Local Implementation)
class SimpleChart {
    constructor(canvas, type, data, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.type = type;
        this.data = data;
        this.options = {
            responsive: true,
            maintainAspectRatio: false,
            ...options
        };
        this.draw();
    }

    draw() {
        if (this.type === 'line') {
            this.drawLineChart();
        } else if (this.type === 'bar') {
            this.drawBarChart();
        } else if (this.type === 'doughnut') {
            this.drawDoughnutChart();
        }
    }

    drawLineChart() {
        const { labels, datasets } = this.data;
        const padding = 40;
        const width = this.canvas.width - padding * 2;
        const height = this.canvas.height - padding * 2;
        
        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Draw axes
        this.ctx.strokeStyle = '#e2e8f0';
        this.ctx.lineWidth = 1;
        this.ctx.beginPath();
        this.ctx.moveTo(padding, padding);
        this.ctx.lineTo(padding, height + padding);
        this.ctx.lineTo(width + padding, height + padding);
        this.ctx.stroke();
        
        // Draw data
        if (datasets && datasets.length > 0) {
            const dataset = datasets[0];
            const maxValue = Math.max(...dataset.data, 1);
            const stepX = width / (labels.length - 1 || 1);
            const stepY = height / maxValue;
            
            this.ctx.strokeStyle = dataset.borderColor || '#6366f1';
            this.ctx.lineWidth = 2;
            this.ctx.beginPath();
            
            labels.forEach((label, index) => {
                const x = padding + (index * stepX);
                const y = height + padding - (dataset.data[index] * stepY);
                
                if (index === 0) {
                    this.ctx.moveTo(x, y);
                } else {
                    this.ctx.lineTo(x, y);
                }
                
                // Draw point
                this.ctx.fillStyle = dataset.borderColor || '#6366f1';
                this.ctx.beginPath();
                this.ctx.arc(x, y, 4, 0, Math.PI * 2);
                this.ctx.fill();
            });
            
            this.ctx.stroke();
        }
    }

    drawBarChart() {
        const { labels, datasets } = this.data;
        const padding = 40;
        const width = this.canvas.width - padding * 2;
        const height = this.canvas.height - padding * 2;
        const barWidth = width / labels.length - 10;
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Draw axes
        this.ctx.strokeStyle = '#e2e8f0';
        this.ctx.lineWidth = 1;
        this.ctx.beginPath();
        this.ctx.moveTo(padding, padding);
        this.ctx.lineTo(padding, height + padding);
        this.ctx.lineTo(width + padding, height + padding);
        this.ctx.stroke();
        
        if (datasets && datasets.length > 0) {
            const dataset = datasets[0];
            const maxValue = Math.max(...dataset.data, 1);
            const stepY = height / maxValue;
            
            labels.forEach((label, index) => {
                const barHeight = dataset.data[index] * stepY;
                const x = padding + (index * (barWidth + 10)) + 5;
                const y = height + padding - barHeight;
                
                this.ctx.fillStyle = dataset.backgroundColor || '#6366f1';
                this.ctx.fillRect(x, y, barWidth, barHeight);
            });
        }
    }

    drawDoughnutChart() {
        const { labels, datasets } = this.data;
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 20;
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        if (datasets && datasets.length > 0) {
            const dataset = datasets[0];
            const total = dataset.data.reduce((a, b) => a + b, 0);
            let currentAngle = -Math.PI / 2;
            
            dataset.data.forEach((value, index) => {
                const sliceAngle = (value / total) * Math.PI * 2;
                
                this.ctx.beginPath();
                this.ctx.moveTo(centerX, centerY);
                this.ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
                this.ctx.closePath();
                this.ctx.fillStyle = dataset.backgroundColor[index] || '#6366f1';
                this.ctx.fill();
                
                currentAngle += sliceAngle;
            });
        }
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    initModals();
    
    // Initialize alerts
    initAlerts();
    
    // Initialize forms
    initForms();
    
    // Initialize tables
    initTables();
    
    // Initialize charts
    initCharts();
    
    // Initialize mobile menu
    initMobileMenu();
    
    // Initialize date pickers
    initDatePickers();
    
    // Initialize tooltips
    initTooltips();
});

// Modal Functions
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modalCloses = document.querySelectorAll('.modal-close, .modal-overlay');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            openModal(modalId);
        });
    });
    
    modalCloses.forEach(close => {
        close.addEventListener('click', function(e) {
            if (e.target === this || this.classList.contains('modal-close')) {
                closeModal(this.closest('.modal-overlay'));
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Alert Functions
function initAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });
}

function showAlert(type, message, container = '.content') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="icon-${type === 'success' ? 'check' : type === 'error' ? 'close' : 'info'}"></i>
        <span>${message}</span>
        <span class="alert-close">&times;</span>
    `;
    
    const containerEl = document.querySelector(container);
    if (containerEl) {
        containerEl.insertBefore(alertDiv, containerEl.firstChild);
        initAlerts();
    }
}

// Form Functions
function initForms() {
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-calculate rental cost
    const rentalForms = document.querySelectorAll('form[data-rental-calc]');
    rentalForms.forEach(form => {
        const startDate = form.querySelector('[name="start_date"]');
        const endDate = form.querySelector('[name="end_date"]');
        const dailyRate = form.querySelector('[name="daily_rate"]');
        const totalAmount = form.querySelector('[name="total_amount"]');
        const totalDays = form.querySelector('[name="total_days"]');
        
        if (startDate && endDate && dailyRate) {
            [startDate, endDate, dailyRate].forEach(input => {
                input.addEventListener('change', () => calculateRentalCost(form));
            });
        }
    });
    
    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const preview = document.querySelector(this.getAttribute('data-preview'));
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }
    });
    
    // Date validation
    const dateFields = form.querySelectorAll('input[type="date"]');
    dateFields.forEach(field => {
        if (field.value) {
            const date = new Date(field.value);
            if (isNaN(date.getTime())) {
                showFieldError(field, 'Please enter a valid date');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = message;
    field.parentElement.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorDiv = field.parentElement.querySelector('.form-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function calculateRentalCost(form) {
    const startDate = form.querySelector('[name="start_date"]').value;
    const endDate = form.querySelector('[name="end_date"]').value;
    const dailyRate = parseFloat(form.querySelector('[name="daily_rate"]').value) || 0;
    
    if (startDate && endDate && dailyRate > 0) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        const subtotal = dailyRate * diffDays;
        const tax = subtotal * 0.1; // 10% tax
        const total = subtotal + tax;
        
        const totalDaysField = form.querySelector('[name="total_days"]');
        const totalAmountField = form.querySelector('[name="total_amount"]');
        
        if (totalDaysField) totalDaysField.value = diffDays;
        if (totalAmountField) totalAmountField.value = total.toFixed(2);
        
        // Update display
        const displayFields = form.querySelectorAll('[data-display="total_days"], [data-display="total_amount"]');
        displayFields.forEach(field => {
            if (field.getAttribute('data-display') === 'total_days') {
                field.textContent = diffDays;
            } else if (field.getAttribute('data-display') === 'total_amount') {
                field.textContent = formatCurrency(total);
            }
        });
    }
}

// Table Functions
function initTables() {
    // Search functionality
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table-search');
            const table = document.getElementById(tableId);
            if (table) {
                filterTable(table, this.value);
            }
        });
    });
    
    // Sort functionality
    const sortHeaders = document.querySelectorAll('[data-sort]');
    sortHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const column = this.getAttribute('data-sort');
            sortTable(table, column);
        });
    });
}

function filterTable(table, searchTerm) {
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(table.querySelectorAll('th')).findIndex(th => th.getAttribute('data-sort') === column);
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex]?.textContent.trim() || '';
        const bText = b.cells[columnIndex]?.textContent.trim() || '';
        return aText.localeCompare(bText);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Chart Functions
function initCharts() {
    const chartCanvases = document.querySelectorAll('[data-chart]');
    chartCanvases.forEach(canvas => {
        const chartType = canvas.getAttribute('data-chart');
        const chartData = JSON.parse(canvas.getAttribute('data-chart-data') || '{}');
        new SimpleChart(canvas, chartType, chartData);
    });
}

// Mobile Menu
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
}

// Date Picker Enhancement
function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set min date to today
        if (!input.hasAttribute('data-allow-past')) {
            input.setAttribute('min', new Date().toISOString().split('T')[0]);
        }
    });
}

// Tooltip Functions
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = this.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: #1e293b;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 10000;
        pointer-events: none;
    `;
    document.body.appendChild(tooltip);
    
    const rect = this.getBoundingClientRect();
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
    
    this._tooltip = tooltip;
}

function hideTooltip() {
    if (this._tooltip) {
        this._tooltip.remove();
        this._tooltip = null;
    }
}

// Utility Functions
function formatCurrency(amount, currency = 'INR') {
    // Default to INR with rupee symbol for UI display
    let symbol = currency;
    switch (String(currency).toUpperCase()) {
        case 'INR':
            symbol = '₹';
            break;
        case 'USD':
            symbol = 'USD';
            break;
        case 'EUR':
            symbol = '€';
            break;
        case 'GBP':
            symbol = '£';
            break;
    }

    const value = isNaN(parseFloat(amount)) ? 0 : parseFloat(amount);
    return symbol + ' ' + value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// AJAX Helper
function ajaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    
    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                if (callback) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        callback(xhr.responseText);
                    }
                }
            } else {
                if (callback) {
                    callback({ success: false, message: 'Request failed' });
                }
            }
        }
    };
    
    if (data && method === 'POST') {
        const formData = new URLSearchParams(data).toString();
        xhr.send(formData);
    } else {
        xhr.send();
    }
}

// Confirm Delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Export functions for global use
window.RVMS = {
    openModal,
    closeModal,
    showAlert,
    formatCurrency,
    formatDate,
    ajaxRequest,
    confirmDelete
};

