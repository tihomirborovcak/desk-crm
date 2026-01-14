/**
 * Portal CMS - JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    initMobileNav();
    initModals();
    initUpload();
    initAlerts();
    initForms();
});

/**
 * Mobile Navigation
 */
function initMobileNav() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileNav = document.querySelector('.mobile-nav');
    const navOverlay = document.querySelector('.nav-overlay');
    const navClose = document.querySelector('.nav-close');
    
    if (!menuToggle || !mobileNav) return;
    
    function openNav() {
        mobileNav.classList.add('active');
        navOverlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeNav() {
        mobileNav.classList.remove('active');
        navOverlay?.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    menuToggle.addEventListener('click', openNav);
    navClose?.addEventListener('click', closeNav);
    navOverlay?.addEventListener('click', closeNav);
    
    // Close on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeNav();
    });
    
    // Close on swipe left
    let touchStartX = 0;
    mobileNav.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
    });
    
    mobileNav.addEventListener('touchend', function(e) {
        const touchEndX = e.changedTouches[0].clientX;
        if (touchStartX - touchEndX > 50) {
            closeNav();
        }
    });
}

/**
 * Modal handling
 */
function initModals() {
    // Open modal
    document.querySelectorAll('[data-modal]').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            openModal(modalId);
        });
    });
    
    // Close modal
    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            closeModal(this.closest('.modal'));
        });
    });
    
    // Close on overlay click (only if click started and ended on overlay)
    document.querySelectorAll('.modal').forEach(function(modal) {
        let clickStartedOnOverlay = false;
        
        modal.addEventListener('mousedown', function(e) {
            clickStartedOnOverlay = (e.target === this);
        });
        
        modal.addEventListener('mouseup', function(e) {
            if (clickStartedOnOverlay && e.target === this) {
                closeModal(this);
            }
            clickStartedOnOverlay = false;
        });
    });
    
    // Close on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.active');
            if (activeModal) closeModal(activeModal);
        }
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

/**
 * File Upload
 */
function initUpload() {
    const uploadAreas = document.querySelectorAll('.upload-area');
    
    uploadAreas.forEach(function(area) {
        const input = area.querySelector('input[type="file"]');
        if (!input) return;
        
        // Click to upload
        area.addEventListener('click', function() {
            input.click();
        });
        
        // Drag and drop
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
        
        // Preview on select
        input.addEventListener('change', function() {
            if (this.files.length) {
                showUploadPreview(area, this.files);
            }
        });
    });
}

function showUploadPreview(area, files) {
    const preview = area.querySelector('.upload-preview') || document.createElement('div');
    preview.className = 'upload-preview';
    preview.innerHTML = '';
    
    Array.from(files).forEach(function(file) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '100px';
                img.style.maxHeight = '100px';
                img.style.margin = '5px';
                img.style.borderRadius = '4px';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        } else {
            const span = document.createElement('span');
            span.textContent = file.name;
            span.style.display = 'block';
            span.style.padding = '5px';
            preview.appendChild(span);
        }
    });
    
    if (!area.querySelector('.upload-preview')) {
        area.appendChild(preview);
    }
}

/**
 * Auto-dismiss alerts
 */
function initAlerts() {
    document.querySelectorAll('.alert[data-dismiss]').forEach(function(alert) {
        const timeout = parseInt(alert.dataset.dismiss) || 5000;
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, timeout);
    });
}

/**
 * Form enhancements
 */
function initForms() {
    // Character counter for textareas
    document.querySelectorAll('textarea[maxlength]').forEach(function(textarea) {
        const maxLength = textarea.maxLength;
        const counter = document.createElement('div');
        counter.className = 'form-text text-right';
        counter.textContent = `0 / ${maxLength}`;
        textarea.parentNode.appendChild(counter);
        
        textarea.addEventListener('input', function() {
            counter.textContent = `${this.value.length} / ${maxLength}`;
        });
    });
    
    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-slug generation
    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.querySelector('input[name="slug"]');
    
    if (titleInput && slugInput && !slugInput.value) {
        titleInput.addEventListener('input', function() {
            slugInput.value = slugify(this.value);
        });
    }
}

/**
 * Generate URL-friendly slug
 */
function slugify(text) {
    const hr = {'č':'c', 'ć':'c', 'đ':'d', 'š':'s', 'ž':'z', 
                'Č':'c', 'Ć':'c', 'Đ':'d', 'Š':'s', 'Ž':'z'};
    
    text = text.toLowerCase();
    
    for (let key in hr) {
        text = text.replace(new RegExp(key, 'g'), hr[key]);
    }
    
    return text
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * AJAX helper
 */
function ajax(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    };
    
    options = {...defaults, ...options};
    
    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        });
}

/**
 * Toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        max-width: 90%;
        animation: slideUp 0.3s ease;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Loading state
 */
function setLoading(element, loading = true) {
    if (loading) {
        element.dataset.originalText = element.innerHTML;
        element.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span>';
        element.disabled = true;
    } else {
        element.innerHTML = element.dataset.originalText;
        element.disabled = false;
    }
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * Format date for display
 */
function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString('hr-HR');
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}
