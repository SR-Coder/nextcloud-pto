<template>
    <div class="calendar-settings">
        <div v-if="loading" class="loading">
            Loading calendars...
        </div>
        
        <div v-else>
            <div class="form-group">
                <label for="calendar-select">PTO Calendar</label>
                <select id="calendar-select" v-model="selectedCalendar" @change="saveCalendar">
                    <option :value="null">No calendar (disabled)</option>
                    <option 
                        v-for="calendar in writableCalendars" 
                        :key="calendar.uri" 
                        :value="calendar.uri"
                    >
                        {{ calendar.displayName }}
                    </option>
                </select>
                <p class="settings-hint">
                    When a PTO request is approved, an all-day event will be created in this calendar.
                    Use a shared calendar to make team time off visible to everyone.
                </p>
            </div>
            
            <div v-if="error" class="error-message">{{ error }}</div>
            <div v-if="success" class="success-message">{{ success }}</div>
        </div>
    </div>
</template>

<script>
import { apiGet, apiPost } from '../api.js'

export default {
    name: 'CalendarSettings',
    data() {
        return {
            calendars: [],
            selectedCalendar: null,
            loading: false,
            error: null,
            success: null,
        }
    },
    computed: {
        writableCalendars() {
            return this.calendars.filter(c => c.writable)
        }
    },
    mounted() {
        this.loadCalendars()
    },
    methods: {
        async loadCalendars() {
            this.loading = true
            this.error = null
            
            try {
                const data = await apiGet('calendar/list')
                this.calendars = data.calendars || []
                this.selectedCalendar = data.selectedUri
            } catch (err) {
                this.error = 'Failed to load calendars: ' + err.message
            } finally {
                this.loading = false
            }
        },
        
        async saveCalendar() {
            this.error = null
            this.success = null
            
            try {
                await apiPost('calendar/select', { uri: this.selectedCalendar })
                
                this.success = this.selectedCalendar 
                    ? 'Calendar integration enabled!' 
                    : 'Calendar integration disabled'
                
                setTimeout(() => { this.success = null }, 3000)
            } catch (err) {
                this.error = err.message
                // Revert selection on error
                await this.loadCalendars()
            }
        },
    },
}
</script>

<style scoped>
.calendar-settings {
    max-width: 600px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--color-main-text);
}

.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--color-border-dark);
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-family: inherit;
    min-height: 44px;
    background-color: var(--color-main-background);
    color: var(--color-main-text);
}

.settings-hint {
    color: var(--color-text-lighter);
    font-size: 0.875rem;
    margin-top: 0.5rem;
    line-height: 1.4;
}

.loading {
    color: var(--color-text-lighter);
    padding: 1rem 0;
}

.success-message {
    background: var(--color-success);
    color: var(--color-primary-element-text);
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    margin-top: 1rem;
    opacity: 0.9;
}

.error-message {
    background: var(--color-error);
    color: var(--color-primary-element-text);
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    margin-top: 1rem;
    opacity: 0.9;
}
</style>
