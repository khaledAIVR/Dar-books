import Cookies from 'js-cookie'
import { cookieFromRequest } from '~/utils'

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
        const raw = Cookies.get('token')
        const token = raw && raw !== 'undefined' ? raw : null
        if (token) {
            commit('auth/SET_TOKEN', token)
        } else {
            Cookies.remove('token')
        }

        const locale = Cookies.get('locale')
        if (locale) {
            commit('lang/SET_LOCALE', { locale })
        }
    }
}
