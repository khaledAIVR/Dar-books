<template>
    <div>
        <p class="mb-0">
            {{ $t('Suggested Books') }}
        </p>

        <ul v-if="!loading" class="list-group">
            <li
                v-for="book in books"
                :key="book.book_id"
                class="list-group-item list-group-item-action d-flex align-items-start"
            >
                <div class="d-flex">
                    <LazyImage
                        ref="cover"
                        alt=""
                        class="img-fluid article-item__image"
                        :source="book['cover_photo']"
                    />
                </div>
                <div class="d-flex flex-column book-desc">
                    <nuxt-link
                        :to="{ name: 'book', params: { slug: book.slug } }"
                        class="book-title"
                        :active-class="'non'"
                        :exact-active-class="'non'"
                    >
                        {{ book.title | truncate(50) }}
                    </nuxt-link>

                    <nuxt-link
                        :to="{
                            name: 'author',
                            params: { author: book.author['id'] }
                        }"
                        :exact-active-class="'non'"
                        class="author"
                    >
                        {{ $t('Author:') }}
                        {{ book.author.name }}
                    </nuxt-link>
                </div>
            </li>
        </ul>
        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center p-5"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">{{ $t('Loading') }}</span>
            </div>
        </div>
        <div role="separator" class="dropdown-divider" />
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    name: 'BookListSmall',
    data() {
        return {
            starStyle: {
                fullStarColor: '#F99D0F',
                emptyStarColor: '#C2C2C2',
                starWidth: 18,
                starHeight: 18
            },
            rating: 5,
            loading: true
        }
    },
    computed: mapGetters({
        books: 'book/books'
    }),
    mounted() {
        this.loading = false
    }
}
</script>

<style scoped lang="scss">
li {
    padding: 1rem !important;
}

img {
    width: 65px;
    border-radius: 3px;
    box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.16);
}

.book-desc {
    padding: 0.3rem;
    a {
        margin: 0 1rem 0rem 1rem;
        padding: 0;
        line-height: 1.4;
        line-break: auto;
        &:hover {
            color: #1b1e21;
        }
    }

    .book-title {
        font-weight: 300;
        font-size: 16px;
        line-break: auto;
    }

    .author {
        font-weight: 100;
        font-size: 14px;
        line-break: auto;
    }

    .star-rating {
        margin: 0.4rem 1rem;

        .star-container {
            margin-right: 2px !important;
        }
    }
}
</style>
