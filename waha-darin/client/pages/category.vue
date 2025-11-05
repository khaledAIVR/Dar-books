<template>
    <div>
        <auth-alert />
        <div
            v-if="
                newCategory && newCategory.books && newCategory.books.length > 0
            "
            class="wrapper"
        >
            <div class="section text-start">
                <div class="d-flex book-list-group justify-content-start mb-5">
                    <h1>
                        <span
                            v-if="newCategory.color"
                            class="circle"
                            :style="`background: ${newCategory.color}`"
                        />
                        {{ newCategory.name }}
                    </h1>
                </div>
                <div class="row justify-content-between">
                    <book-list
                        v-for="(book, index) in ourBooks"
                        :key="book.id + 'book' + index"
                        :book="book"
                    />
                </div>
                <infinite-loading spinner="spiral" @infinite="infiniteScroll" />
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'Category',
    components: {
        'book-list': () => import('~/components/BookList')
    },
    async fetch() {
        try {
            this.$nuxt.$loading.start()
        } catch (e) {}
        await this.$store.dispatch(
            'category/fetchCategoryBooks',
            this.$route.params.category
        )
    },
    data() {
        return {
            page: 1,
            loading: true
        }
    },
    computed: {
        newCategory() {
            const getter = this.$store.getters['category/category']
            return getter(Number(this.$route.params.category))
        },
        ourBooks() {
            return this.newCategory.books
        }
    },
    methods: {
        infiniteScroll($state) {
            if (
                this.newCategory.books.length < this.newCategory.books_count ||
                this.page === 1
            ) {
                this.$store
                    .dispatch('category/fetchAllCategoryBooksPaginated', {
                        categoryId: this.$route.params.category,
                        page: this.page
                    })
                    .then(() => {
                        this.page++
                        $state.loaded()
                    })
            } else {
                $state.complete()
            }
        }
    }
}
</script>

<style lang="scss" scoped>
.book-list-group {
    justify-content: space-evenly;
}
</style>
