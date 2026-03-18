import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import Dashboard from './views/Dashboard.vue'
import MyRequests from './views/MyRequests.vue'
import NewRequest from './views/NewRequest.vue'
import ApprovalQueue from './views/ApprovalQueue.vue'
import AdminSettings from './views/AdminSettings.vue'

const routes = [
    { path: '/', component: Dashboard },
    { path: '/requests', component: MyRequests },
    { path: '/requests/new', component: NewRequest },
    { path: '/approvals', component: ApprovalQueue },
    { path: '/admin', component: AdminSettings },
]

const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

function initApp() {
    const mountPoint = document.getElementById('app-content-vue')
    if (!mountPoint) {
        console.error('PTO app mount point not found')
        return
    }
    
    const app = createApp(App)
    app.use(router)
    app.mount('#app-content-vue')
}

// Try multiple initialization strategies
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp)
} else {
    // DOM already ready
    initApp()
}
