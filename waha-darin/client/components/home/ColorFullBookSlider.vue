<template>
    <div class="section text-start">
        <h1 v-if="title" class="mb-0 text-dark">
            {{ $t('Popular Books') }}
        </h1>
        <swiper
            v-if="!loading"
            class="swiper colorFull"
            :options="swiperOption"
            :auto-update="true"
        >
            <swiper-slide v-for="book in books.slice(0, 10)" :key="book.id">
                <div class="slide-wrap rounded">
                    <div class="card text-white">
                        <div
                            class="card-img img-fluid rounded"
                            alt=""
                            :style="{
                                backgroundImage: `url( ${getValidUrl(
                                    book['cover_photo']
                                )} )`
                            }"
                        />
                        <div class="card-img-overlay">
                            <div class="d-flex p-15 justify-content-between">
                                <div class="p-0">
                                    <h5
                                        class="card-title font-weight-light mb-1"
                                    >
                                        {{ book.title | truncate(45) }}
                                    </h5>
                                    <p class="author font-weight-lighter mb-1">
                                        {{ $t('Author:') }}
                                        {{ book.author.name }}
                                    </p>
                                    <p class="desc p-2">
                                        {{ book.description | truncate(55) }}
                                    </p>
                                    <a
                                        class="btn-primary btn-sm rounded-pill w-100"
                                        @click.prevent="
                                            goToBookFromSlug(book.slug)
                                        "
                                    >
                                        {{ $t('Details') }}
                                    </a>
                                </div>
                                <img
                                    data-lazy-load
                                    :src="book['cover_photo']"
                                    class="book-cover img-fluid rounded"
                                    :style="
                                        `box-shadow: 0px 10px 14px rgba(72, 176, 219, 0.4)`
                                    "
                                    @load="ImageLoad"
                                />
                            </div>
                        </div>
                    </div>
                    <div
                        class="color-full-shadow bg-js-lay"
                        :style="{
                            backgroundImage: `url( ${getValidUrl(
                                book['cover_photo']
                            )} )`
                        }"
                    />
                </div>
            </swiper-slide>
            <div slot="pagination" class="swiper-pagination" />
        </swiper>
        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center p-5"
            style="min-height: 100vh"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">{{ $t('Loading') }}</span>
            </div>
        </div>
        <hr class="my-1" />
    </div>
</template>

<script>
import { Swiper, SwiperSlide } from 'vue-awesome-swiper'

export default {
    name: 'ColorFullBookSlider',
    components: {
        Swiper,
        SwiperSlide
    },
    props: {
        title: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            swiperOption: {
                // mousewheel: true,
                grabCursor: true,
                slidesPerView: 'auto',
                spaceBetween: 30,
                keyboard: {
                    enabled: true
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true
                }
            },
            starStyle: {
                fullStarColor: '#FED68D',
                emptyStarColor: '#C2C2C2',
                starWidth: 18,
                starHeight: 18
            },
            loading: true
        }
    },
    computed: {
        books() {
            return this.$store.getters['book/books']
        }
    },
    mounted() {
        this.initSwiperWhenBooksDone()
    },
    methods: {
        initSwiperWhenBooksDone() {
            const that = this
            const doWeHaveBooks = () => {
                if (that.books && that.books.length > 1) {
                    that.loading = false
                    clearInterval(checkInterval)
                }
            }

            const checkInterval = setInterval(doWeHaveBooks, 100)
        }
    }
}
</script>

<style scoped lang="scss">
@mixin better-blur($radius) {
    filter: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='a' x='0' y='0' width='1' height='1' color-interpolation-filters='sRGB'%3E%3CfeGaussianBlur stdDeviation='#{$radius}' result='b'/%3E%3CfeMorphology operator='dilate' radius='#{$radius}'/%3E %3CfeMerge%3E%3CfeMergeNode/%3E%3CfeMergeNode in='b'/%3E%3C/feMerge%3E%3C/filter%3E %3C/svg%3E#a")
        brightness(0.8);
}

.slide-wrap {
    height: auto;
    width: 100%;

    .card {
        overflow: hidden;
        position: initial;
        height: 225px !important;
        border: none;

        .card-img {
            height: 225px !important;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            @include better-blur(20);
        }

        .card-img-overlay {
            top: unset;
            right: unset;
            bottom: unset;
            left: unset;
            width: 100%;

            img {
                margin-bottom: -50px;
                max-width: 135px;
                width: 140px;
                height: 220px;
                object-fit: cover;
            }
        }

        .desc {
            font-size: 14px;
            font-weight: 100;
        }
    }
    .btn-primary {
        cursor: pointer !important;
    }
    .color-full-shadow {
        filter: blur(10px);
        position: absolute;
        top: 70px;
        left: -2px;
        right: 0px;
        bottom: 70px;
        z-index: -1;
        width: auto;
        opacity: 0;
        transition: all 0.21s ease-out;
        will-change: opacity;
    }

    &:hover .color-full-shadow {
        opacity: 1;
        transition: all 0.2s ease-out;
    }
}
</style>
