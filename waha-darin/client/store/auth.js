import axios from 'axios'
import {
    authHeader,
    clearStoredToken,
    cleanToken,
    getStoredToken,
    storeToken
} from '~/utils/auth-token'

// state
export const state = () => ({
    user: null,
    token: null
})

// getters
export const getters = {
    user: (state) => state.user,
    token: (state) => state.token,
    check: (state) => state.user !== null
}

// mutations
export const mutations = {
    SET_TOKEN(state, token) {
        state.token = token
    },

    FETCH_USER_SUCCESS(state, user) {
        state.user = user
    },

    FETCH_USER_FAILURE(state) {
        state.token = null
        state.user = null
    },

    LOGOUT(state) {
        state.user = null
        state.token = null
    },

    UPDATE_USER(state, { user }) {
        state.user = user
    }
}

// actions
export const actions = {
    saveToken({ commit }, { token, remember }) {
        const clean = storeToken(token, remember)
        commit('SET_TOKEN', clean)
    },

    async fetchUser({ commit, getters }) {
        const token = cleanToken(getters.token) || getStoredToken()
        const headers = {}
        const bearer = authHeader(token)

        if (bearer) {
            commit('SET_TOKEN', token)
            headers.Authorization = bearer
        }

        let lastError = null
        for (let attempt = 0; attempt < 3; attempt++) {
            try {
                const { data } = await axios.get('/user', { headers })
                commit('FETCH_USER_SUCCESS', data)

                return
            } catch (e) {
                lastError = e
                const status = e.response && e.response.status

                if (status === 401) {
                    clearStoredToken()
                    commit('FETCH_USER_FAILURE')

                    return
                }

                const retry =
                    attempt < 2 &&
                    (!status ||
                        status >= 500 ||
                        status === 429 ||
                        status === 408)

                if (retry) {
                    await new Promise((resolve) =>
                        setTimeout(resolve, 500 * (attempt + 1))
                    )

                    continue
                }

                break
            }
        }

        if (process.env.NODE_ENV === 'development') {
            // eslint-disable-next-line no-console
            console.warn('[auth/fetchUser] failed after retries', lastError)
        }
    },

    updateUser({ commit }, payload) {
        commit('UPDATE_USER', payload)
    },

    async logout({ commit }) {
        try {
            await axios.post('/logout')
        } catch (e) {}

        clearStoredToken()

        commit('LOGOUT')
    },

    async fetchOauthUrl(ctx, { provider }) {
        const { data } = await axios.post(`/oauth/${provider}`)

        return data.url
    }
}
