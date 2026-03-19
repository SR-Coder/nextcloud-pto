<template>
    <ul>
        <li>
            <router-link to="/" class="app-navigation-entry">
                <div class="app-navigation-entry__icon">
                    <span class="icon icon-home"></span>
                </div>
                <div class="app-navigation-entry__name">Dashboard</div>
            </router-link>
        </li>
        <li>
            <router-link to="/requests" class="app-navigation-entry">
                <div class="app-navigation-entry__icon">
                    <span class="icon icon-files"></span>
                </div>
                <div class="app-navigation-entry__name">My Requests</div>
            </router-link>
        </li>
        <li>
            <router-link to="/requests/new" class="app-navigation-entry">
                <div class="app-navigation-entry__icon">
                    <span class="icon icon-add"></span>
                </div>
                <div class="app-navigation-entry__name">New Request</div>
            </router-link>
        </li>
        <li>
            <router-link to="/approvals" class="app-navigation-entry">
                <div class="app-navigation-entry__icon">
                    <span class="icon icon-checkmark"></span>
                </div>
                <div class="app-navigation-entry__name">Approvals</div>
            </router-link>
        </li>
    </ul>
</template>

<script>
export default {
    name: 'Navigation',
}
</script>
