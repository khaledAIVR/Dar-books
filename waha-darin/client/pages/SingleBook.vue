<template>
    <div>
        <add-to-cart-modal />
        <auth-alert />

        <div>
            <div class="d-flex flex-column p-5 bg-white rounded">
                <div class="d-flex">
                    <LazyImage
                        :source="book['cover_photo']"
                        alt=""
                        class="cover-img img-fluid rounded mb-2"
                    />
                    <div
                        class="text-start d-flex flex-column book-info w-100 px-5 justify-content-between"
                    >
                        <h1>{{ book.title }}</h1>
                        <nuxt-link
                            :to="{
                                name: 'author',
                                params: { author: book['author']['id'] }
                            }"
                            class="width-fit"
                        >
                            <h4 class="font-weight-lighter my-3">
                                {{ $t('Author:') }} {{ book['author']['name'] }}
                            </h4>
                        </nuxt-link>
                        <!--                        <star-rating
                            :is-indicator-active="false"
                            :rating="5"
                            :star-style="starStyle"
                        />-->
                        <div
                            class="description my-3"
                            v-html="book.description"
                        />
                        <div class="buttons d-flex w-50">
                            <a
                                class="btn btn-primary btn-lg d-flex flex-grow-1 justify-content-center align-items-center"
                                href="#"
                                @click.prevent="AddToCartForm"
                            >
                                <div
                                    v-if="cartloading"
                                    class="spinner-border text-light mx-2"
                                    role="status"
                                >
                                    <span class="sr-only">{{ $t('Loading') }}</span>
                                </div>
                                <Icon
                                    color="white"
                                    name="cartAdd"
                                    size="medium"
                                    :title="$t('Add to cart')"
                                />
                                {{ $t('Add to cart') }}
                            </a>
                            <a
                                class="btn btn-outline-primary d-flex btn-lg flex-shrink-1 justify-content-center align-items-center m-start-10"
                                href="#"
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
            </div>
            <div class="my-5" />
            <div class="p-5 bg-white rounded">
                <div class="row">
                    <div class="col-6 text-start">
                        <h3>{{ $t('Book Details:') }}</h3>
                        <ul class="book-details">
                            <li>
                                <span>{{ $t('Categories:') }}</span>
                                <ul>
                                    <li
                                        v-for="category in book.categories"
                                        :key="category.id"
                                        class="cat"
                                    >
                                        <nuxt-link
                                            :style="
                                                `color: ${
                                                    category.color
                                                        ? category.color
                                                        : '#F99D0F'
                                                }`
                                            "
                                            :to="{
                                                name: 'category',
                                                params: {
                                                    category: category.id
                                                }
                                            }"
                                        >
                                            {{ $i18n.categoryName(category) }}
                                        </nuxt-link>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <span>{{ $t('Publisher:') }}</span>
                                <ul>
                                    <li class="cat">
                                        <p class="m-0">
                                            {{ book.publisher.name }}
                                        </p>
                                    </li>
                                </ul>
                            </li>
                            <li v-if="book.internal_code">
                                <span>{{ $t('Internal Code:') }}</span>
                                <ul>
                                    <li class="cat">
                                        <p class="m-0">
                                            {{ book.internal_code }}
                                        </p>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <span>ISBN: </span>
                                <ul>
                                    <li class="cat">
                                        {{ book.ISBN }}
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <span>{{ $t('Year:') }}</span>
                                <ul>
                                    <li class="cat">
                                        {{ book.year }}
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div v-if="pages.length > 0" class="col-6 text-start">
                        <h3>{{ $t('From the book') }}</h3>
                        <swiper
                            :auto-update="true"
                            :options="swiperOption"
                            class="swiper colorFull"
                        >
                            <swiper-slide
                                v-for="page in pages"
                                :key="page + Math.random()"
                            >
                                <img
                                    :src="page"
                                    alt=""
                                    class="rounded img-fluid"
                                    @load="ImageLoad"
                                />
                            </swiper-slide>
                            <div slot="pagination" class="swiper-pagination" />
                        </swiper>
                    </div>
                </div>
            </div>
            <div class="my-5" />
        </div>
        <category-books-slider
            v-for="category in book.categories"
            v-if="book.categories && book.categories.length > 0"
            :key="category.id"
            :category="category"
        />
    </div>
</template>

<script>
import axios from 'axios'
import { Swiper, SwiperSlide } from 'vue-awesome-swiper'
import LazyImage from '../components/global/LazyImage'

export default {
    name: 'SingleBook',
    components: {
        LazyImage,
        Swiper,
        SwiperSlide,
        'add-to-cart-modal': () => import('~/components/global/AddToCartModal'),
        'category-books-slider': () =>
            import('~/components/home/CategoryBooksSlider')
    },
    async asyncData({ params }) {
        try {
            window.$nuxt.$loading.start()
        } catch (e) {}
        try {
            const { data } = await axios.get(`/books/${params.slug}`)
            return { book: data }
        } catch (e) {
            // debugger
        }
    },
    data() {
        return {
            starStyle: {
                fullStarColor: '#FED68D',
                emptyStarColor: '#C2C2C2',
                starWidth: 28,
                starHeight: 28
            },
            favloading: false,
            cartloading: false,
            pages: [],
            swiperOption: {
                // mousewheel: true,
                grabCursor: true,
                slidesPerView: 3,
                height: 'auto',
                spaceBetween: 15,
                keyboard: {
                    enabled: true
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true
                }
            }
        }
    },
    mounted() {
        this.extractPagesImages()
    },
    methods: {
        /* Voyager can't upload multiple images yet, so the admin uploads the images in a rich text area
         *  Holding a lot of another HTML tags.
         *  Here we create a v dom element and extract the src of images to use in swiper later
         *  Just a hand clap will be enough !
         */
        extractPagesImages() {
            const el = document.createElement('div')
            el.innerHTML = this.book.pages_screenshots

            const images = el.getElementsByTagName('img')

            for (const img of images) {
                this.pages.push(img.src)
            }
        },

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
                this.$toast.error(
                    msg || this.$t('cart_borrow_limit_toast')
                )
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

<style lang="scss" scoped>
.description {
    font-size: 18px;
    font-weight: 200;
    line-height: 1.8;
}

.width-fit {
    width: fit-content;
}

.cover-img {
    max-width: 250px;
    align-self: flex-start;
}

.book-details {
    font-size: 18px;

    li {
        margin-top: 5px;

        span {
        }

        ul {
            display: inline-block;
            padding-right: 15px;
        }

        li.cat {
            display: inline-block;
            font-weight: 200;

            a {
                font-weight: 200;
                margin: 5px;

                &::after {
                    content: ',';
                }
            }

            &:last-child {
                a {
                    &::after {
                        content: '';
                    }
                }
            }
        }
    }
}
</style>
