import { hydrateStoredToken } from '~/utils/auth-token'

export default async ({ store, redirect }) => {
    const token = hydrateStoredToken(store)

    if (token && !store.getters['auth/check']) {
        await store.dispatch('auth/fetchUser')
    }

    // Only redirect when there is no token at all.
    // If fetchUser failed transiently (cold start, network blip) but the token
    // still exists, stay on the page — the user IS authenticated.
    if (!store.getters['auth/token']) {
        return redirect('/login')
    }
}
