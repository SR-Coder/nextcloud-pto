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
        
        <div v-if="error" class="error-message">{{ error }}</div>
        <div v-if="success" class="success-message">{{ success }}</div>
    </div>
</template>

<script>
export default {
    name: 'ApprovalQueue',
    data() {
        return {
            isManager: false,
            pendingRequests: [],
            comments: {},
            processing: {},
            loading: false,
            error: null,
            success: null,
        }
    },
    mounted() {
        this.checkManagerStatus()
        this.loadPendingRequests()
    },
    methods: {
        async checkManagerStatus() {
            // TODO: Add endpoint to check if current user is a manager
            // For now, assume they are if they can access this page
            this.isManager = true
        },
        
        async loadPendingRequests() {
            this.loading = true
            this.error = null
            
            try {
                const response = await fetch('/index.php/apps/pto/api/v1/requests/pending')
                if (!response.ok) throw new Error('Failed to load pending requests')
                
                const data = await response.json()
                this.pendingRequests = data.requests || []
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
            
            this.$set(this.processing, requestId, true)
            this.error = null
            this.success = null
            
            try {
                const response = await fetch(`/index.php/apps/pto/api/v1/requests/${requestId}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comments: this.comments[requestId] || null,
                    }),
                })
                
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.error || 'Failed to approve request')
                }
                
                this.success = 'Request approved successfully'
                this.pendingRequests = this.pendingRequests.filter(r => r.id !== requestId)
                delete this.comments[requestId]
                
                setTimeout(() => { this.success = null }, 3000)
            } catch (err) {
                console.error('Approve request error:', err)
                this.error = err.message
            } finally {
                this.$set(this.processing, requestId, false)
            }
        },
        
        async denyRequest(requestId) {
            if (!confirm('Are you sure you want to deny this request?')) {
                return
            }
            
            this.$set(this.processing, requestId, true)
            this.error = null
            this.success = null
            
            try {
                const response = await fetch(`/index.php/apps/pto/api/v1/requests/${requestId}/deny`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comments: this.comments[requestId] || null,
                    }),
                })
                
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.error || 'Failed to deny request')
                }
                
                this.success = 'Request denied'
                this.pendingRequests = this.pendingRequests.filter(r => r.id !== requestId)
                delete this.comments[requestId]
                
                setTimeout(() => { this.success = null }, 3000)
            } catch (err) {
                console.error('Deny request error:', err)
                this.error = err.message
            } finally {
                this.$set(this.processing, requestId, false)
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
    max-width: 1200px;
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
    background: white;
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
</style>
