/**
 * API utility functions with automatic CSRF token handling
 */

const BASE_URL = '/index.php/apps/pto/api/v1'

/**
 * Get CSRF token from Nextcloud
 */
function getRequestToken() {
    return OC.requestToken || document.head.querySelector('meta[name="csrf-token"]')?.content
}

/**
 * Make an authenticated API request with CSRF protection
 */
export async function apiRequest(endpoint, options = {}) {
    const url = endpoint.startsWith('/') ? endpoint : `${BASE_URL}/${endpoint}`
    
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    }

    // Add CSRF token for ALL requests (Nextcloud requires it even for GET via AJAX)
    headers['requesttoken'] = getRequestToken()

    const response = await fetch(url, {
        ...options,
        headers
    })

    if (!response.ok) {
        const error = await response.text()
        throw new Error(`API Error (${response.status}): ${error}`)
    }

    // Handle 204 No Content
    if (response.status === 204) {
        return null
    }

    return response.json()
}

/**
 * GET request
 */
export async function apiGet(endpoint) {
    return apiRequest(endpoint, { method: 'GET' })
}

/**
 * POST request
 */
export async function apiPost(endpoint, data) {
    return apiRequest(endpoint, {
        method: 'POST',
        body: JSON.stringify(data)
    })
}

/**
 * PUT request
 */
export async function apiPut(endpoint, data) {
    return apiRequest(endpoint, {
        method: 'PUT',
        body: JSON.stringify(data)
    })
}

/**
 * DELETE request
 */
export async function apiDelete(endpoint) {
    return apiRequest(endpoint, { method: 'DELETE' })
}
