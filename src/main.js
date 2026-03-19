import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import Navigation from './components/Navigation.vue'
import Dashboard from './views/Dashboard.vue'
import MyRequests from './views/MyRequests.vue'
import NewRequest from './views/NewRequest.vue'
import ApprovalQueue from './views/ApprovalQueue.vue'

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

function initApp() {
    const navMount = document.getElementById('app-navigation-vue')
    const contentMount = document.getElementById('app-content-vue')
    
    if (!navMount || !contentMount) {
        console.error('[PTO] Mount points not found!')
        return
    }
    
    // Mount navigation
    const navApp = createApp(Navigation)
    navApp.use(router)
    navApp.mount('#app-navigation-vue')
    
    // Mount main content
    const contentApp = createApp(App)
    contentApp.use(router)
    contentApp.mount('#app-content-vue')
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp)
} else {
    initApp()
}
