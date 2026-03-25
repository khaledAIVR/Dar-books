const { join } = require('path')
// Load Laravel env vars for Nuxt build-time config (APP_URL, API_URL, etc.)
require('dotenv').config({ path: join(__dirname, '..', '.env') })
const { copySync, removeSync, pathExistsSync } = require('fs-extra')

module.exports = {
    mode: 'spa', // Comment this for SSR

    srcDir: __dirname,

    server: {
        port: 8000,
        host: 'localhost'
    },

    env: {
        // Prefer explicit API_URL (set in Laravel root .env for split dev, e.g. http://127.0.0.1:8001/api).
        // Default `/api` avoids localhost vs 127.0.0.1 mismatch when Laravel serves the built SPA on one port.
        apiUrl: process.env.API_URL && String(process.env.API_URL).trim() !== ''
            ? process.env.API_URL
            : '/api',
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
        components: ['BPopover', 'BModal', 'BButton']
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
                    const clientPublicDir = join(
                        generator.nuxt.options.rootDir,
                        'public',
                        '_nuxt'
                    )
                    const laravelPublicDir = join(
                        generator.nuxt.options.rootDir,
                        '..',
                        'public',
                        '_nuxt'
                    )
                    const targets = [clientPublicDir, laravelPublicDir]
                    targets.forEach((target) => {
                        removeSync(target)
                        copySync(
                            join(generator.nuxt.options.generate.dir, '_nuxt'),
                            target
                        )
                    })
                    const distDir = generator.nuxt.options.generate.dir
                    const fallbackFile = pathExistsSync(
                        join(distDir, 'index.html')
                    )
                        ? 'index.html'
                        : '200.html'
                    targets.forEach((target) => {
                        copySync(
                            join(distDir, fallbackFile),
                            join(target, 'index.html')
                        )
                    })
                    if (!process.env.KEEP_GENERATE_DIR) {
                        removeSync(generator.nuxt.options.generate.dir)
                    }
                }
            }
        }
    }
}
