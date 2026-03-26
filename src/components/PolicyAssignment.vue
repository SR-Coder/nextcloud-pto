<template>
    <div class="policy-assignment">
        <h3>User Policy Assignment</h3>
        <p class="section-desc">Assign PTO policies to users and set their initial balances.</p>

        <!-- User Selection -->
        <div class="form-group">
            <label for="user-select">Select User:</label>
            <select id="user-select" v-model="selectedUserId" @change="loadUserPolicies">
                <option value="">-- Select a user --</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.displayName }} ({{ user.id }})
                </option>
            </select>
        </div>

        <!-- Policy Assignment (shown when user selected) -->
        <div v-if="selectedUserId" class="policy-list">
            <h4>Policies for {{ selectedUserName }}</h4>
            
            <div v-if="loading" class="loading-message">Loading policies...</div>
            
            <div v-else-if="policies.length === 0" class="info-message">
                No policies available. Create policies first.
            </div>
            
            <div v-else class="policy-items">
                <div v-for="policy in policies" :key="policy.id" class="policy-item">
                    <div class="policy-checkbox">
                        <input 
                            type="checkbox" 
                            :id="'policy-' + policy.id"
                            :checked="isPolicyAssigned(policy.id)"
                            @change="togglePolicy(policy.id, $event.target.checked)"
                        />
                        <label :for="'policy-' + policy.id">
                            <strong>{{ policy.name }}</strong>
                            <span class="policy-type">{{ formatPolicyType(policy.type) }}</span>
                        </label>
                    </div>
                    
                    <div v-if="isPolicyAssigned(policy.id)" class="policy-balance">
                        <label>Current Balance:</label>
                        <input 
                            type="number" 
                            step="0.5"
                            :value="getBalance(policy.id)"
                            @change="updateBalance(policy.id, $event.target.value)"
                            :disabled="policy.type === 'unlimited'"
                        />
                        <span class="balance-label">hours</span>
                    </div>
                    
                    <div v-else-if="pendingAssignment[policy.id]" class="policy-balance">
                        <label>Initial Balance:</label>
                        <input 
                            type="number" 
                            step="0.5"
                            v-model.number="pendingAssignment[policy.id].initialBalance"
                            :disabled="policy.type === 'unlimited'"
                            placeholder="0"
                        />
                        <span class="balance-label">hours</span>
                    </div>
                </div>
            </div>
            
            <div v-if="hasChanges" class="actions">
                <button @click="saveChanges" :disabled="saving" class="primary">
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
                <button @click="cancelChanges" :disabled="saving">
                    Cancel
                </button>
            </div>
            
            <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
            <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>
        </div>
    </div>
</template>

<script>
import { apiGet, apiPost } from '../api'

export default {
    name: 'PolicyAssignment',
    
    data() {
        return {
            users: [],
            policies: [],
            userBalances: [],
            selectedUserId: '',
            loading: false,
            saving: false,
            successMessage: '',
            errorMessage: '',
            pendingAssignment: {},
            pendingRemoval: [],
            balanceUpdates: {},
        }
    },
    
    computed: {
        selectedUserName() {
            const user = this.users.find(u => u.id === this.selectedUserId)
            return user ? user.displayName : ''
        },
        
        hasChanges() {
            return Object.keys(this.pendingAssignment).length > 0 
                || this.pendingRemoval.length > 0
                || Object.keys(this.balanceUpdates).length > 0
        }
    },
    
    async mounted() {
        await this.loadUsers()
        await this.loadPolicies()
    },
    
    methods: {
        async loadUsers() {
            try {
                const data = await apiGet('users')
                this.users = data.users || data
            } catch (error) {
                this.errorMessage = 'Failed to load users'
            }
        },
        
        async loadPolicies() {
            try {
                this.policies = await apiGet('policies')
            } catch (error) {
                this.errorMessage = 'Failed to load policies'
            }
        },
        
        async loadUserPolicies() {
            if (!this.selectedUserId) return
            
            this.loading = true
            this.errorMessage = ''
            this.successMessage = ''
            this.pendingAssignment = {}
            this.pendingRemoval = []
            this.balanceUpdates = {}
            
            try {
                const response = await apiGet(`balances?userId=${this.selectedUserId}`)
                // Ensure userBalances is always an array
                this.userBalances = Array.isArray(response) ? response : 
                                   (response && Array.isArray(response.balances)) ? response.balances : []
            } catch (error) {
                this.errorMessage = 'Failed to load user balances'
                this.userBalances = []
            } finally {
                this.loading = false
            }
        },
        
        isPolicyAssigned(policyId) {
            if (this.pendingRemoval.includes(policyId)) return false
            if (this.pendingAssignment[policyId]) return true
            return this.userBalances.some(b => b.policyId === policyId)
        },
        
        getBalance(policyId) {
            if (this.balanceUpdates[policyId] !== undefined) {
                return this.balanceUpdates[policyId]
            }
            const balance = this.userBalances.find(b => b.policyId === policyId)
            return balance ? balance.availableBalance : 0
        },
        
        togglePolicy(policyId, checked) {
            if (checked) {
                // Assigning policy
                const policy = this.policies.find(p => p.id === policyId)
                const initialBalance = policy.type === 'fixed' ? policy.fixedAnnualHours : 0
                
                this.pendingAssignment[policyId] = {
                    policyId,
                    initialBalance
                }
                
                // Remove from pending removal if it was there
                const removalIndex = this.pendingRemoval.indexOf(policyId)
                if (removalIndex > -1) {
                    this.pendingRemoval.splice(removalIndex, 1)
                }
            } else {
                // Removing policy
                if (this.pendingAssignment[policyId]) {
                    // Was a pending assignment, just remove from pending
                    delete this.pendingAssignment[policyId]
                } else {
                    // Was previously assigned, mark for removal
                    this.pendingRemoval.push(policyId)
                }
            }
        },
        
        updateBalance(policyId, value) {
            this.balanceUpdates[policyId] = parseFloat(value) || 0
        },
        
        async saveChanges() {
            this.saving = true
            this.errorMessage = ''
            this.successMessage = ''
            
            try {
                // Assign new policies
                for (const policyId in this.pendingAssignment) {
                    const assignment = this.pendingAssignment[policyId]
                    await apiPost('balances/assign', {
                        userId: this.selectedUserId,
                        policyId: parseInt(policyId),
                        initialBalance: assignment.initialBalance
                    })
                }
                
                // Remove policies
                for (const policyId of this.pendingRemoval) {
                    await apiPost('balances/remove', {
                        userId: this.selectedUserId,
                        policyId: parseInt(policyId)
                    })
                }
                
                // Update existing balances
                for (const policyId in this.balanceUpdates) {
                    const balance = this.userBalances.find(b => b.policyId === parseInt(policyId))
                    if (balance) {
                        // Would need a PATCH endpoint for balance updates
                        // For now, we'll skip this - could add later
                    }
                }
                
                this.successMessage = 'Policy assignments saved successfully'
                await this.loadUserPolicies()
            } catch (error) {
                this.errorMessage = error.response?.data?.error || error.message || 'Failed to save changes'
            } finally {
                this.saving = false
            }
        },
        
        cancelChanges() {
            this.pendingAssignment = {}
            this.pendingRemoval = []
            this.balanceUpdates = {}
            this.errorMessage = ''
            this.successMessage = ''
        },
        
        formatPolicyType(type) {
            const types = {
                unlimited: 'Unlimited',
                accrual: 'Accrual-Based',
                fixed: 'Fixed Annual'
            }
            return types[type] || type
        }
    }
}
</script>

<style scoped>
.policy-assignment {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
}

.section-desc {
    color: var(--color-text-lighter);
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.form-group select {
    width: 100%;
    max-width: 400px;
    padding: 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-main-background);
    color: var(--color-main-text);
}

.policy-list {
    margin-top: 2rem;
}

.policy-items {
    margin-top: 1rem;
}

.policy-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: var(--color-background-dark);
    border-radius: var(--border-radius);
}

.policy-checkbox {
    flex: 1;
}

.policy-checkbox input[type="checkbox"] {
    margin-right: 0.5rem;
}

.policy-checkbox label {
    cursor: pointer;
}

.policy-type {
    margin-left: 0.5rem;
    color: var(--color-text-lighter);
    font-size: 0.9em;
}

.policy-balance {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.policy-balance label {
    font-size: 0.9em;
    color: var(--color-text-lighter);
}

.policy-balance input {
    width: 80px;
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-main-background);
    color: var(--color-main-text);
}

.balance-label {
    font-size: 0.9em;
    color: var(--color-text-lighter);
}

.actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 0.5rem;
}

.actions button {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: bold;
}

.actions button.primary {
    background: var(--color-primary-element);
    color: var(--color-primary-element-text);
}

.actions button:not(.primary) {
    background: var(--color-background-dark);
    color: var(--color-main-text);
}

.actions button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.loading-message,
.info-message {
    color: var(--color-text-lighter);
    font-style: italic;
    padding: 1rem;
}

.success-message {
    background: var(--color-success);
    color: var(--color-primary-element-text);
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    margin-top: 1rem;
}

.error-message {
    background: var(--color-error);
    color: var(--color-primary-element-text);
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    margin-top: 1rem;
}
</style>
