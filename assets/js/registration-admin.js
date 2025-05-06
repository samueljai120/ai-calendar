class RegistrationAdmin {
    constructor() {
        // Initialize properties
        this.table = document.querySelector('.wp-list-table');
        this.bulkActions = document.getElementById('bulk-action-selector-top');
        this.searchInput = document.querySelector('.search-box input[name="s"]');
        this.filterDropdowns = document.querySelectorAll('.tablenav select');
        this.statusCells = document.querySelectorAll('.column-status');
        
        // Initialize features
        this.initializeFeatures();
    }

    initializeFeatures() {
        // Add quick status toggle
        this.initQuickStatusToggle();
        
        // Enhanced bulk actions
        this.initBulkActions();
        
        // Live search
        this.initLiveSearch();
        
        // Filter enhancements
        this.initFilterEnhancements();
        
        // Row hover actions
        this.initRowActions();
        
        // Statistics summary
        this.initStatsSummary();
    }

    initQuickStatusToggle() {
        this.statusCells.forEach(cell => {
            const status = cell.querySelector('.registration-status');
            if (!status) return;

            // Create status menu
            const menu = document.createElement('div');
            menu.className = 'status-quick-menu';
            menu.innerHTML = `
                <div class="status-option" data-status="pending">Pending</div>
                <div class="status-option" data-status="confirmed">Confirmed</div>
                <div class="status-option" data-status="cancelled">Cancelled</div>
            `;

            // Add click handler
            status.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                
                // Position menu
                const rect = status.getBoundingClientRect();
                menu.style.top = rect.bottom + 'px';
                menu.style.left = rect.left + 'px';
            });

            // Handle status change
            menu.addEventListener('click', async (e) => {
                const option = e.target.closest('.status-option');
                if (!option) return;

                const newStatus = option.dataset.status;
                const row = cell.closest('tr');
                const registrationId = row.id.replace('post-', '');

                try {
                    await this.updateRegistrationStatus(registrationId, newStatus);
                    status.className = `registration-status status-${newStatus}`;
                    status.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    menu.style.display = 'none';
                    
                    // Show success notification
                    this.showNotification('success', 'Status updated successfully');
                    
                    // Update stats
                    this.updateStatsSummary();
                } catch (error) {
                    this.showNotification('error', 'Failed to update status');
                }
            });

            cell.appendChild(menu);
        });

        // Close menus when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.status-quick-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        });
    }

    async updateRegistrationStatus(registrationId, status) {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_registration_status',
                registration_id: registrationId,
                status: status,
                nonce: aiCalendarAdmin.nonce
            })
        });

        if (!response.ok) throw new Error('Failed to update status');
        return await response.json();
    }

    initBulkActions() {
        const bulkForm = this.table?.closest('form');
        if (!bulkForm) return;

        bulkForm.addEventListener('submit', async (e) => {
            const action = this.bulkActions.value;
            if (!action.startsWith('update_status_')) return;

            e.preventDefault();
            const status = action.replace('update_status_', '');
            const checked = bulkForm.querySelectorAll('input[name="post[]"]:checked');
            
            if (!checked.length) {
                this.showNotification('error', 'Please select registrations to update');
                return;
            }

            const ids = Array.from(checked).map(input => input.value);
            
            try {
                await Promise.all(ids.map(id => this.updateRegistrationStatus(id, status)));
                
                // Update UI
                checked.forEach(input => {
                    const row = input.closest('tr');
                    const statusCell = row.querySelector('.registration-status');
                    if (statusCell) {
                        statusCell.className = `registration-status status-${status}`;
                        statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    }
                });

                this.showNotification('success', 'Bulk status update completed');
                this.updateStatsSummary();
            } catch (error) {
                this.showNotification('error', 'Failed to update some registrations');
            }
        });
    }

    initLiveSearch() {
        if (!this.searchInput) return;

        let timeout;
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchTerm = e.target.value.toLowerCase();
                
                document.querySelectorAll('.wp-list-table tbody tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
                
                this.updateStatsSummary();
            }, 300);
        });
    }

    initFilterEnhancements() {
        this.filterDropdowns.forEach(dropdown => {
            dropdown.addEventListener('change', () => {
                this.updateStatsSummary();
            });
        });
    }

    initRowActions() {
        document.querySelectorAll('.wp-list-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', () => {
                const actions = row.querySelector('.row-actions');
                if (actions) actions.style.left = '0';
            });

            row.addEventListener('mouseleave', () => {
                const actions = row.querySelector('.row-actions');
                if (actions) actions.style.left = '-9999em';
            });
        });
    }

    initStatsSummary() {
        // Create stats container
        const stats = document.createElement('div');
        stats.className = 'registration-stats';
        stats.innerHTML = this.getStatsHTML();

        // Insert after search box
        const searchBox = document.querySelector('.search-box');
        if (searchBox) {
            searchBox.parentNode.insertBefore(stats, searchBox.nextSibling);
        }

        this.updateStatsSummary();
    }

    getStatsHTML() {
        return `
            <div class="stats-grid">
                <div class="stat-card total">
                    <span class="stat-label">Total</span>
                    <span class="stat-value">0</span>
                </div>
                <div class="stat-card pending">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value">0</span>
                </div>
                <div class="stat-card confirmed">
                    <span class="stat-label">Confirmed</span>
                    <span class="stat-value">0</span>
                </div>
                <div class="stat-card cancelled">
                    <span class="stat-label">Cancelled</span>
                    <span class="stat-value">0</span>
                </div>
            </div>
        `;
    }

    updateStatsSummary() {
        const visibleRows = document.querySelectorAll('.wp-list-table tbody tr:not([style*="display: none"])');
        const stats = {
            total: visibleRows.length,
            pending: 0,
            confirmed: 0,
            cancelled: 0
        };

        visibleRows.forEach(row => {
            const status = row.querySelector('.registration-status')?.textContent.toLowerCase();
            if (status) stats[status]++;
        });

        Object.entries(stats).forEach(([key, value]) => {
            const statValue = document.querySelector(`.stat-card.${key} .stat-value`);
            if (statValue) {
                statValue.textContent = value;
                
                // Animate value change
                statValue.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    statValue.style.transform = 'scale(1)';
                }, 200);
            }
        });
    }

    showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `notice notice-${type === 'success' ? 'success' : 'error'} is-dismissible`;
        notification.innerHTML = `<p>${message}</p>`;
        
        const wrapper = document.querySelector('.wrap');
        if (wrapper) {
            wrapper.insertBefore(notification, wrapper.firstChild);
            
            // Auto dismiss after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new RegistrationAdmin();
}); 