<template>
    <div class="my-requests">
        <h2>My Requests</h2>
        
        <div class="filters">
            <select v-model="statusFilter" @change="loadRequests">
                <option value="">All Requests</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="denied">Denied</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        
        <div v-if="loading" class="loading-message">Loading requests...</div>
        
        <div v-else-if="requests.length === 0" class="no-requests">
            <p v-if="statusFilter">No {{ statusFilter }} requests found.</p>
            <p v-else>You haven't submitted any PTO requests yet.</p>
            <router-link to="/requests/new" class="btn-primary">Submit a Request</router-link>
        </div>
        
        <div v-else class="requests-list">
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Policy</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="request in requests" :key="request.id">
                        <td>{{ request.policyName || 'N/A' }}</td>
                        <td>{{ formatLeaveType(request.leaveType) }}</td>
                        <td>{{ formatDateRange(request.startDate, request.endDate) }}</td>
                        <td>{{ request.hours }} hrs</td>
                        <td>
                            <span :class="'status-badge ' + request.status">
                                {{ formatStatus(request.status) }}
                            </span>
                        </td>
                        <td>{{ formatDate(request.createdAt) }}</td>
                        <td>
                            <button 
                                v-if="request.status === 'pending'"
                                @click="cancelRequest(request.id)"
                                class="btn-cancel"
                                :disabled="cancelling[request.id]"
                            >
                                Cancel
                            </button>
                            <span v-else class="no-action">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div v-if="error" class="error-message">{{ error }}</div>
        <div v-if="success" class="success-message">{{ success }}</div>
    </div>
</template>

<script>
import { apiGet, apiPost } from '../api.js'

export default {
    name: 'MyRequests',
    data() {
        return {
            requests: [],
            statusFilter: '',
            loading: false,
            cancelling: {},
            error: null,
            success: null,
        }
    },
    mounted() {
        this.loadRequests()
    },
    methods: {
        async loadRequests() {
            this.loading = true
            this.error = null
            
            try {
                let endpoint = 'requests'
                if (this.statusFilter) {
                    endpoint += `?status=${this.statusFilter}`
                }
                
                const data = await apiGet(endpoint)
                this.requests = data.requests || data || []
            } catch (err) {
                console.error('Load requests error:', err)
                this.error = err.message
            } finally {
                this.loading = false
            }
        },
        
        async cancelRequest(requestId) {
            if (!confirm('Are you sure you want to cancel this request?')) return
            
            this.cancelling[requestId] = true
            this.error = null
            this.success = null
            
            try {
                await apiPost(`requests/${requestId}/cancel`)
                
                this.success = 'Request cancelled'
                await this.loadRequests()
                setTimeout(() => { this.success = null }, 3000)
            } catch (err) {
                this.error = err.message
            } finally {
                this.cancelling[requestId] = false
            }
        },
        
        formatLeaveType(type) {
            return { vacation: 'Vacation', sick: 'Sick', personal: 'Personal', other: 'Other' }[type] || type
        },
        
        formatStatus(status) {
            return { pending: 'Pending', approved: 'Approved', denied: 'Denied', cancelled: 'Cancelled' }[status] || status
        },
        
        formatDateRange(start, end) {
            const opts = { month: 'short', day: 'numeric' }
            const s = new Date(start).toLocaleDateString('en-US', opts)
            const e = new Date(end).toLocaleDateString('en-US', opts)
            return s === e ? s : `${s} – ${e}`
        },
        
        formatDate(dt) {
            return new Date(dt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        },
    },
}
</script>

<style scoped>
.my-requests { width: 100%; padding: 20px; }
.my-requests h2 { color: var(--color-primary-element, #0082c9); margin-bottom: 1.5rem; }

.filters { margin-bottom: 1.5rem; }
.filters select {
    padding: 8px 12px;
    border: 1px solid var(--color-border-dark, #ddd);
    border-radius: var(--border-radius, 4px);
    font-size: 1rem;
}

.loading-message { color: var(--color-text-lighter, #666); font-style: italic; padding: 1rem; }

.no-requests {
    background: var(--color-background-hover, #f8f8f8);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    padding: 2rem;
    text-align: center;
}

.btn-primary {
    display: inline-block;
    margin-top: 1rem;
    padding: 10px 20px;
    background: var(--color-primary-element, #0082c9);
    color: white;
    border: none;
    border-radius: var(--border-radius, 4px);
    text-decoration: none;
    font-size: 1rem;
    cursor: pointer;
}

.requests-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    overflow: hidden;
}

.requests-table th {
    background: var(--color-background-dark, #f8f8f8);
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: var(--color-text-lighter, #666);
    border-bottom: 2px solid var(--color-border, #e0e0e0);
}

.requests-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-border, #e0e0e0);
    color: var(--color-main-text, #333);
}

.requests-table tr:hover { background: var(--color-background-hover, #fafafa); }

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}
.status-badge.pending { background: var(--color-warning, #ff9800); color: white; }
.status-badge.approved { background: var(--color-success, #4caf50); color: white; }
.status-badge.denied { background: var(--color-error, #f44336); color: white; }
.status-badge.cancelled { background: var(--color-text-lighter, #999); color: white; }

.btn-cancel {
    padding: 4px 12px;
    background: none;
    border: 1px solid var(--color-error, #f44336);
    color: var(--color-error, #f44336);
    border-radius: var(--border-radius, 4px);
    cursor: pointer;
    font-size: 0.875rem;
}
.btn-cancel:hover:not(:disabled) { background: var(--color-error, #f44336); color: white; }
.btn-cancel:disabled { opacity: 0.5; cursor: not-allowed; }

.no-action { color: var(--color-text-lighter, #999); }

.error-message {
    color: var(--color-error, #d32f2f);
    padding: 1rem;
    background: var(--color-error-background, #ffebee);
    border-radius: var(--border-radius, 4px);
    margin-top: 1rem;
}

.success-message {
    color: var(--color-success, #388e3c);
    padding: 1rem;
    background: var(--color-success-background, #e8f5e9);
    border-radius: var(--border-radius, 4px);
    margin-top: 1rem;
}
</style>
