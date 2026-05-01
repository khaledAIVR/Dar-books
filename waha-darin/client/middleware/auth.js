export default async ({ store, redirect }) => {
    const token = store.getters['auth/token']
    if (!store.getters['auth/check'] && token) {
        await store.dispatch('auth/fetchUser')
    }

    if (!store.getters['auth/check']) {
        return redirect('/login')
    }
}
