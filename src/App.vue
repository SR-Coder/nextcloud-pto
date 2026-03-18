<template>
    <div id="pto-app">
        <div class="pto-container">
            <nav class="pto-nav">
                <router-link to="/">Dashboard</router-link>
                <router-link to="/requests">My Requests</router-link>
                <router-link to="/requests/new">New Request</router-link>
                <router-link to="/approvals">Approvals</router-link>
                <router-link to="/admin">Settings</router-link>
            </nav>
            <main class="pto-content">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script>
export default {
    name: 'App',
}
</script>

<style>
/* Global styles to ensure proper scrolling in Nextcloud */
#app-content-vue {
    height: auto !important;
    min-height: 100%;
    overflow: visible !important;
}
</style>

<style scoped>
#pto-app {
    padding: 20px;
    max-height: none;
    overflow-y: auto;
}

.pto-container {
    max-width: 1200px;
    margin: 0 auto 60px auto;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pto-nav {
    display: flex;
    gap: 0;
    background: #0082c9;
    border-bottom: 3px solid #006aa3;
}

.pto-nav a {
    padding: 1rem 1.5rem;
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.pto-nav a:last-child {
    border-right: none;
}

.pto-nav a:hover {
    background: rgba(255, 255, 255, 0.1);
}

.pto-nav a.router-link-active {
    background: rgba(255, 255, 255, 0.2);
    border-bottom: 3px solid white;
    margin-bottom: -3px;
}

.pto-content {
    padding: 2rem;
    color: #333;
    min-height: 400px;
    overflow: visible;
}
</style>
