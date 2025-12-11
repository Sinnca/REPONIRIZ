/**
 * Lost & Found System - Main JavaScript
 * AJAX helpers, form handlers, and utility functions with modern modal alerts
 */

// Base API URL
const API_BASE = '/lsf_system/api'; // Adjust based on your setup

// Enhanced Modal Manager for all alerts and confirmations
const EnhancedModalManager = {
    /**
     * Create modal HTML structure if it doesn't exist
     */
    initModal() {
        if (document.getElementById('enhancedModal')) return;

        const modalHTML = `
            <div id="enhancedModal" class="enhanced-modal-overlay">
                <div class="enhanced-modal">
                    <div class="enhanced-modal-header">
                        <div class="enhanced-modal-icon" id="enhancedModalIcon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3 class="enhanced-modal-title" id="enhancedModalTitle">Success</h3>
                    </div>
                    <div class="enhanced-modal-body">
                        <p id="enhancedModalMessage"></p>
                    </div>
                    <div class="enhanced-modal-footer" id="enhancedModalFooter">
                        <button class="enhanced-modal-btn enhanced-modal-btn-primary" onclick="EnhancedModalManager.closeModal()">
                            <i class="bi bi-check-circle me-1"></i>OK
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.addModalStyles();
        this.loadBootstrapIcons();
    },

    /**
     * Load Bootstrap Icons if not already loaded
     */
    loadBootstrapIcons() {
        if (document.getElementById('bootstrapIconsLink')) return;
        
        const link = document.createElement('link');
        link.id = 'bootstrapIconsLink';
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css';
        document.head.appendChild(link);
    },

    /**
     * Add modal styles
     */
    addModalStyles() {
        if (document.getElementById('enhancedModalStyles')) return;

        const styles = `
            <style id="enhancedModalStyles">
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Space+Grotesk:wght@700;800&display=swap');

                .enhanced-modal-overlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.65);
                    backdrop-filter: blur(5px);
                    z-index: 10000;
                    animation: fadeIn 0.25s ease;
                    padding: 1rem;
                    overflow-y: auto;
                }

                .enhanced-modal-overlay.show {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .enhanced-modal {
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
                    max-width: 550px;
                    width: 100%;
                    animation: slideUp 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
                    border-top: 5px solid #0066FF;
                }

                .enhanced-modal-header {
                    padding: 2.5rem 2.5rem 1.5rem;
                    text-align: center;
                    border-bottom: 2px solid #E5E7EB;
                }

                .enhanced-modal-icon {
                    width: 90px;
                    height: 90px;
                    margin: 0 auto 1.5rem;
                    background: linear-gradient(135deg, #0066FF 0%, #0052CC 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
                    box-shadow: 0 8px 20px rgba(0, 102, 255, 0.3);
                }

                .enhanced-modal-icon.success {
                    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
                    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
                }

                .enhanced-modal-icon.error {
                    background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
                    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
                }

                .enhanced-modal-icon.warning {
                    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
                    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
                }

                .enhanced-modal-icon.confirm {
                    background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
                    box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
                }

                .enhanced-modal-icon i {
                    font-size: 2.8rem;
                    color: white;
                }

                .enhanced-modal-title {
                    font-family: 'Space Grotesk', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    font-size: 1.6rem;
                    font-weight: 800;
                    color: #002D72;
                    margin: 0;
                    text-transform: uppercase;
                    letter-spacing: -0.02em;
                }

                .enhanced-modal-body {
                    padding: 2rem 2.5rem;
                    text-align: center;
                }

                .enhanced-modal-body p {
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    font-size: 1.1rem;
                    line-height: 1.8;
                    color: #6B7280;
                    margin: 0;
                    font-weight: 500;
                }

                .enhanced-modal-footer {
                    padding: 1.5rem 2.5rem 2.5rem;
                    text-align: center;
                    display: flex;
                    gap: 1rem;
                    justify-content: center;
                }

                .enhanced-modal-btn {
                    border: none;
                    padding: 1rem 2.5rem;
                    font-size: 0.95rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 140px;
                }

                .enhanced-modal-btn-primary {
                    background: linear-gradient(135deg, #0066FF 0%, #0052CC 100%);
                    color: white;
                    box-shadow: 0 4px 14px rgba(0, 102, 255, 0.35);
                }

                .enhanced-modal-btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 24px rgba(0, 102, 255, 0.45);
                    background: linear-gradient(135deg, #0052CC 0%, #003D99 100%);
                }

                .enhanced-modal-btn-secondary {
                    background: white;
                    color: #6B7280;
                    border: 2px solid #E5E7EB;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }

                .enhanced-modal-btn-secondary:hover {
                    transform: translateY(-2px);
                    background: #F9FAFB;
                    border-color: #D1D5DB;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }

                .enhanced-modal-btn:active {
                    transform: translateY(0);
                }

                @keyframes fadeIn {
                    from {
                        opacity: 0;
                    }
                    to {
                        opacity: 1;
                    }
                }

                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(40px) scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }

                @keyframes scaleIn {
                    from {
                        transform: scale(0);
                        opacity: 0;
                    }
                    to {
                        transform: scale(1);
                        opacity: 1;
                    }
                }

                @keyframes pulse {
                    0%, 100% {
                        box-shadow: 0 8px 20px rgba(0, 102, 255, 0.3);
                    }
                    50% {
                        box-shadow: 0 8px 30px rgba(0, 102, 255, 0.5);
                    }
                }

                .enhanced-modal-icon {
                    animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), pulse 2s ease-in-out infinite 0.5s;
                }

                /* Responsive */
                @media (max-width: 576px) {
                    .enhanced-modal {
                        margin: 1rem;
                        max-width: calc(100% - 2rem);
                        border-radius: 12px;
                    }

                    .enhanced-modal-header {
                        padding: 2rem 1.5rem 1.2rem;
                    }

                    .enhanced-modal-icon {
                        width: 75px;
                        height: 75px;
                        margin-bottom: 1.2rem;
                    }

                    .enhanced-modal-icon i {
                        font-size: 2.2rem;
                    }

                    .enhanced-modal-title {
                        font-size: 1.4rem;
                    }

                    .enhanced-modal-body {
                        padding: 1.5rem;
                    }

                    .enhanced-modal-body p {
                        font-size: 1rem;
                    }

                    .enhanced-modal-footer {
                        padding: 1.2rem 1.5rem 2rem;
                        flex-direction: column;
                    }

                    .enhanced-modal-btn {
                        width: 100%;
                        padding: 0.9rem 2rem;
                        min-width: unset;
                    }
                }

                /* Loading spinner */
                .spinner {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    border-top-color: white;
                    border-radius: 50%;
                    animation: spin 0.6s linear infinite;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    },

    /**
     * Show modal with message
     */
    showModal(message, type = 'success', title = null) {
        this.initModal();

        const modal = document.getElementById('enhancedModal');
        const messageEl = document.getElementById('enhancedModalMessage');
        const iconEl = document.getElementById('enhancedModalIcon');
        const titleEl = document.getElementById('enhancedModalTitle');
        const footerEl = document.getElementById('enhancedModalFooter');

        messageEl.textContent = message;
        iconEl.className = `enhanced-modal-icon ${type}`;

        // Set icon and title based on type
        let icon = 'bi-check-circle';
        let modalTitle = title || 'Success';

        switch(type) {
            case 'success':
                icon = 'bi-check-circle';
                modalTitle = title || 'Success';
                break;
            case 'error':
                icon = 'bi-x-circle';
                modalTitle = title || 'Error';
                break;
            case 'warning':
                icon = 'bi-exclamation-triangle';
                modalTitle = title || 'Warning';
                break;
            case 'info':
                icon = 'bi-info-circle';
                modalTitle = title || 'Information';
                break;
        }

        iconEl.querySelector('i').className = `bi ${icon}`;
        titleEl.textContent = modalTitle;

        // Reset footer to single button
        footerEl.innerHTML = `
            <button class="enhanced-modal-btn enhanced-modal-btn-primary" onclick="EnhancedModalManager.closeModal()">
                <i class="bi bi-check-circle me-1"></i>OK
            </button>
        `;

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Close on overlay click
        modal.onclick = (e) => {
            if (e.target === modal) {
                this.closeModal();
            }
        };

        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    },

    /**
     * Show confirmation dialog
     */
    showConfirm(message, onConfirm, onCancel = null) {
        this.initModal();

        const modal = document.getElementById('enhancedModal');
        const messageEl = document.getElementById('enhancedModalMessage');
        const iconEl = document.getElementById('enhancedModalIcon');
        const titleEl = document.getElementById('enhancedModalTitle');
        const footerEl = document.getElementById('enhancedModalFooter');

        messageEl.textContent = message;
        iconEl.className = 'enhanced-modal-icon confirm';
        iconEl.querySelector('i').className = 'bi bi-question-circle';
        titleEl.textContent = 'Confirm Action';

        // Two-button footer for confirmation
        footerEl.innerHTML = `
            <button class="enhanced-modal-btn enhanced-modal-btn-secondary" id="confirmCancelBtn">
                <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
            <button class="enhanced-modal-btn enhanced-modal-btn-primary" id="confirmOkBtn">
                <i class="bi bi-check-circle me-1"></i>Confirm
            </button>
        `;

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Handle buttons
        document.getElementById('confirmCancelBtn').onclick = () => {
            this.closeModal();
            if (onCancel) onCancel();
        };

        document.getElementById('confirmOkBtn').onclick = () => {
            this.closeModal();
            if (onConfirm) onConfirm();
        };

        // Close on overlay click
        modal.onclick = (e) => {
            if (e.target === modal) {
                this.closeModal();
                if (onCancel) onCancel();
            }
        };

        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                if (onCancel) onCancel();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    },

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('enhancedModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
};

// AJAX Helper Functions
const API = {
    /**
     * Generic AJAX request handler
     */
    async request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API Request Error:', error);
            return {
                success: false,
                message: 'Network error. Please try again.'
            };
        }
    },

    /**
     * POST request helper
     */
    async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    /**
     * GET request helper
     */
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(fullUrl);
    },

    /**
     * Upload file with FormData
     */
    async upload(url, formData) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('Upload Error:', error);
            return {
                success: false,
                message: 'Upload failed. Please try again.'
            };
        }
    }
};

// UI Helper Functions
const UI = {
    /**
     * Show loading spinner
     */
    showLoading(element) {
        if (element) {
            element.disabled = true;
            element.innerHTML = '<span class="spinner"></span> Loading...';
        }
    },

    /**
     * Hide loading spinner
     */
    hideLoading(element, originalText) {
        if (element) {
            element.disabled = false;
            element.innerHTML = originalText;
        }
    },

    /**
     * Show alert message using modal
     */
    showAlert(message, type = 'success') {
        EnhancedModalManager.showModal(message, type);
    },

    /**
     * Show confirmation dialog using modal
     */
    confirm(message) {
        return new Promise((resolve) => {
            EnhancedModalManager.showConfirm(
                message,
                () => resolve(true),
                () => resolve(false)
            );
        });
    },

    /**
     * Scroll to top
     */
    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

// Form Handlers
const FormHandlers = {
    /**
     * Handle lost item submission
     */
    async submitLostItem(formElement) {
        const formData = new FormData(formElement);
        const submitBtn = formElement.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        UI.showLoading(submitBtn);

        const result = await API.upload(`${API_BASE}/items/create_lost.php`, formData);

        UI.hideLoading(submitBtn, originalText);

        if (result.success) {
            UI.showAlert(result.message, 'success');
            formElement.reset();
            setTimeout(() => {
                window.location.href = '/lsf_system/student/index.php';
            }, 2000);
        } else {
            UI.showAlert(result.message, 'error');
        }
    },

    /**
     * Handle found item submission
     */
    async submitFoundItem(formElement) {
        const formData = new FormData(formElement);
        const submitBtn = formElement.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        UI.showLoading(submitBtn);

        const result = await API.upload(`${API_BASE}/items/create_found.php`, formData);

        UI.hideLoading(submitBtn, originalText);

        if (result.success) {
            UI.showAlert(result.message, 'success');
            formElement.reset();
            setTimeout(() => {
                window.location.href = '/lsf_system/student/index.php';
            }, 2000);
        } else {
            UI.showAlert(result.message, 'error');
        }
    },

    /**
     * Handle claim request submission
     */
    async submitClaimRequest(lostItemId, notes, foundItemId = null) {
        const data = {
            notes: notes
        };

        if (foundItemId) {
            data.found_item_id = foundItemId;
        } else if (lostItemId) {
            data.lost_item_id = lostItemId;
        }

        const result = await API.post(`${API_BASE}/claims/create_claim.php`, data);

        if (result.success) {
            UI.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = '/lsf_system/student/index.php';
            }, 2000);
        } else {
            UI.showAlert(result.message, 'error');
        }
    },

    /**
     * Delete item
     */
    async deleteItem(itemId, type) {
        const confirmed = await UI.confirm(`Are you sure you want to delete this ${type} item?`);
        if (!confirmed) {
            return;
        }

        const result = await API.post(`${API_BASE}/items/delete_item.php`, {
            item_id: itemId,
            type: type
        });

        if (result.success) {
            UI.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            UI.showAlert(result.message, 'error');
        }
    }
};

// Admin Functions
const AdminActions = {
    /**
     * Verify item (approve/reject)
     */
    async verifyItem(itemId, type, action, reason = '') {
        const result = await API.post(`${API_BASE}/admin/verify_item.php`, {
            item_id: itemId,
            type: type,
            action: action,
            reason: reason
        });

        if (result.success) {
            UI.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = `/lsf_system/admin/pending_${type}.php`;
            }, 1500);
        } else {
            UI.showAlert(result.message, 'error');
        }
    },

    /**
     * Approve claim
     */
    async approveClaim(claimId, scheduleDate, scheduleTime, notes = '') {
        const result = await API.post(`${API_BASE}/admin/approve_claim.php`, {
            claim_id: claimId,
            action: 'approve',
            schedule_date: scheduleDate,
            schedule_time: scheduleTime,
            notes: notes
        });

        if (result.success) {
            UI.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = '/lsf_system/admin/claim_requests.php';
            }, 1500);
        } else {
            UI.showAlert(result.message, 'error');
        }
    },

    /**
     * Reject claim
     */
    async rejectClaim(claimId, reason) {
        const result = await API.post(`${API_BASE}/admin/approve_claim.php`, {
            claim_id: claimId,
            action: 'reject',
            reason: reason
        });

        if (result.success) {
            UI.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = '/lsf_system/admin/claim_requests.php';
            }, 1500);
        } else {
            UI.showAlert(result.message, 'error');
        }
    },

    /**
     * Complete claim
     */
    async completeClaim(claimId) {
        const confirmed = await UI.confirm('Mark this claim as completed?');
        if (!confirmed) {
            return;
        }

        const result = await API.post(`${API_BASE}/admin/approve_claim.php`, {
            claim_id: claimId,
            action: 'complete'
        });

        if (result.success) {
            UI.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = '/lsf_system/admin/claim_requests.php';
            }, 1500);
        } else {
            UI.showAlert(result.message, 'error');
        }
    }
};

// Notifications
const Notifications = {
    unreadCount: 0,

    /**
     * Fetch notifications
     */
    async fetch(unreadOnly = false) {
        const result = await API.get(`${API_BASE}/notifications/get_notifications.php`, {
            unread_only: unreadOnly,
            limit: 10
        });

        if (result.success) {
            this.unreadCount = result.unread_count;
            this.updateBadge();
            return result.notifications;
        }
        return [];
    },

    /**
     * Mark as read
     */
    async markAsRead(notificationId = null, markAll = false) {
        const result = await API.post(`${API_BASE}/notifications/mark_read.php`, {
            notification_id: notificationId,
            mark_all: markAll
        });

        if (result.success) {
            this.fetch(true); // Refresh unread count
        }
    },

    /**
     * Update notification badge
     */
    updateBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'inline' : 'none';
        }
    },

    /**
     * Auto-refresh notifications
     */
    startAutoRefresh(intervalMs = 30000) {
        setInterval(() => {
            this.fetch(true);
        }, intervalMs);
    }
};

// Image Preview
function previewImage(input, previewElement) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewElement.src = e.target.result;
            previewElement.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal system
    EnhancedModalManager.initModal();

    // Start notification auto-refresh if user is logged in
    if (document.querySelector('.notification-badge')) {
        Notifications.fetch(true);
        Notifications.startAutoRefresh();
    }

    // Setup image preview
    const photoInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    photoInputs.forEach(input => {
        input.addEventListener('change', function() {
            const preview = document.getElementById('image-preview');
            if (preview) {
                previewImage(this, preview);
            }
        });
    });
});

// Export for use in other scripts
window.LSF = {
    API,
    UI,
    FormHandlers,
    AdminActions,
    Notifications,
    previewImage
};

window.EnhancedModalManager = EnhancedModalManager;