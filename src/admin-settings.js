import { createApp } from 'vue'
import PolicyManagement from './components/PolicyManagement.vue'
import ManagerAssignment from './components/ManagerAssignment.vue'
import CalendarSettings from './components/CalendarSettings.vue'

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

// Mount Calendar Settings
const calendarEl = document.getElementById('pto-calendar-settings')
if (calendarEl) {
    const calendarApp = createApp(CalendarSettings)
    calendarApp.mount(calendarEl)
}
