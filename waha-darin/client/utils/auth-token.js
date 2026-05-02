import Cookies from 'js-cookie'

const TOKEN_KEY = 'token'

export function cleanToken(token) {
    const clean = String(token || '').trim()
    return clean && clean !== 'undefined' ? clean : null
}

export function authHeader(token) {
    const clean = cleanToken(token)
    if (!clean) return null

    return clean.match(/^Bearer\s+/i) ? clean : `Bearer ${clean}`
}

export function getStoredToken() {
    if (typeof window === 'undefined') {
        return null
    }

    const fromStorage = window.localStorage.getItem(TOKEN_KEY)
    const fromCookie = Cookies.get(TOKEN_KEY)

    return cleanToken(fromStorage || fromCookie)
}

export function storeToken(token, remember) {
    const clean = cleanToken(token)
    if (!clean) return null

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(TOKEN_KEY, clean)
    }

    Cookies.set(TOKEN_KEY, clean, { expires: remember ? 365 : 7 })

    return clean
}

export function clearStoredToken() {
    Cookies.remove(TOKEN_KEY)

    if (typeof window !== 'undefined') {
        window.localStorage.removeItem(TOKEN_KEY)
    }
}

export function hydrateStoredToken(store) {
    const token = cleanToken(store.getters['auth/token']) || getStoredToken()

    if (token) {
        store.commit('auth/SET_TOKEN', token)
        return token
    }

    clearStoredToken()

    return null
}
