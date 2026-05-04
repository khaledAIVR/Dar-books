<template>
    <div class="section text-start mb-5">
        <div class="row">
            <b-popover
                :delay="{ show: 500, hide: 50 }"
                triggers="hover"
                placement="auto"
                :target="'tooltip-target-' + book.id"
            >
                <book-popover :book="book" :text="popoverText" />
            </b-popover>
            <nuxt-link
                :id="'tooltip-target-' + book.id"
                :to="{ name: 'book', params: { slug: book.slug } }"
                class="slide-wrapper"
                data-placement="auto"
                data-toggle="popover"
            >
                <LazyImage
                    ref="cover"
                    :source="book['cover_photo']"
                    class="book-cover img-fluid rounded"
                    @load="ImageLoad"
                />
                <h5 class="card-title font-weight-light text-dark mb-2 mt-3">
                    {{ book.title }}
                </h5>
                <nuxt-link
                    :to="{ name: 'author', params: { author: book.author.id } }"
                    class="author font-weight-lighter mb-3 text-dark"
                >
                    {{ $t('Author:') }}
                    {{ book.author.name }}
                </nuxt-link>
            </nuxt-link>
            <button
                class="btn btn-primary btn-sm mt-1 w-100 add-cart-btn"
                :disabled="cartAdding"
                @click.prevent="addToCart"
            >
                <span v-if="cartAdding" class="spinner-border spinner-border-sm" role="status" />
                <span v-else>{{ $t('Add to cart') }}</span>
            </button>
        </div>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'BookList',
    components: {
        LazyImage: () => import('./global/LazyImage'),
        'book-popover': () => import('~/components/BookPopover')
    },
    props: {
        book: {
            type: [Array, Object],
            required: true
        }
    },
    data() {
        return {
            cartAdding: false,
            popoverText: {
                details: this.$t('Details'),
                author: this.$t('Author:')
            },
            starStyle: {
                fullStarColor: '#FED68D',
                emptyStarColor: '#C2C2C2',
                starWidth: 18,
                starHeight: 18
            }
        }
    },
    methods: {
        async addToCart() {
            if (!this.$store.getters['auth/check']) {
                return this.$router.push({ name: 'login' })
            }
            this.cartAdding = true
            try {
                await axios.patch(`/cart/${this.book.id}`)
                this.$modal.show('add-to-cart')
            } catch (e) {
                const msg = e?.response?.data?.message
                this.$toast.error(msg || this.$t('Error adding to cart'))
            } finally {
                this.cartAdding = false
            }
        }
    }
}
</script>

<style lang="scss" scoped>
.slide-wrapper {
    max-width: 185px;
}
.book-cover {
    width: 185px;
    height: 293px;
    object-fit: cover;
    transition: all 0.3s ease-out;
    will-change: scale;

    &:hover {
        transform: scale(1.1);
        transition: all 0.3s ease-out;
    }
}

.categoryBooks a {
    cursor: pointer;

    > * {
        pointer-events: none;
    }

    &.slide-wrapper {
        display: flex;
        justify-content: center;
        flex-direction: column;
        max-width: 185px;
        margin-bottom: 40px;
    }
}
</style>
