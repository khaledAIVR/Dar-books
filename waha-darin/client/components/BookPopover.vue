<template>
    <div class="d-flex flex-column">
        <div class="book-header d-flex">
            <LazyImage
                ref="cover"
                :source="book['cover_photo']"
                class="img-fluid rounded mb-2"
                @load="ImageLoad"
            />
            <div class="d-flex flex-column book-info w-100">
                <h4 class="title font-weight-light">
                    {{ book.title | truncate(50) }}
                </h4>
                <p class="author font-weight-lighter">
                    {{ text.author }} {{ book.author.name }}
                </p>
                <p class="rating w-100" />
            </div>
        </div>
        <div class="description mt-3 mb-3 font-weight-lighter">
            {{ book.description | truncate(300) }}
        </div>
        <div class="buttons d-flex flex-column w-100">
            <a
                href="#"
                class="btn btn-primary btn-sm rounded w-100 mb-2 d-flex justify-content-center align-items-center"
                @click.prevent="AddToCartForm"
            >
                <div
                    v-if="cartloading"
                    class="spinner-border spinner-border-sm text-light mx-2"
                    role="status"
                    style="max-width: inherit"
                >
                    <span class="sr-only">{{ $t('Loading') }}</span>
                </div>
                <span v-if="!cartloading">{{ $t('Add to cart') }}</span>
            </a>
            <div class="d-flex w-100">
                <a
                    class="btn btn-primary btn-sm rounded w-100"
                    @click.prevent="goToBookFromSlug(book.slug)"
                >
                    {{ text.details }}
                </a>
                <a
                    href="#"
                    class="btn btn-outline-primary d-flex btn-lg flex-shrink-1 justify-content-center align-items-center m-start-10"
                    @click.prevent="AddToFavListForm"
                >
                    <div
                        v-if="favloading"
                        class="spinner-border text-light mx-2"
                        role="status"
                        style="max-width: inherit"
                    >
                        <span class="sr-only">{{ $t('Loading') }}</span>
                    </div>
                    <i v-if="!favloading" class="gg-heart" />
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'BookPopover',
    props: {
        book: {
            type: Object,
            required: true
        },
        text: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            cartloading: false,
            favloading: false
        }
    },
    methods: {
        AddToCartForm() {
            if (this.$store.getters['auth/check']) {
                this.cartloading = true
                this.addBookToCart()
            } else {
                this.$modal.show('auth-alert')
            }
        },

        async addBookToCart() {
            try {
                const { data } = await axios.patch(`/cart/${this.book.id}`)
                if (data.status === 200) {
                    this.$modal.show('add-to-cart')
                }
            } catch (e) {
                const res = e.response
                const msg =
                    res &&
                    res.data &&
                    typeof res.data.message === 'string' &&
                    res.data.message
                this.$toast.error(msg || this.$t('cart_borrow_limit_toast'))
            } finally {
                this.cartloading = false
            }
        },

        AddToFavListForm() {
            if (this.$store.getters['auth/check']) {
                this.favloading = true
                this.addBookToFav()
            } else {
                this.$modal.show('auth-alert')
            }
        },

        async addBookToFav() {
            const { data } = await axios.patch(`/favourite/${this.book.id}`)
            this.favloading = false
            if (data.status === 200) {
                this.$modal.show('add-to-fav-list')
            }
        }
    }
}
</script>

<style></style>
