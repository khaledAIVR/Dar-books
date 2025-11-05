<template>
    <li class="input-group align-items-center">
        <autocomplete
            class="w-100"
            auto-select
            :search="search"
            :get-result-value="getResultValue"
            :placeholder="$t('Search by book name, author or category')"
            :aria-label="$t('Search by book name, author or category')"
            @submit="onSubmit"
        >
            <template #result="{ result, props }">
                <li v-bind="props" class="books-wrapper">
                    <LazyImage
                        ref="cover"
                        alt=""
                        class="img-fluid article-item__image"
                        :source="result['cover_photo']"
                    />
                    <div class="d-flex flex-column book-desc">
                        <nuxt-link
                            :to="{
                                name: 'book',
                                params: { slug: result.slug }
                            }"
                            class="book-title"
                            :active-class="'non'"
                            :exact-active-class="'non'"
                        >
                            {{ result.title | truncate(50) }}
                        </nuxt-link>

                        <nuxt-link
                            :to="{
                                name: 'author',
                                params: { author: result.author['id'] }
                            }"
                            :exact-active-class="'non'"
                            class="author"
                        >
                            {{ $t('Author:') }}
                            {{ result.author.name }}
                        </nuxt-link>
                    </div>
                </li>
            </template>
        </autocomplete>
    </li>
</template>

<script>
import Autocomplete from '@trevoreyre/autocomplete-vue'
import axios from 'axios'

export default {
    name: 'Search',
    components: {
        Autocomplete
    },
    data() {
        return {
            starStyle: {
                fullStarColor: '#F99D0F',
                emptyStarColor: '#C2C2C2',
                starWidth: 18,
                starHeight: 18
            },
            rating: 5
        }
    },
    methods: {
        search(input) {
            this.stretch = true
            const params = `?search=${encodeURI(input)}`
            return new Promise((resolve) => {
                if (input.length < 3) {
                    return resolve([])
                }
                axios.get(`/books${params}`).then((data) => {
                    resolve(data.data.data)
                })
            })
        },
        onSubmit(result) {
            this.$router.push({ name: 'book', params: { slug: result.slug } })
        },
        getResultValue(result) {
            return result.title
        }
    }
}
</script>

<style lang="scss">
@import '@trevoreyre/autocomplete-vue/dist/style.css';
input {
    border-radius: 8px !important;
}
.autocomplete-result-list {
    max-height: 70vh;
    z-index: 2 !important;
    li {
        cursor: pointer;
    }
}
.books-wrapper {
    display: flex;
    align-items: center;
    .article-item__image {
        width: 65px;
        border-radius: 3px;
        box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.16);
    }

    .book-desc {
        a {
            margin: 0 1rem 0rem 1rem;
            padding: 0;
            line-height: 1.4;
            &:hover {
                color: #1b1e21;
            }
        }

        .book-title {
            font-weight: 300;
            font-size: 16px;
            color: #43425d;
        }

        .author {
            font-weight: 100;
            font-size: 14px;
            color: #43425d;
        }

        .star-rating {
            margin: 0.4rem 1rem;

            .star-container {
                margin-right: 2px !important;
            }
        }
    }
}
.autocomplete-input {
    background-color: #fff;
    border-color: #eee;
}
</style>
<style scoped>
.input-group {
    margin-bottom: 1rem;
}
</style>
