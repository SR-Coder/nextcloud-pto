<template>
    <div id="app-navigation">
        <Navigation />
    </div>
    <div id="app-content">
        <div id="app-content-wrapper">
            <router-view />
        </div>
    </div>
</template>

<script>
import Navigation from './components/Navigation.vue'

export default {
    name: 'App',
    components: {
        Navigation
    }
}
</script>
