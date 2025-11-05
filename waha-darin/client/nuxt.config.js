require('dotenv').config()
const { join } = require('path')
const { copySync, removeSync } = require('fs-extra')

module.exports = {
    mode: 'spa', // Comment this for SSR

    srcDir: __dirname,

    env: {
        apiUrl: process.env.API_URL || process.env.APP_URL + '/api',
        appName: process.env.APP_NAME || 'Waha Darin',
        appLocale: process.env.APP_LOCALE || 'ar',
        githubAuth: !!process.env.GITHUB_CLIENT_ID
    },

    head: {
        title: process.env.APP_NAME,
        titleTemplate: '%s - ' + process.env.APP_NAME,
        meta: [
            { charset: 'utf-8' },
            {
                name: 'viewport',
                content: 'width=device-width, initial-scale=1'
            },
            { hid: 'description', name: 'description', content: 'Waha Darin' }
        ],
        link: [{ rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }]
    },

    loading: { color: '#F99D0F', throttle: 0, height: '5px' },

    router: {
        middleware: ['locale', 'check-auth']
    },

    css: [{ src: '~assets/sass/app.scss', lang: 'scss' }],

    plugins: [
        '~plugins/axios',
        '~components/global',
        '~plugins/i18n',
        '~plugins/vform',
        '~plugins/lazy-image',
        '~plugins/toast',
        '~plugins/global-methods',
        '~plugins/v-modal',
        '~plugins/image-loaded',
        '~plugins/star-rating',
        '~plugins/nuxt-client-init', // Comment this for SSR
        '~plugins/truncate',
        '~plugins/menu',
        { src: '~/plugins/vue-infinite-loading', ssr: false },
        { src: '~plugins/bootstrap', mode: 'client' },
        { src: '~plugins/ga', mode: 'client' }
    ],

    modules: ['@nuxtjs/router', 'bootstrap-vue/nuxt'],
    bootstrapVue: {
        components: ['BPopover']
    },

    build: {
        extractCSS: true
    },

    hooks: {
        generate: {
            done(generator) {
                // Copy dist files to public/_nuxt
                if (
                    generator.nuxt.options.dev === false &&
                    generator.nuxt.options.mode === 'spa'
                ) {
                    const publicDir = join(
                        generator.nuxt.options.rootDir,
                        'public',
                        '_nuxt'
                    )
                    removeSync(publicDir)
                    copySync(
                        join(generator.nuxt.options.generate.dir, '_nuxt'),
                        publicDir
                    )
                    copySync(
                        join(generator.nuxt.options.generate.dir, '200.html'),
                        join(publicDir, 'index.html')
                    )
                    removeSync(generator.nuxt.options.generate.dir)
                }
            }
        }
    }
}
