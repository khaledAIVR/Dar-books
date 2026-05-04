import axios from 'axios'
import { authHeader, hydrateStoredToken } from '~/utils/auth-token'

export default async ({ store }) => {
    const token = hydrateStoredToken(store)

    if (process.server) {
        const bearer = authHeader(token)
        if (bearer) axios.defaults.headers.common.Authorization = bearer
        else delete axios.defaults.headers.common.Authorization
    }

    if (!store.getters['auth/check'] && token) {
        if (process.client) {
            store.dispatch('auth/fetchUser')
        } else {
            await store.dispatch('auth/fetchUser')
        }
    }
}
