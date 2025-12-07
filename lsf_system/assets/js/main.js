/**
 * Lost & Found System - Main JavaScript
 * AJAX helpers, form handlers, and utility functions
 */

// Base API URL
const API_BASE = '/lsf_system/api'; // Adjust based on your setup

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
     * Show alert message
     */
    showAlert(message, type = 'success') {
        // Check if we're using Bootstrap alerts
        const container = document.getElementById('alertContainer') || document.querySelector('main');

        if (!container) return;

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        if (document.getElementById('alertContainer')) {
            container.innerHTML = '';
            container.appendChild(alertDiv);
        } else {
            container.insertBefore(alertDiv, container.firstChild);
        }

        // Scroll to alert
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Auto-remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },

    /**
     * Show confirmation dialog
     */
    confirm(message) {
        return window.confirm(message);
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
        if (!UI.confirm(`Are you sure you want to delete this ${type} item?`)) {
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
        if (!UI.confirm('Mark this claim as completed?')) {
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