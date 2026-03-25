import Vue from 'vue'
import VueI18n from 'vue-i18n'

Vue.use(VueI18n)

const i18n = new VueI18n({
    locale: 'ar',
    messages: {}
})

export default async ({ app, store }) => {
    if (process.client) {
        await loadMessages(store.getters['lang/locale'])
    }

    app.i18n = i18n

    // Helper: translated category name (falls back to category.name if no translation)
    app.i18n.categoryName = function (category) {
        if (!category || !category.name) return ''
        const key = `categories.${category.name}`
        return this.te(key) ? this.t(key) : category.name
    }
}

/**
 * @param {String} locale
 */
export async function loadMessages(locale) {
    if (Object.keys(i18n.getLocaleMessage(locale)).length === 0) {
        const module = await import(
            /* webpackChunkName: "lang-[request]" */ `~/lang/${locale}`
        )
        const messages = module.default != null ? module.default : module
        i18n.setLocaleMessage(locale, messages)
    }

    if (i18n.locale !== locale) {
        i18n.locale = locale
    }
}
