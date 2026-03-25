import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
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
    const app = createApp(App)
    app.use(router)
    
    const mountPoint = document.getElementById('content')
    if (mountPoint) {
        app.mount('#content')
    } else {
        console.error('[PTO] Mount point #content not found!')
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp)
} else {
    initApp()
}
