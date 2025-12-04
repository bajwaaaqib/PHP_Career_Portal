// Career System - Main JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // File size validation
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['pdf', 'doc', 'docx'];
            
            if (this.files[0]) {
                // Check file size
                if (this.files[0].size > maxSize) {
                    showToast('File size must be less than 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Check file type
                const fileName = this.files[0].name.toLowerCase();
                const isValidType = allowedTypes.some(type => fileName.endsWith(type));
                
                if (!isValidType) {
                    showToast('Only PDF, DOC, and DOCX files are allowed', 'error');
                    this.value = '';
                } else {
                    showToast('File selected successfully', 'success');
                }
            }
        });
    });

    // Date validation
    const dobInput = document.getElementById('date_of_birth');
    if (dobInput) {
        const today = new Date().toISOString().split('T')[0];
        dobInput.max = today;
        
        // Calculate age
        dobInput.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age < 18) {
                showToast('You must be at least 18 years old', 'warning');
            }
        });
    }

    // Password strength checker
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('password-strength');
            
            if (!strengthMeter) return;
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const strengthColors = ['#dc3545', '#ffc107', '#fd7e14', '#20c997', '#28a745'];
            
            strengthMeter.textContent = strengthText[strength];
            strengthMeter.style.color = strengthColors[strength];
        });
    }

    // Form validation
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Mobile menu toggle
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    }

    // Table row click for mobile
    if (window.innerWidth < 768) {
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                const viewBtn = this.querySelector('.btn-outline-primary');
                if (viewBtn) {
                    viewBtn.click();
                }
            });
        });
    }

    // Status filter tabs
    const statusTabs = document.querySelectorAll('.status-tab');
    statusTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            statusTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const status = this.dataset.status;
            document.querySelectorAll('tbody tr').forEach(row => {
                if (!status || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId !== '#') {
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Copy to clipboard
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function() {
            const text = this.dataset.copy;
            navigator.clipboard.writeText(text).then(() => {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check"></i> Copied!';
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                }, 2000);
            });
        });
    });

    // Window resize handler
    window.addEventListener('resize', function() {
        if (window.innerWidth < 992 && sidebar && !sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('collapsed');
        }
    });

    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
});

// Toast notification function
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast show align-items-center border-0 bg-${type} text-white`;
    toast.style.marginBottom = '10px';
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi ${getToastIcon(type)} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
    `;
    document.body.appendChild(container);
    return container;
}

function getToastIcon(type) {
    const icons = {
        'success': 'bi-check-circle-fill',
        'error': 'bi-x-circle-fill',
        'warning': 'bi-exclamation-circle-fill',
        'info': 'bi-info-circle-fill'
    };
    return icons[type] || 'bi-info-circle-fill';
}

// Initialize charts
function initializeCharts() {
    const ctx = document.getElementById('applicationsChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Applications',
                    data: [12, 19, 15, 25, 22, 30],
                    borderColor: '#4a6bff',
                    backgroundColor: 'rgba(74, 107, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Debounce function for resize events
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

// Add CSS for animations
const globalStyles = `
    .fade-out {
        opacity: 0;
        transition: opacity 0.3s ease-out;
    }
    
    .sidebar.collapsed {
        width: var(--sidebar-collapsed);
    }
    
    .sidebar.collapsed .menu-item span,
    .sidebar.collapsed .user-name,
    .sidebar.collapsed .user-role {
        display: none;
    }
    
    .main-content.expanded {
        margin-left: var(--sidebar-collapsed);
    }
    
    .status-tab {
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .status-tab.active {
        background: rgba(74, 107, 255, 0.1);
        color: var(--primary-color);
        font-weight: 600;
    }
    
    @media (max-width: 992px) {
        .sidebar {
            position: fixed;
            left: -100%;
            transition: left 0.3s ease;
            z-index: 1050;
        }
        
        .sidebar.show {
            left: 0;
        }
        
        .main-content {
            margin-left: 0 !important;
        }
        
        .navbar-toggler {
            display: block;
        }
    }
`;

// Inject global styles
const styleSheet = document.createElement('style');
styleSheet.textContent = globalStyles;
document.head.appendChild(styleSheet);