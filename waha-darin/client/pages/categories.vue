<template>
    <div>
        <home-banner v-if="!user" />
        <categories-list />
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    components: {
        'home-banner': () => import('~/components/home/HomeBanner'),
        'categories-list': () => import('~/components/CategoriesList')
    },
    computed: mapGetters({
        user: 'auth/user'
    }),
    head() {
        return { title: this.$t('home') }
    }
}
</script>
