<template>
    <div class="dashboard">
        <h2>PTO Dashboard</h2>
        <div class="dashboard-grid">
            <div class="balance-summary card">
                <h3>Your Balances</h3>
                <p class="placeholder">Coming soon: Display PTO balances by policy</p>
            </div>
            <div class="upcoming-time-off card">
                <h3>Upcoming Time Off</h3>
                <p class="placeholder">Coming soon: Display approved future requests</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Dashboard',
}
</script>

<style scoped>
.dashboard {
    max-width: 1200px;
}

.dashboard h2 {
    color: #0082c9;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.card {
    padding: 1.5rem;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card h3 {
    color: #333;
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.placeholder {
    color: #666;
    font-style: italic;
}
</style>
