<template>
    <div class="approval-queue">
        <h2>Pending Approvals</h2>
        
        <div v-if="!isManager" class="not-manager">
            <p>You do not have manager permissions. Only managers can approve PTO requests.</p>
            <p>Contact your administrator if you believe this is an error.</p>
        </div>
        
        <div v-else>
            <div v-if="loading" class="loading-message">Loading pending requests...</div>
            
            <div v-else-if="pendingRequests.length === 0" class="no-requests">
                <p>No pending requests to review.</p>
            </div>
            
            <div v-else class="requests-list">
                <div v-for="request in pendingRequests" :key="request.id" class="request-card">
                    <div class="request-header">
                        <div class="user-info">
                            <h3>{{ request.userName }}</h3>
                            <span class="user-id">{{ request.userId }}</span>
                        </div>
                        <span class="status-badge pending">Pending</span>
                    </div>
                    
                    <div class="request-details">
                        <div class="detail-row">
                            <span class="label">Policy:</span>
                            <span class="value">{{ request.policyName }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Leave Type:</span>
                            <span class="value">{{ formatLeaveType(request.leaveType) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Dates:</span>
                            <span class="value">{{ formatDateRange(request.startDate, request.endDate) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Duration:</span>
                            <span class="value">{{ request.hours }} hours</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Submitted:</span>
                            <span class="value">{{ formatDateTime(request.createdAt) }}</span>
                        </div>
                        <div v-if="request.notes" class="request-notes">
                            <span class="label">Notes:</span>
                            <p>{{ request.notes }}</p>
                        </div>
                    </div>
                    
                    <div class="approval-actions">
                        <div class="comments-section">
                            <label :for="'comments-' + request.id">Manager Comments (optional)</label>
                            <textarea 
                                :id="'comments-' + request.id"
                                v-model="comments[request.id]"
                                rows="2"
                                placeholder="Add comments for the employee..."
                            ></textarea>
                        </div>
                        
                        <div class="action-buttons">
                            <button 
                                @click="denyRequest(request.id)"
                                class="btn-deny"
                                :disabled="processing[request.id]"
                            >
                                {{ processing[request.id] ? 'Processing...' : 'Deny' }}
                            </button>
                            <button 
                                @click="approveRequest(request.id)"
                                class="btn-approve"
                                :disabled="processing[request.id]"
                            >
                                {{ processing[request.id] ? 'Processing...' : 'Approve' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Approval History (collapsible) -->
        <details v-if="isManager && historicalRequests.length > 0" class="history-section" style="margin-top: 2rem;">
            <summary><h3>Approval History</h3></summary>
            
            <div v-if="loadingHistory" class="loading-message">Loading history...</div>
            
            <div v-else class="history-list">
                <div v-for="request in historicalRequests" :key="request.id" class="history-card">
                    <div class="history-header">
                        <div class="user-info">
                            <span class="user-name">{{ request.userName }}</span>
                            <span class="user-id">{{ request.userId }}</span>
                        </div>
                        <span :class="'status-badge ' + request.status">{{ request.status }}</span>
                    </div>
                    
                    <div class="history-details">
                        <span class="detail-item"><strong>Type:</strong> {{ formatLeaveType(request.leaveType) }}</span>
                        <span class="detail-item"><strong>Dates:</strong> {{ formatDateRange(request.startDate, request.endDate) }}</span>
                        <span class="detail-item"><strong>Hours:</strong> {{ request.hours }}</span>
                        <span class="detail-item"><strong>Decided:</strong> {{ formatDate(request.updatedAt) }}</span>
                    </div>
                </div>
            </div>
        </details>
        
        <div v-if="error" class="error-message">{{ error }}</div>
        <div v-if="success" class="success-message">{{ success }}</div>
    </div>
</template>

<script>
import { apiGet, apiPost } from '../api.js'

export default {
    name: 'ApprovalQueue',
    data() {
        return {
            isManager: false,
            pendingRequests: [],
            historicalRequests: [],
            comments: {},
            processing: {},
            loading: false,
            loadingHistory: false,
            error: null,
            success: null,
        }
    },
    mounted() {
        this.checkManagerStatus()
        this.loadPendingRequests()
        this.loadHistory()
    },
    methods: {
        async checkManagerStatus() {
            // TODO: Add endpoint to check if current user is a manager
            // For now, assume they are if they can access this page
            this.isManager = true
        },
        
        async loadHistory() {
            this.loadingHistory = true
            
            try {
                const data = await apiGet('requests/history')
                this.historicalRequests = Array.isArray(data) ? data : (data.requests || [])
            } catch (err) {
                console.error('Load history error:', err)
                // Don't show error - history is optional
            } finally {
                this.loadingHistory = false
            }
        },
        
        async loadPendingRequests() {
            this.loading = true
            this.error = null
            
            try {
                const data = await apiGet('requests/pending')
                this.pendingRequests = Array.isArray(data) ? data : (data.requests || [])
            } catch (err) {
                console.error('Load pending requests error:', err)
                this.error = err.message
                this.isManager = false // Hide queue if can't load
            } finally {
                this.loading = false
            }
        },
        
        async approveRequest(requestId) {
            if (!confirm('Are you sure you want to approve this request?')) {
                return
            }
            
            this.processing[requestId] = true
            this.error = null
            this.success = null
            
            try {
                await apiPost(`requests/${requestId}/approve`, {
                    comments: this.comments[requestId] || null,
                })
                
                this.success = 'Request approved successfully'
                this.pendingRequests = this.pendingRequests.filter(r => r.id !== requestId)
                delete this.comments[requestId]
                
                setTimeout(() => { this.success = null }, 3000)
            } catch (err) {
                console.error('Approve request error:', err)
                this.error = err.message
            } finally {
                this.processing[requestId] = false
            }
        },
        
        async denyRequest(requestId) {
            if (!confirm('Are you sure you want to deny this request?')) {
                return
            }
            
            this.processing[requestId] = true
            this.error = null
            this.success = null
            
            try {
                await apiPost(`requests/${requestId}/deny`, {
                    comments: this.comments[requestId] || null,
                })
                
                this.success = 'Request denied'
                this.pendingRequests = this.pendingRequests.filter(r => r.id !== requestId)
                delete this.comments[requestId]
                
                setTimeout(() => { this.success = null }, 3000)
            } catch (err) {
                console.error('Deny request error:', err)
                this.error = err.message
            } finally {
                this.processing[requestId] = false
            }
        },
        
        formatLeaveType(type) {
            const types = {
                vacation: 'Vacation',
                sick: 'Sick Leave',
                personal: 'Personal',
                other: 'Other',
            }
            return types[type] || type
        },
        
        formatDateRange(start, end) {
            const startDate = new Date(start)
            const endDate = new Date(end)
            
            const options = { month: 'short', day: 'numeric', year: 'numeric' }
            const startStr = startDate.toLocaleDateString('en-US', options)
            const endStr = endDate.toLocaleDateString('en-US', options)
            
            if (startDate.toDateString() === endDate.toDateString()) {
                return startStr
            }
            
            return `${startStr} - ${endStr}`
        },
        
        formatDateTime(dateTime) {
            const date = new Date(dateTime)
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            })
        },
    },
}
</script>

<style scoped>
.approval-queue {
    width: 100%;
    padding: 20px;
}

.approval-queue h2 {
    color: var(--color-primary-element, #0082c9);
    margin-bottom: 1.5rem;
}

.not-manager,
.no-requests {
    background: var(--color-background-hover, #f8f8f8);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    padding: 2rem;
    text-align: center;
}

.loading-message {
    color: var(--color-text-lighter, #666);
    font-style: italic;
    padding: 1rem;
}

.requests-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.request-card {
    background: var(--color-main-background);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--color-border, #e0e0e0);
}

.user-info h3 {
    margin: 0;
    color: var(--color-main-text, #333);
    font-size: 1.2rem;
}

.user-id {
    color: var(--color-text-lighter, #666);
    font-size: 0.875rem;
}

.status-badge {
    padding: 6px 16px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-badge.pending {
    background: var(--color-warning, #ff9800);
    color: white;
}

.request-details {
    margin-bottom: 1.5rem;
}

.detail-row {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 1rem;
    padding: 0.5rem 0;
}

.detail-row .label {
    font-weight: 600;
    color: var(--color-text-lighter, #666);
}

.detail-row .value {
    color: var(--color-main-text, #333);
}

.request-notes {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-border, #e0e0e0);
}

.request-notes .label {
    font-weight: 600;
    color: var(--color-text-lighter, #666);
    display: block;
    margin-bottom: 0.5rem;
}

.request-notes p {
    color: var(--color-main-text, #333);
    margin: 0;
    white-space: pre-wrap;
}

.approval-actions {
    border-top: 1px solid var(--color-border, #e0e0e0);
    padding-top: 1.5rem;
}

.comments-section {
    margin-bottom: 1rem;
}

.comments-section label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--color-text-lighter, #666);
}

.comments-section textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--color-border-dark, #ddd);
    border-radius: var(--border-radius, 4px);
    font-size: 1rem;
    font-family: inherit;
    resize: vertical;
}

.comments-section textarea:focus {
    outline: none;
    border-color: var(--color-primary-element, #0082c9);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn-approve,
.btn-deny {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--border-radius, 4px);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-approve {
    background: var(--color-success, #4caf50);
    color: white;
}

.btn-approve:hover:not(:disabled) {
    background: var(--color-success-hover, #388e3c);
}

.btn-deny {
    background: var(--color-error, #f44336);
    color: white;
}

.btn-deny:hover:not(:disabled) {
    background: var(--color-error-hover, #d32f2f);
}

.btn-approve:disabled,
.btn-deny:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

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

/* Approval History */
.history-section {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-large);
    padding: 1rem;
    background: var(--color-background-hover);
}

.history-section summary {
    cursor: pointer;
    font-weight: 600;
    padding: 0.5rem;
    list-style: none;
    user-select: none;
}

.history-section summary h3 {
    display: inline;
    margin: 0;
    color: var(--color-main-text);
}

.history-section summary::-webkit-details-marker {
    display: none;
}

.history-section summary::before {
    content: '▶';
    display: inline-block;
    margin-right: 0.5rem;
    transition: transform 0.2s;
}

.history-section[open] summary::before {
    transform: rotate(90deg);
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.history-card {
    background: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    padding: 1rem;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--color-border);
}

.history-header .user-name {
    font-weight: 600;
    color: var(--color-main-text);
    margin-right: 0.5rem;
}

.history-header .user-id {
    color: var(--color-text-lighter);
    font-size: 0.875rem;
}

.history-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    color: var(--color-main-text);
}

.detail-item {
    font-size: 0.875rem;
}

.status-badge.approved {
    background: var(--color-success);
    color: white;
}

.status-badge.denied {
    background: var(--color-error);
    color: white;
}
</style>
