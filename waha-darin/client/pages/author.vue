<template>
    <div>
        <auth-alert />
        <div
            v-if="newAuthor && newAuthor.books && newAuthor.books.length > 0"
            class="wrapper"
        >
            <div class="section text-start">
                <div class="d-flex justify-content-start mb-5">
                    <h1>
                        {{ newAuthor.name }}
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
            'author/fetchAuthorBooks',
            this.$route.params.author
        )
    },
    data() {
        return {
            page: 1,
            loading: true
        }
    },
    computed: {
        newAuthor() {
            const getter = this.$store.getters['author/author']
            return getter(Number(this.$route.params.author))
        },
        ourBooks() {
            return this.newAuthor.books
        }
    },
    methods: {
        infiniteScroll($state) {
            if (
                this.newAuthor.books.length < this.newAuthor.books_count ||
                this.page === 1
            ) {
                this.$store
                    .dispatch('author/fetchAllAuthorBooksPaginated', {
                        authorId: this.$route.params.author,
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
