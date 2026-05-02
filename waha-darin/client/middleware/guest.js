import { hydrateStoredToken } from '~/utils/auth-token'

export default async ({ store, redirect }) => {
    const token = hydrateStoredToken(store)

    if (token && !store.getters['auth/check']) {
        await store.dispatch('auth/fetchUser')
    }

    if (store.getters['auth/check']) {
        return redirect('/')
    }
}
