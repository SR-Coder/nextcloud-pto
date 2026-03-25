<template>
    <div class="balance-display">
        <h3>{{ policyName }}</h3>
        <div class="balance-value">
            <span class="hours">{{ balance }}</span>
            <span class="label">hours available</span>
        </div>
        <div v-if="policyType === 'accrual'" class="accrual-info">
            <small>Accrues {{ accrualRate }} hours {{ accrualPeriod }}</small>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BalanceDisplay',
    props: {
        policyName: {
            type: String,
            required: true,
        },
        balance: {
            type: Number,
            required: true,
        },
        policyType: {
            type: String,
            required: true,
        },
        accrualRate: {
            type: Number,
            default: null,
        },
        accrualPeriod: {
            type: String,
            default: null,
        },
    },
}
</script>

<style scoped>
.balance-display {
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
}

.balance-value {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin: 0.5rem 0;
}

.hours {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-primary);
}

.label {
    color: var(--color-text-lighter);
}

.accrual-info {
    margin-top: 0.5rem;
    color: var(--color-text-lighter);
}
</style>
