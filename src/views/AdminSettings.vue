<template>
    <div class="admin-settings">
        <h2>PTO Administration</h2>
        
        <!-- Policy Management -->
        <div class="section">
            <h3>PTO Policies</h3>
            <p class="section-desc">Create and manage PTO policies for your organization.</p>
            
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
                    
                    <button type="submit" class="btn-primary" :disabled="creating">
                        {{ creating ? 'Creating...' : 'Create Policy' }}
                    </button>
                    
                    <div v-if="policyError" class="error">{{ policyError }}</div>
                    <div v-if="policySuccess" class="success">{{ policySuccess }}</div>
                </form>
            </details>
            
            <div class="subsection">
                <h4>Existing Policies</h4>
                
                <div v-if="loadingPolicies" class="loading">Loading policies...</div>
                
                <div v-else-if="policies.length === 0" class="placeholder">
                    No policies created yet. Create one above to get started.
                </div>
                
                <div v-else class="policies-list">
                    <div v-for="policy in policies" :key="policy.id" class="policy-card">
                        <div class="policy-header">
                            <h5>{{ policy.name }}</h5>
                            <span class="policy-type">{{ formatType(policy.type) }}</span>
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
                            <button @click="togglePolicy(policy)" class="btn-secondary">
                                {{ policy.enabled ? 'Disable' : 'Enable' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Manager Assignment -->
        <div class="section">
            <h3>Manager Assignment</h3>
            <p class="section-desc">Assign users who can approve PTO requests.</p>
            
            <div v-if="loadingUsers" class="loading">Loading users...</div>
            
            <div v-else class="user-roles">
                <div class="placeholder">
                    Coming soon: User management integration with Nextcloud groups.
                    Managers will be able to approve requests from their team members.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AdminSettings',
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
            
            users: [],
            loadingUsers: false,
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
.admin-settings {
    max-width: 1200px;
}

.admin-settings h2 {
    color: #0082c9;
    margin-bottom: 2rem;
}

.section {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.section h3 {
    margin-top: 0;
    color: #333;
}

.section-desc {
    color: #666;
    margin-top: -0.5rem;
    margin-bottom: 1.5rem;
}

.collapsible {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 1.5rem;
}

.collapsible summary {
    padding: 0.75rem 1rem;
    cursor: pointer;
    font-weight: 500;
    background: #f8f8f8;
    border-radius: 4px;
}

.collapsible summary:hover {
    background: #f0f0f0;
}

.collapsible[open] summary {
    border-bottom: 1px solid #e0e0e0;
    border-radius: 4px 4px 0 0;
}

.policy-form {
    padding: 1rem;
}

.subsection h4 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    color: #555;
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
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: #555;
}

.form-group input,
.form-group select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0082c9;
}

.btn-primary,
.btn-secondary {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary {
    background: #0082c9;
    color: white;
    margin-top: 1rem;
}

.btn-primary:hover:not(:disabled) {
    background: #006aa3;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #e8e8e8;
}

.error {
    color: #d32f2f;
    margin-top: 1rem;
    padding: 0.75rem;
    background: #ffebee;
    border-radius: 4px;
}

.success {
    color: #388e3c;
    margin-top: 1rem;
    padding: 0.75rem;
    background: #e8f5e9;
    border-radius: 4px;
}

.loading,
.placeholder {
    color: #666;
    font-style: italic;
    padding: 1rem;
}

.policies-list {
    display: grid;
    gap: 1rem;
}

.policy-card {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 1rem;
    background: #fafafa;
}

.policy-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.policy-header h5 {
    margin: 0;
    color: #333;
}

.policy-type {
    background: #0082c9;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
}

.policy-details {
    color: #555;
    margin-bottom: 1rem;
}

.policy-actions {
    display: flex;
    gap: 0.5rem;
}

.user-roles {
    display: grid;
    gap: 0.75rem;
}
</style>
