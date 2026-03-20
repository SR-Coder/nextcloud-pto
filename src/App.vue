<template>
    <div v-if="loading" id="app-content">
        <div id="app-content-wrapper">
            <p style="padding: 2rem;">Loading...</p>
        </div>
    </div>
    <div v-else-if="!hasAccess" id="app-content">
        <div id="app-content-wrapper" style="padding: 2rem;">
            <h2>PTO Tracker Not Available</h2>
            <p>You do not have any PTO policies assigned to your account.</p>
            <p>Please contact your administrator to request access to the PTO Tracker.</p>
        </div>
    </div>
    <div v-else>
        <div id="app-navigation">
            <Navigation />
        </div>
        <div id="app-content">
            <div id="app-content-wrapper">
                <router-view />
            </div>
        </div>
    </div>
</template>

<script>
import Navigation from './components/Navigation.vue'
import { apiGet } from './api'

export default {
    name: 'App',
    components: {
        Navigation
    },
    data() {
        return {
            loading: true,
            hasAccess: false,
        }
    },
    async mounted() {
        await this.checkAccess()
    },
    methods: {
        async checkAccess() {
            try {
                // Check if user has any policies assigned
                const balances = await apiGet('balances')
                
                // User has access if they have at least one policy OR are an admin
                if (Array.isArray(balances) && balances.length > 0) {
                    this.hasAccess = true
                } else {
                    // Check if admin
                    const data = await apiGet('users/managers')
                    const currentUser = data.users.find(u => u.id === OC.getCurrentUser().uid)
                    this.hasAccess = currentUser?.isAdmin || false
                }
            } catch (error) {
                // On error, show access denied (safer than granting access)
                this.hasAccess = false
            } finally {
                this.loading = false
            }
        }
    }
}
</script>
