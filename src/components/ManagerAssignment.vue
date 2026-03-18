<template>
    <div class="manager-assignment">
        <div v-if="loading" class="loading-message">Loading users...</div>
        
        <div v-else class="users-table">
            <div class="table-header">
                <div class="col-user">User</div>
                <div class="col-manager">Manager</div>
                <div class="col-actions">Actions</div>
            </div>
            
            <div v-for="user in users" :key="user.id" class="user-row">
                <div class="col-user">
                    <strong>{{ user.displayName }}</strong>
                    <span class="user-id">({{ user.id }})</span>
                </div>
                
                <div class="col-manager">
                    <input 
                        type="checkbox" 
                        :id="'manager-' + user.id"
                        v-model="user.isManager"
                        @change="toggleManager(user)"
                    />
                    <label :for="'manager-' + user.id">Can approve PTO requests</label>
                </div>
                
                <div class="col-actions">
                    <span v-if="user.isAdmin" class="badge admin">Admin</span>
                    <span v-if="user.isManager" class="badge manager">Manager</span>
                </div>
            </div>
            
            <div v-if="users.length === 0" class="placeholder-message">
                No users found.
            </div>
        </div>
        
        <div v-if="error" class="error-message">{{ error }}</div>
        <div v-if="success" class="success-message">{{ success }}</div>
    </div>
</template>

<script>
export default {
    name: 'ManagerAssignment',
    data() {
        return {
            users: [],
            loading: false,
            error: null,
            success: null,
        }
    },
    mounted() {
        this.loadUsers()
    },
    methods: {
        async loadUsers() {
            this.loading = true
            this.error = null
            
            try {
                const response = await fetch('/index.php/apps/pto/api/v1/users')
                if (!response.ok) throw new Error('Failed to load users')
                
                const data = await response.json()
                this.users = data.users || []
            } catch (err) {
                console.error('Load users error:', err)
                this.error = err.message
            } finally {
                this.loading = false
            }
        },
        
        async toggleManager(user) {
            this.error = null
            this.success = null
            
            try {
                const response = await fetch(`/index.php/apps/pto/api/v1/users/${user.id}/manager`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ isManager: user.isManager }),
                })
                
                if (!response.ok) {
                    const error = await response.json()
                    throw new Error(error.error || 'Failed to update manager status')
                }
                
                this.success = `${user.displayName} manager status updated`
                setTimeout(() => { this.success = null }, 3000)
            } catch (err) {
                console.error('Toggle manager error:', err)
                this.error = err.message
                // Revert checkbox on error
                user.isManager = !user.isManager
            }
        },
    },
}
</script>

<style scoped>
.manager-assignment {
    margin-top: 1rem;
}

.loading-message,
.placeholder-message {
    color: var(--color-text-lighter, #666);
    font-style: italic;
    padding: 1rem;
}

.users-table {
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 4px);
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr;
    gap: 1rem;
    padding: 12px 16px;
    background: var(--color-background-dark, #f8f8f8);
    font-weight: 600;
    border-bottom: 1px solid var(--color-border, #e0e0e0);
}

.user-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr;
    gap: 1rem;
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-border, #e0e0e0);
    align-items: center;
}

.user-row:last-child {
    border-bottom: none;
}

.user-row:hover {
    background: var(--color-background-hover, #fafafa);
}

.col-user {
    display: flex;
    flex-direction: column;
}

.user-id {
    color: var(--color-text-lighter, #666);
    font-size: 0.875rem;
}

.col-manager {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.col-manager input[type="checkbox"] {
    cursor: pointer;
}

.col-manager label {
    cursor: pointer;
    user-select: none;
}

.col-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge.admin {
    background: var(--color-error, #d32f2f);
    color: white;
}

.badge.manager {
    background: var(--color-primary-element, #0082c9);
    color: white;
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
</style>
