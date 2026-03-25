<template>
    <!-- Loading state -->
    <div v-if="loading" id="app-content">
        <div id="app-content-wrapper">
            <p style="padding: 2rem;">Loading...</p>
        </div>
    </div>
    
    <!-- No access state -->
    <div v-else-if="!hasAccess" id="app-content">
        <div id="app-content-wrapper" style="padding: 2rem;">
            <h2>PTO Tracker Not Available</h2>
            <p>You do not have any PTO policies assigned to your account.</p>
            <p>Please contact your administrator to request access to the PTO Tracker.</p>
        </div>
    </div>
    
    <!-- Has access - render navigation and content as siblings (required for Nextcloud layout) -->
    <template v-else>
        <div id="app-navigation">
            <Navigation />
        </div>
        <div id="app-content">
            <div id="app-content-wrapper">
                <router-view />
            </div>
        </div>
    </template>
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
                const data = await apiGet('balances')
                const balances = data.balances || data
                
                // User has access if they have at least one policy OR are an admin
                if (Array.isArray(balances) && balances.length > 0) {
                    this.hasAccess = true
                } else {
                    // Check if admin (canApprove means user is admin or manager)
                    const userData = await apiGet('users/managers')
                    const currentUser = userData.users.find(u => u.id === OC.getCurrentUser().uid)
                    this.hasAccess = currentUser?.canApprove || false
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
