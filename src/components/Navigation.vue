<template>
    <li>
        <router-link to="/">
            <span class="app-navigation-entry-icon icon-home"></span>
            Dashboard
        </router-link>
    </li>
    <li>
        <router-link to="/requests">
            <span class="app-navigation-entry-icon icon-files"></span>
            My Requests
        </router-link>
    </li>
    <li>
        <router-link to="/requests/new">
            <span class="app-navigation-entry-icon icon-add"></span>
            New Request
        </router-link>
    </li>
    <li>
        <router-link to="/approvals">
            <span class="app-navigation-entry-icon icon-checkmark"></span>
            Approvals
        </router-link>
    </li>
</template>

<script>
export default {
    name: 'Navigation',
}
</script>
