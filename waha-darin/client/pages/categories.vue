<template>
    <div>
        <home-banner v-if="!user" />
        <categories-list />
        <infinite-loading
            v-if="categories"
            spinner="spiral"
            @infinite="infiniteScroll"
        />
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
        user: 'auth/user',
        categories: 'category/categories'
    }),
    methods: {
        infiniteScroll($state) {
            if (
                this.categories.categories.length < this.categories.total ||
                this.page === 1
            ) {
                this.page++
                this.$store
                    .dispatch('category/fetchCategories', { page: this.page })
                    .then(() => {
                        $state.loaded()
                        this.$nuxt.$loading.stop()
                    })
            } else {
                $state.complete()
            }
        }
    },
    head() {
        return { title: this.$t('home') }
    }
}
</script>
