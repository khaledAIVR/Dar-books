import Vue from 'vue'

// Calculate the current timestamp
Vue.mixin({
    methods: {
        timestamp() {
            let timeIndex = 0
            const shifts = [
                35,
                60,
                60 * 3,
                60 * 60 * 2,
                60 * 60 * 25,
                60 * 60 * 24 * 4,
                60 * 60 * 24 * 10
            ]
            const now = new Date()
            const shift = shifts[timeIndex++] || 0
            const date = new Date(now - shift * 1000)
            return date.getTime() / 1000
        },
        goToBookFromSlug(slug) {
            this.$router.push({ name: 'book', params: { slug } })
        },
        getValidUrl(url) {
            return url.replace(/\\/g, '/')
        }
    }
})
