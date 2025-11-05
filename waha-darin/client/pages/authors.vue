<template>
    <div>
        <home-banner v-if="!user" />
        <authors-list />
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    components: {
        'home-banner': () => import('~/components/home/HomeBanner'),
        'authors-list': () => import('~/components/AuthorsList')
    },
    computed: mapGetters({
        user: 'auth/user'
    }),
    head() {
        return { title: this.$t('home') }
    }
}
</script>
