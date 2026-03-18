import { createApp } from 'vue'
import PolicyManagement from './components/PolicyManagement.vue'
import ManagerAssignment from './components/ManagerAssignment.vue'

// Mount Policy Management
const policyEl = document.getElementById('pto-policy-management')
if (policyEl) {
    const policyApp = createApp(PolicyManagement)
    policyApp.mount(policyEl)
}

// Mount Manager Assignment
const managerEl = document.getElementById('pto-manager-assignment')
if (managerEl) {
    const managerApp = createApp(ManagerAssignment)
    managerApp.mount(managerEl)
}
