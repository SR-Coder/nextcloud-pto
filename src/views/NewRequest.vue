<template>
    <div class="new-request">
        <h2>New PTO Request</h2>
        
        <div v-if="loading" class="loading-message">Loading policies...</div>
        
        <div v-else-if="policies.length === 0" class="no-policies">
            <p>No PTO policies available. Contact your administrator to get started.</p>
        </div>
        
        <form v-else @submit.prevent="submitRequest" class="request-form">
            <div class="form-section">
                <h3>Request Details</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="policy">PTO Policy *</label>
                        <select id="policy" v-model="form.policyId" required @change="updateBalanceInfo">
                            <option value="">Select a policy...</option>
                            <option v-for="policy in policies" :key="policy.policyId" :value="policy.policyId">
                                {{ policy.policyName }} 
                                <template v-if="policy.policyType !== 'unlimited'">
                                    ({{ policy.availableBalance }} hrs available)
                                </template>
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="leaveType">Leave Type *</label>
                        <select id="leaveType" v-model="form.leaveType" required>
                            <option value="">Select type...</option>
                            <option value="vacation">Vacation</option>
                            <option value="sick">Sick Leave</option>
                            <option value="personal">Personal</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div v-if="selectedPolicy" class="balance-info">
                    <div v-if="selectedPolicy.policyType === 'unlimited'" class="balance-unlimited">
                        ✓ Unlimited time off available
                    </div>
                    <div v-else class="balance-details">
                        <span>Available: <strong>{{ selectedPolicy.availableBalance }} hours</strong></span>
                        <span>Used this year: {{ selectedPolicy.usedBalance }} hours</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="startDate">Start Date *</label>
                        <input 
                            id="startDate"
                            type="date" 
                            v-model="form.startDate" 
                            required
                            :min="minDate"
                            @change="calculateHours"
                        />
                    </div>
                    
                    <div class="form-group">
                        <label for="endDate">End Date *</label>
                        <input 
                            id="endDate"
                            type="date" 
                            v-model="form.endDate" 
                            required
                            :min="form.startDate || minDate"
                            @change="calculateHours"
                        />
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="totalHours">Total Hours *</label>
                    <input 
                        id="totalHours"
                        type="number" 
                        v-model.number="form.totalHours" 
                        required
                        min="0"
                        step="0.5"
                        placeholder="Calculated automatically"
                    />
                    <small class="form-hint">
                        {{ calculateBusinessDays() }} business days selected (8 hrs/day)
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (optional)</label>
                    <textarea 
                        id="notes"
                        v-model="form.notes" 
                        rows="4"
                        placeholder="Add any additional details..."
                    ></textarea>
                </div>
            </div>
            
            <div v-if="insufficientBalance" class="warning-message">
                ⚠️ Warning: This request exceeds your available balance by {{ Math.abs(balanceAfterRequest).toFixed(1) }} hours.
                Your request will still be submitted for manager approval.
            </div>
            
            <div class="form-actions">
                <button type="button" @click="cancel" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="submitting">
                    {{ submitting ? 'Submitting...' : 'Submit Request' }}
                </button>
            </div>
            
            <div v-if="error" class="error-message">{{ error }}</div>
            <div v-if="success" class="success-message">
                ✓ Request submitted successfully! 
                <router-link to="/requests">View your requests</router-link>
            </div>
        </form>
    </div>
</template>

<script>
import { apiGet, apiPost } from '../api.js'

export default {
    name: 'NewRequest',
    data() {
        return {
            policies: [],
            loading: false,
            submitting: false,
            error: null,
            success: false,
            form: {
                policyId: '',
                leaveType: '',
                startDate: '',
                endDate: '',
                totalHours: 0,
                notes: '',
            },
        }
    },
    computed: {
        minDate() {
            return new Date().toISOString().split('T')[0]
        },
        selectedPolicy() {
            return this.policies.find(p => p.policyId === parseInt(this.form.policyId))
        },
        insufficientBalance() {
            if (!this.selectedPolicy || this.selectedPolicy.policyType === 'unlimited') {
                return false
            }
            return this.form.totalHours > this.selectedPolicy.availableBalance
        },
        balanceAfterRequest() {
            if (!this.selectedPolicy) return 0
            return this.selectedPolicy.availableBalance - this.form.totalHours
        },
    },
    mounted() {
        this.loadPolicies()
    },
    methods: {
        async loadPolicies() {
            this.loading = true
            this.error = null
            
            try {
                const data = await apiGet('balances')
                this.policies = data.balances || []
            } catch (err) {
                console.error('Load policies error:', err)
                this.error = err.message
            } finally {
                this.loading = false
            }
        },
        
        updateBalanceInfo() {
            // Triggered when policy selection changes
            this.error = null
            this.success = false
        },
        
        calculateHours() {
            if (!this.form.startDate || !this.form.endDate) {
                this.form.totalHours = 0
                return
            }
            
            const days = this.calculateBusinessDays()
            this.form.totalHours = days * 8 // 8 hours per business day
        },
        
        calculateBusinessDays() {
            if (!this.form.startDate || !this.form.endDate) return 0
            
            const start = new Date(this.form.startDate)
            const end = new Date(this.form.endDate)
            
            let count = 0
            const current = new Date(start)
            
            while (current <= end) {
                const dayOfWeek = current.getDay()
                if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Not Sunday or Saturday
                    count++
                }
                current.setDate(current.getDate() + 1)
            }
            
            return count
        },
        
        async submitRequest() {
            this.submitting = true
            this.error = null
            this.success = false
            
            try {
                if (!this.form.policyId || !this.form.leaveType || !this.form.startDate || !this.form.endDate) {
                    throw new Error('Please fill in all required fields')
                }
                
                if (this.form.totalHours <= 0) {
                    throw new Error('Total hours must be greater than 0')
                }
                
                await apiPost('requests', {
                    policyId: parseInt(this.form.policyId),
                    leaveType: this.form.leaveType,
                    startDate: this.form.startDate,
                    endDate: this.form.endDate,
                    hours: this.form.totalHours,
                    notes: this.form.notes || null,
                })
                
                this.success = true
                this.resetForm()
                
                // Auto-redirect after 3 seconds
                setTimeout(() => {
                    this.$router.push('/requests')
                }, 3000)
            } catch (err) {
                console.error('Submit request error:', err)
                this.error = err.message
            } finally {
                this.submitting = false
            }
        },
        
        resetForm() {
            this.form = {
                policyId: '',
                leaveType: '',
                startDate: '',
                endDate: '',
                totalHours: 0,
                notes: '',
            }
        },
        
        cancel() {
            this.$router.push('/')
        },
    },
}
</script>

<style scoped>
.new-request {
    max-width: 800px;
}

.new-request h2 {
    color: var(--color-primary-element, #0082c9);
    margin-bottom: 1.5rem;
}

.loading-message {
    color: var(--color-text-lighter, #666);
    font-style: italic;
    padding: 1rem;
}

.no-policies {
    background: var(--color-background-hover, #f8f8f8);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    padding: 2rem;
    text-align: center;
}

.request-form {
    background: white;
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    padding: 2rem;
}

.form-section h3 {
    color: var(--color-main-text, #333);
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.2rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 1.5rem;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--color-text-lighter, #555);
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid var(--color-border-dark, #ddd);
    border-radius: var(--border-radius, 4px);
    font-size: 1rem;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-primary-element, #0082c9);
}

.form-hint {
    color: var(--color-text-lighter, #666);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.balance-info {
    background: var(--color-background-hover, #f8f8f8);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 4px);
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.balance-unlimited {
    color: var(--color-success, #4caf50);
    font-weight: 600;
}

.balance-details {
    display: flex;
    gap: 2rem;
    color: var(--color-main-text, #333);
}

.warning-message {
    background: var(--color-warning, #ff9800);
    color: white;
    padding: 1rem;
    border-radius: var(--border-radius, 4px);
    margin-bottom: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

.btn-primary,
.btn-secondary {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--border-radius, 4px);
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary {
    background: var(--color-primary-element, #0082c9);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: var(--color-primary-element-hover, #006aa3);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: var(--color-background-hover, #f5f5f5);
    color: var(--color-main-text, #333);
    border: 1px solid var(--color-border, #ddd);
}

.btn-secondary:hover {
    background: var(--color-background-dark, #e8e8e8);
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

.success-message a {
    color: var(--color-success, #388e3c);
    text-decoration: underline;
}
</style>
