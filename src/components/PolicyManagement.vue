<template>
    <div class="policy-management">
        <details class="collapsible" open>
            <summary>Create New Policy</summary>
            <form @submit.prevent="createPolicy" class="policy-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Policy Name *</label>
                        <input v-model="newPolicy.name" type="text" required placeholder="e.g., Vacation, Sick Leave" />
                    </div>
                    
                    <div class="form-group">
                        <label>Policy Type *</label>
                        <select v-model="newPolicy.type" required>
                            <option value="">Select type...</option>
                            <option value="unlimited">Unlimited</option>
                            <option value="accrual">Accrual-Based</option>
                            <option value="fixed">Fixed Annual</option>
                        </select>
                    </div>
                </div>
                
                <div v-if="newPolicy.type === 'accrual'" class="form-row">
                    <div class="form-group">
                        <label>Accrual Rate (hours) *</label>
                        <input v-model.number="newPolicy.accrualRate" type="number" step="0.5" min="0" required />
                    </div>
                    
                    <div class="form-group">
                        <label>Accrual Period *</label>
                        <select v-model="newPolicy.accrualPeriod" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Max Balance (hours)</label>
                        <input v-model.number="newPolicy.maxBalance" type="number" step="0.5" min="0" placeholder="Leave empty for no cap" />
                    </div>
                </div>
                
                <div v-if="newPolicy.type === 'fixed'" class="form-row">
                    <div class="form-group">
                        <label>Annual Hours *</label>
                        <input v-model.number="newPolicy.fixedAnnualHours" type="number" step="0.5" min="0" required />
                    </div>
                    
                    <div class="form-group">
                        <label>Reset Date (MM-DD)</label>
                        <input v-model="newPolicy.resetDate" type="text" pattern="\d{2}-\d{2}" placeholder="01-01" />
                    </div>
                </div>
                
                <button type="submit" class="primary" :disabled="creating">
                    {{ creating ? 'Creating...' : 'Create Policy' }}
                </button>
                
                <div v-if="policyError" class="error-message">{{ policyError }}</div>
                <div v-if="policySuccess" class="success-message">{{ policySuccess }}</div>
            </form>
        </details>
        
        <div class="subsection">
            <h4>Existing Policies</h4>
            
            <div v-if="loadingPolicies" class="loading-message">Loading policies...</div>
            
            <div v-else-if="policies.length === 0" class="placeholder-message">
                No policies created yet. Create one above to get started.
            </div>
            
            <div v-else class="policies-list">
                <div v-for="policy in policies" :key="policy.id" class="policy-card">
                    <div class="policy-header">
                        <h5>{{ policy.name }}</h5>
                        <span class="policy-badge">{{ formatType(policy.type) }}</span>
                    </div>
                    
                    <div class="policy-details">
                        <div v-if="policy.type === 'accrual'">
                            <strong>Accrual:</strong> {{ policy.accrualRate }} hours {{ policy.accrualPeriod }}
                            <span v-if="policy.maxBalance"> (max: {{ policy.maxBalance }} hours)</span>
                        </div>
                        <div v-if="policy.type === 'fixed'">
                            <strong>Annual:</strong> {{ policy.fixedAnnualHours }} hours
                            <span v-if="policy.resetDate"> (resets {{ policy.resetDate }})</span>
                        </div>
                        <div v-if="policy.type === 'unlimited'">
                            <strong>Unlimited time off</strong>
                        </div>
                    </div>
                    
                    <div class="policy-actions">
                        <button @click="togglePolicy(policy)" class="secondary">
                            {{ policy.enabled ? 'Disable' : 'Enable' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PolicyManagement',
    data() {
        return {
            policies: [],
            newPolicy: {
                name: '',
                type: '',
                accrualRate: null,
                accrualPeriod: 'monthly',
                maxBalance: null,
                fixedAnnualHours: null,
                resetDate: '',
            },
            loadingPolicies: false,
            creating: false,
            policyError: null,
            policySuccess: null,
        }
    },
    mounted() {
        this.loadPolicies()
    },
    methods: {
        async loadPolicies() {
            this.loadingPolicies = true
            try {
                const response = await fetch('/index.php/apps/pto/api/v1/policies')
                if (!response.ok) throw new Error('Failed to load policies')
                this.policies = await response.json()
            } catch (err) {
                console.error('Load policies error:', err)
            } finally {
                this.loadingPolicies = false
            }
        },
        
        async createPolicy() {
            this.creating = true
            this.policyError = null
            this.policySuccess = null
            
            try {
                const payload = {
                    name: this.newPolicy.name,
                    type: this.newPolicy.type,
                }
                
                if (this.newPolicy.type === 'accrual') {
                    payload.accrualRate = this.newPolicy.accrualRate
                    payload.accrualPeriod = this.newPolicy.accrualPeriod
                    if (this.newPolicy.maxBalance) payload.maxBalance = this.newPolicy.maxBalance
                }
                
                if (this.newPolicy.type === 'fixed') {
                    payload.fixedAnnualHours = this.newPolicy.fixedAnnualHours
                    if (this.newPolicy.resetDate) payload.resetDate = this.newPolicy.resetDate
                }
                
                const response = await fetch('/index.php/apps/pto/api/v1/policies', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                
                if (!response.ok) {
                    const error = await response.json()
                    throw new Error(error.error || 'Failed to create policy')
                }
                
                this.policySuccess = 'Policy created successfully!'
                this.resetForm()
                await this.loadPolicies()
                
                setTimeout(() => { this.policySuccess = null }, 3000)
            } catch (err) {
                this.policyError = err.message
            } finally {
                this.creating = false
            }
        },
        
        async togglePolicy(policy) {
            try {
                const response = await fetch(`/index.php/apps/pto/api/v1/policies/${policy.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ enabled: !policy.enabled }),
                })
                
                if (!response.ok) throw new Error('Failed to update policy')
                await this.loadPolicies()
            } catch (err) {
                console.error('Toggle policy error:', err)
            }
        },
        
        resetForm() {
            this.newPolicy = {
                name: '',
                type: '',
                accrualRate: null,
                accrualPeriod: 'monthly',
                maxBalance: null,
                fixedAnnualHours: null,
                resetDate: '',
            }
        },
        
        formatType(type) {
            return {
                unlimited: 'Unlimited',
                accrual: 'Accrual-Based',
                fixed: 'Fixed Annual',
            }[type] || type
        },
    },
}
</script>

<style scoped>
/* Import Nextcloud styles where available */
.policy-management {
    margin-top: 1rem;
}

.collapsible {
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 4px);
    margin-bottom: 1.5rem;
}

.collapsible summary {
    padding: 12px 16px;
    cursor: pointer;
    font-weight: 600;
    background: var(--color-background-hover, #f8f8f8);
    border-radius: var(--border-radius, 4px);
}

.collapsible summary:hover {
    background: var(--color-background-dark, #f0f0f0);
}

.collapsible[open] summary {
    border-bottom: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 4px) var(--border-radius, 4px) 0 0;
}

.policy-form {
    padding: 16px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 4px;
    color: var(--color-text-lighter, #555);
    font-size: 0.9rem;
}

.form-group input,
.form-group select {
    padding: 8px;
    border: 1px solid var(--color-border-dark, #ddd);
    border-radius: var(--border-radius, 4px);
    font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--color-primary-element, #0082c9);
}

button.primary,
button.secondary {
    padding: 10px 20px;
    border: none;
    border-radius: var(--border-radius, 4px);
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
    margin-top: 1rem;
}

button.primary {
    background: var(--color-primary-element, #0082c9);
    color: white;
}

button.primary:hover:not(:disabled) {
    background: var(--color-primary-element-hover, #006aa3);
}

button.primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

button.secondary {
    background: var(--color-background-hover, #f5f5f5);
    color: var(--color-main-text, #333);
    border: 1px solid var(--color-border, #ddd);
}

button.secondary:hover {
    background: var(--color-background-dark, #e8e8e8);
}

.error-message {
    color: var(--color-error, #d32f2f);
    margin-top: 1rem;
    padding: 12px;
    background: var(--color-error-background, #ffebee);
    border-radius: var(--border-radius, 4px);
}

.success-message {
    color: var(--color-success, #388e3c);
    margin-top: 1rem;
    padding: 12px;
    background: var(--color-success-background, #e8f5e9);
    border-radius: var(--border-radius, 4px);
}

.loading-message,
.placeholder-message {
    color: var(--color-text-lighter, #666);
    font-style: italic;
    padding: 1rem;
}

.subsection h4 {
    margin: 1.5rem 0 1rem 0;
    color: var(--color-main-text, #555);
}

.policies-list {
    display: grid;
    gap: 1rem;
}

.policy-card {
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 4px);
    padding: 1rem;
    background: var(--color-background-hover, #fafafa);
}

.policy-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.policy-header h5 {
    margin: 0;
    color: var(--color-main-text, #333);
}

.policy-badge {
    background: var(--color-primary-element, #0082c9);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.875rem;
}

.policy-details {
    color: var(--color-text-lighter, #555);
    margin-bottom: 1rem;
}

.policy-actions {
    display: flex;
    gap: 0.5rem;
}
</style>
