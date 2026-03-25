<template>
    <div class="col-md-12 col-lg-8 mx-auto mb-5">
        <h1 class="mb-5 text-start">
            {{ $t('My Cart') }}
        </h1>
        <div
            v-if="!loading && books.length > available"
            class="alert alert-warning text-start"
            role="alert"
        >
            {{
                $t('borrow_quota_cart_overflow', {
                    c: books.length,
                    n: available
                })
            }}
        </div>
        <div v-if="!loading && books.length > 0">
            <div
                v-for="book in books"
                :key="book.id"
                class="d-flex cart-item justify-content-between align-items-center mb-5"
            >
                <div class="d-flex">
                    <LazyImage
                        ref="cover"
                        alt=""
                        class="cover-img img-fluid rounded article-item__image"
                        :source="book['cover_photo']"
                        :style="
                            `box-shadow: rgba(183, 183, 183, 0.4) 0px 1px 14px 2px;`
                        "
                    />
                    <div
                        class="text-start d-flex flex-column justify-content-center book-info w-100 px-5"
                    >
                        <nuxt-link
                            :to="{ name: 'book', params: { slug: book.slug } }"
                        >
                            <h4>{{ book.title }}</h4>
                        </nuxt-link>
                        <h6 class="font-weight-lighter">
                            {{ $t('Author:') }} {{ book.author }}
                        </h6>
                    </div>
                </div>
                <a
                    href="#"
                    class="rounded-circle trash-circle mx-5"
                    @click.prevent="deleteFromCart(book.id)"
                >
                    <Icon
                        name="trash"
                        :title="$t('Remove Item')"
                        size="medium"
                        color="red"
                    />
                </a>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <nuxt-link
                    v-if="canCheckout"
                    :to="{ name: 'borrow' }"
                    class="btn btn-primary btn-lg p-0 m-0 align-baseline"
                >
                    {{ $t('Checkout Now') }}
                </nuxt-link>
                <button
                    v-else
                    type="button"
                    class="btn btn-secondary btn-lg p-0 m-0 align-baseline"
                    disabled
                >
                    {{ $t('Checkout Now') }}
                </button>
            </div>
        </div>
        <div v-if="!loading && books.length <= 0">
            <div class="row justify-content-center">
                <div class="col-6">
                    <div class="w-75 d-flex justify-content-center m-auto">
                        <img
                            src="~static/empty-cart.svg"
                            class="img-fluid "
                            alt=""
                        />
                    </div>
                    <div
                        class="d-flex justify-content-center flex-column align-items-center"
                    >
                        <h1 class="my-5">
                            {{ $t('Your cart is empty') }}
                        </h1>
                        <nuxt-link
                            :to="{ name: 'home' }"
                            class="btn btn-primary btn-lg"
                        >
                            {{ $t('Browse Books') }}
                        </nuxt-link>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center p-5"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">{{ $t('Loading') }}</span>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios'

export default {
    name: 'Cart',
    middleware: 'auth',
    async asyncData() {
        try {
            window.$nuxt.$loading.start()
        } catch (e) {}

        try {
            const { data } = await axios.get(`/cart`)
            const available = Math.max(0, parseInt(data.available, 10) || 0)
            return {
                books: data.books || [],
                available,
                loading: false
            }
        } catch (e) {
            return { books: [], available: 0, loading: false }
        }
    },
    data() {
        return {
            loading: true,
            available: 0
        }
    },
    computed: {
        canCheckout() {
            return (
                this.available > 0 &&
                this.books.length > 0 &&
                this.books.length <= this.available
            )
        }
    },
    methods: {
        async deleteFromCart(bookId) {
            try {
                const res = await axios.delete(`/cart/${bookId}`)
                if (res.data.status === 200) {
                    for (const [index, book] of this.books.entries()) {
                        if (book.id === bookId) {
                            this.books.splice(index, 1)
                            break
                        }
                    }
                    const { data } = await axios.get('/cart')
                    this.available = Math.max(
                        0,
                        parseInt(data.available, 10) || 0
                    )
                }
            } catch (e) {}
        }
    }
}
</script>
<style lang="scss" scoped>
.cover-img {
    max-width: 200px;
    height: auto;
}

.cart-item {
    background: #fff;
    padding: 1.5rem 2rem;
    border-radius: 15px;
}

.trash-circle {
    width: 50px;
    height: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 12px;
    background: #ffd9d9;
}

.btn-lg {
    padding: 1rem 2rem !important;
    margin: 5px !important;
}
</style>
