import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import Dashboard from './views/Dashboard.vue'
import MyRequests from './views/MyRequests.vue'
import NewRequest from './views/NewRequest.vue'
import ApprovalQueue from './views/ApprovalQueue.vue'

console.log('[PTO] Script loaded')

const routes = [
    { path: '/', component: Dashboard },
    { path: '/requests', component: MyRequests },
    { path: '/requests/new', component: NewRequest },
    { path: '/approvals', component: ApprovalQueue },
]

const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

console.log('[PTO] Router created')

function initApp() {
    console.log('[PTO] initApp called, readyState:', document.readyState)
    const mountPoint = document.getElementById('app-content-vue')
    console.log('[PTO] Mount point:', mountPoint)
    
    if (!mountPoint) {
        console.error('[PTO] Mount point #app-content-vue not found!')
        return
    }
    
    console.log('[PTO] Creating Vue app')
    const app = createApp(App)
    app.use(router)
    console.log('[PTO] Mounting to #app-content-vue')
    app.mount('#app-content-vue')
    console.log('[PTO] App mounted successfully!')
}

console.log('[PTO] Checking document.readyState:', document.readyState)

if (document.readyState === 'loading') {
    console.log('[PTO] DOM loading, adding event listener')
    document.addEventListener('DOMContentLoaded', initApp)
} else {
    console.log('[PTO] DOM already loaded, calling initApp immediately')
    initApp()
}
