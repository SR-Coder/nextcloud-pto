<template>
    <div class="dashboard">
        <h2>PTO Dashboard</h2>
        
        <div v-if="loading" class="loading-message">Loading your balances...</div>
        
        <div v-else class="dashboard-grid">
            <!-- Balance Cards -->
            <div class="section">
                <h3>Your PTO Balances</h3>
                
                <div v-if="balances.length === 0" class="placeholder">
                    No PTO policies assigned yet. Contact your administrator to get started.
                </div>
                
                <div v-else class="balance-cards">
                    <div v-for="balance in balances" :key="balance.policyId" class="balance-card">
                        <div class="balance-header">
                            <h4>{{ balance.policyName }}</h4>
                            <span class="policy-type-badge">{{ formatPolicyType(balance.policyType) }}</span>
                        </div>
                        
                        <div class="balance-info">
                            <div v-if="balance.policyType === 'unlimited'" class="unlimited-badge">
                                ∞ Unlimited Time Off
                            </div>
                            
                            <div v-else class="balance-stats">
                                <div class="stat">
                                    <span class="stat-label">Available</span>
                                    <span class="stat-value">{{ formatHours(balance.availableBalance) }}</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Used</span>
                                    <span class="stat-value">{{ formatHours(balance.usedBalance) }}</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Pending</span>
                                    <span class="stat-value">{{ formatHours(balance.pendingBalance) }}</span>
                                </div>
                            </div>
                            
                            <div v-if="balance.policyType === 'accrual'" class="accrual-info">
                                <small>Accrues {{ balance.accrualRate }} hours {{ balance.accrualPeriod }}</small>
                            </div>
                        </div>
                        
                        <div v-if="balance.policyType !== 'unlimited'" class="balance-bar">
                            <div 
                                class="balance-bar-fill"
                                :style="{ width: balancePercentage(balance) + '%' }"
                                :class="balanceColorClass(balance)"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Time Off -->
            <div class="section">
                <h3>Upcoming Time Off</h3>
                
                <div v-if="loadingRequests" class="loading-message">Loading...</div>
                
                <div v-else-if="upcomingRequests.length === 0" class="placeholder">
                    No upcoming time off scheduled.
                </div>
                
                <div v-else class="upcoming-list">
                    <div v-for="request in upcomingRequests" :key="request.id" class="upcoming-item">
                        <div class="upcoming-date">
                            <span class="date-label">{{ formatDateRange(request.startDate, request.endDate) }}</span>
                            <span class="duration-label">{{ request.totalHours }} hours</span>
                        </div>
                        <div class="upcoming-details">
                            <strong>{{ request.policyName }}</strong>
                            <span v-if="request.status === 'pending'" class="status-badge pending">Pending</span>
                            <span v-if="request.status === 'approved'" class="status-badge approved">Approved</span>
                        </div>
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
    name: 'Dashboard',
    data() {
        return {
            balances: [],
            upcomingRequests: [],
            loading: false,
            loadingRequests: false,
            error: null,
        }
    },
    mounted() {
        this.loadBalances()
        this.loadUpcomingRequests()
    },
    methods: {
        async loadBalances() {
            this.loading = true
            this.error = null
            
            try {
                const data = await apiGet('balances')
                this.balances = data.balances || []
            } catch (err) {
                console.error('Load balances error:', err)
                this.error = err.message
            } finally {
                this.loading = false
            }
        },
        
        async loadUpcomingRequests() {
            this.loadingRequests = true
            
            try {
                const data = await apiGet('requests')
                const now = new Date()
                
                // Filter for upcoming approved or pending requests
                this.upcomingRequests = (data.requests || [])
                    .filter(r => {
                        const startDate = new Date(r.startDate)
                        return (r.status === 'approved' || r.status === 'pending') && startDate >= now
                    })
                    .sort((a, b) => new Date(a.startDate) - new Date(b.startDate))
                    .slice(0, 5) // Show next 5
            } catch (err) {
                console.error('Load requests error:', err)
            } finally {
                this.loadingRequests = false
            }
        },
        
        formatPolicyType(type) {
            const types = {
                unlimited: 'Unlimited',
                accrual: 'Accrual',
                fixed: 'Fixed Annual',
            }
            return types[type] || type
        },
        
        formatHours(hours) {
            if (hours === null || hours === undefined) return '0 hrs'
            return `${hours} hrs`
        },
        
        formatDateRange(start, end) {
            const startDate = new Date(start)
            const endDate = new Date(end)
            
            const options = { month: 'short', day: 'numeric' }
            const startStr = startDate.toLocaleDateString('en-US', options)
            const endStr = endDate.toLocaleDateString('en-US', options)
            
            if (startDate.toDateString() === endDate.toDateString()) {
                return startStr
            }
            
            return `${startStr} - ${endStr}`
        },
        
        balancePercentage(balance) {
            const total = balance.availableBalance + balance.usedBalance
            if (total === 0) return 0
            return Math.round((balance.availableBalance / total) * 100)
        },
        
        balanceColorClass(balance) {
            const pct = this.balancePercentage(balance)
            if (pct > 50) return 'high'
            if (pct > 20) return 'medium'
            return 'low'
        },
    },
}
</script>

<style scoped>
.dashboard {
    max-width: 1200px;
}

.dashboard h2 {
    color: var(--color-primary-element, #0082c9);
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.loading-message,
.placeholder {
    color: var(--color-text-lighter, #666);
    font-style: italic;
    padding: 1rem;
}

.error-message {
    color: var(--color-error, #d32f2f);
    padding: 1rem;
    background: var(--color-error-background, #ffebee);
    border-radius: var(--border-radius, 4px);
    margin-top: 1rem;
}

.dashboard-grid {
    display: grid;
    gap: 2rem;
}

.section {
    background: white;
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    padding: 1.5rem;
}

.section h3 {
    color: var(--color-main-text, #333);
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
}

.balance-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.balance-card {
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 8px);
    padding: 1.5rem;
    background: var(--color-background-hover, #fafafa);
}

.balance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.balance-header h4 {
    margin: 0;
    color: var(--color-main-text, #333);
    font-size: 1.1rem;
}

.policy-type-badge {
    background: var(--color-background-dark, #e8e8e8);
    color: var(--color-text-lighter, #666);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.balance-info {
    margin-bottom: 1rem;
}

.unlimited-badge {
    font-size: 1.5rem;
    color: var(--color-primary-element, #0082c9);
    font-weight: 600;
    text-align: center;
    padding: 1rem 0;
}

.balance-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat {
    display: flex;
    flex-direction: column;
    text-align: center;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--color-text-lighter, #666);
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-main-text, #333);
}

.accrual-info {
    margin-top: 0.5rem;
    color: var(--color-text-lighter, #666);
    text-align: center;
}

.balance-bar {
    height: 8px;
    background: var(--color-background-dark, #e0e0e0);
    border-radius: 4px;
    overflow: hidden;
}

.balance-bar-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.balance-bar-fill.high {
    background: var(--color-success, #4caf50);
}

.balance-bar-fill.medium {
    background: var(--color-warning, #ff9800);
}

.balance-bar-fill.low {
    background: var(--color-error, #f44336);
}

.upcoming-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.upcoming-item {
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--border-radius, 4px);
    padding: 1rem;
    background: var(--color-background-hover, #fafafa);
}

.upcoming-date {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.date-label {
    font-weight: 600;
    color: var(--color-main-text, #333);
}

.duration-label {
    color: var(--color-text-lighter, #666);
    font-size: 0.875rem;
}

.upcoming-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.pending {
    background: var(--color-warning, #ff9800);
    color: white;
}

.status-badge.approved {
    background: var(--color-success, #4caf50);
    color: white;
}
</style>
