<template>
    <main class="">
        <Navbar />
        <div class="py-4">
            <nuxt />
        </div>
        <div class="my-5" />
        <Footer />
    </main>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    components: {
        Navbar: require('~/components/layout/Navbar').default,
        Footer: require('~/components/layout/Footer').default
    },
    computed: mapGetters({
        locale: 'lang/locale'
    }),
    head() {
        return {
            htmlAttrs: {
                lang: this.locale,
                dir: this.locale === 'ar' ? 'rtl' : 'ltr'
            }
        }
    }
}
</script>

<style lang="scss"></style>
