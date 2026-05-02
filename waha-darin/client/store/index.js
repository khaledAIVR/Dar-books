import Cookies from 'js-cookie'
import { cookieFromRequest } from '~/utils'
import { clearStoredToken, getStoredToken } from '~/utils/auth-token'

export const actions = {
    nuxtServerInit({ commit }, { req }) {
        const token = cookieFromRequest(req, 'token')
        if (token) {
            commit('auth/SET_TOKEN', token)
        }

        const locale = cookieFromRequest(req, 'locale')
        if (locale) {
            commit('lang/SET_LOCALE', { locale })
        }
    },

    nuxtClientInit({ commit }) {
        const token = getStoredToken()
        if (token) {
            commit('auth/SET_TOKEN', token)
        } else {
            clearStoredToken()
        }

        const locale = Cookies.get('locale')
        if (locale) {
            commit('lang/SET_LOCALE', { locale })
        }
    }
}
