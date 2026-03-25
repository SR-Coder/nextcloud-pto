<template>
    <div class="manager-assignment">
        <div class="info-box">
            <p><strong>✓ Using Nextcloud's Native Manager System</strong></p>
            <p>Manager assignments are now handled by Nextcloud's built-in user management.</p>
            <p>To assign managers:</p>
            <ol>
                <li>Go to <strong>Settings</strong> → <strong>Users</strong></li>
                <li>Select a user</li>
                <li>Assign their manager(s) in the user details</li>
            </ol>
            <p>The PTO app will automatically respect these assignments - managers can approve PTO requests from users they manage.</p>
        </div>
        
        <div class="manager-summary">
            <h4>Current Manager Assignments</h4>
            
            <div v-if="loading" class="loading-message">Loading...</div>
            
            <div v-else-if="users.length === 0" class="placeholder-message">
                No users found.
            </div>
            
            <div v-else class="users-table">
                <div class="table-header">
                    <div class="col-user">User</div>
                    <div class="col-managers">Assigned Managers</div>
                    <div class="col-can-approve">Can Approve?</div>
                </div>
                
                <div v-for="user in users" :key="user.id" class="user-row">
                    <div class="col-user">
                        <strong>{{ user.displayName }}</strong>
                        <span class="user-id">({{ user.id }})</span>
                    </div>
                    
                    <div class="col-managers">
                        <span v-if="user.managers && user.managers.length > 0">
                            {{ user.managers.join(', ') }}
                        </span>
                        <span v-else class="no-managers">No managers assigned</span>
                    </div>
                    
                    <div class="col-can-approve">
                        <span v-if="user.canApprove" class="badge manager">✓ Manager</span>
                        <span v-else class="no-action">—</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="error" class="error-message">{{ error }}</div>
    </div>
</template>

<script>
import { apiGet } from '../api.js'

export default {
    name: 'ManagerAssignment',
    data() {
        return {
            users: [],
            loading: false,
            error: null,
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
                const data = await apiGet('users/managers')
                this.users = data.users || []
            } catch (err) {
                console.error('Load users error:', err)
                this.error = err.message
            } finally {
                this.loading = false
            }
        },
    },
}
</script>

<style scoped>
.manager-assignment {
    margin-top: 1rem;
}

.info-box {
    background: var(--color-background-hover);
    border: 1px solid var(--color-border);
    border-left: 3px solid var(--color-success);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-box p {
    margin: 0.5rem 0;
    color: var(--color-main-text);
}

.info-box ol {
    margin: 0.5rem 0 0.5rem 1.5rem;
    color: var(--color-main-text);
}

.manager-summary h4 {
    color: var(--color-main-text, #555);
    margin-bottom: 1rem;
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
    grid-template-columns: 2fr 3fr 1fr;
    gap: 1rem;
    padding: 12px 16px;
    background: var(--color-background-dark, #f8f8f8);
    font-weight: 600;
    border-bottom: 1px solid var(--color-border, #e0e0e0);
}

.user-row {
    display: grid;
    grid-template-columns: 2fr 3fr 1fr;
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

.col-managers {
    color: var(--color-main-text, #333);
}

.no-managers {
    color: var(--color-text-lighter, #999);
    font-style: italic;
}

.col-can-approve {
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

.badge.manager {
    background: var(--color-primary-element, #0082c9);
    color: white;
}

.no-action {
    color: var(--color-text-lighter, #999);
}

.error-message {
    color: var(--color-error, #d32f2f);
    margin-top: 1rem;
    padding: 12px;
    background: var(--color-error-background, #ffebee);
    border-radius: var(--border-radius, 4px);
}
</style>
