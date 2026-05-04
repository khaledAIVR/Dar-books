<template>
    <div>
        <home-banner v-if="!user" />
        <!--    <categories-stories />-->
        <color-full-books-slider :key="locale" />
        <div v-if="categories.categories.length > 0">
            <category-books-slider
                v-for="index in load"
                :key="categories.categories[index].id + locale"
                :category="categories.categories[index]"
            />
        </div>
        <infinite-loading
            v-if="categories.categories.length > 0"
            :distance="500"
            spinner="spiral"
            @infinite="infiniteScroll"
        />
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    components: {
        'home-banner': require('~/components/home/HomeBanner').default,
        // 'categories-stories': () => import('~/components/home/CategoriesStories'),
        'color-full-books-slider': require('~/components/home/ColorFullBookSlider')
            .default,
        'category-books-slider': require('~/components/home/CategoryBooksSlider')
            .default
    },
    data() {
        return {
            load: 0
        }
    },
    computed: mapGetters({
        locale: 'lang/locale',
        user: 'auth/user',
        categories: 'category/categories'
    }),
    created() {
        this.fetch()
    },
    methods: {
        async fetch() {
            try {
                this.$nuxt.$loading.start()
            } catch (e) {}
            await Promise.all([
                this.$store.dispatch('book/fetchBooks'),
                this.$store.dispatch('category/fetchCategories', {})
            ])
        },
        infiniteScroll($state) {
            if (
                this.load < this.categories.categories.length - 1 ||
                this.load === 1
            ) {
                this.load++
                $state.loaded()
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
