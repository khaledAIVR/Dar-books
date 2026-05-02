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
        // localStorage is primary; fall back to cookie
        const fromStorage = typeof window !== 'undefined'
            ? window.localStorage.getItem('token')
            : null
        const fromCookie = Cookies.get('token')
        const raw = fromStorage || fromCookie
        const token = raw && raw !== 'undefined' ? raw : null
        if (token) {
            commit('auth/SET_TOKEN', token)
        } else {
            Cookies.remove('token')
            if (typeof window !== 'undefined') window.localStorage.removeItem('token')
        }

        const locale = Cookies.get('locale')
        if (locale) {
            commit('lang/SET_LOCALE', { locale })
        }
    }
}
