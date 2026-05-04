<template>
    <main class="">
        <Navbar />
        <!--        <Search/>-->
        <add-to-cart-modal />
        <add-to-fav-list-modal />

        <div class="container-fluid py-5">
            <div class="row">
                <div class="mt-10 col-10 col-lg-10 mx-auto">
                    <Search class="search" />
                    <nuxt />
                </div>
            </div>
            <Footer />
        </div>
    </main>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    components: {
        Footer: require('~/components/layout/Footer').default,
        Navbar: require('~/components/layout/Navbar').default,
        Search: () => import('../components/layout/Search')
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

<style>
.search {
    z-index: 9;
}
</style>
