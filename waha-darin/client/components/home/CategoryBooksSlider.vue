<template>
    <div class="section text-start">
        <auth-alert />
        <div class="d-flex justify-content-between mb-5">
            <h1>
                <span
                    v-if="category.color"
                    class="circle"
                    :style="`background: ${category.color}`"
                />
                {{ $i18n.categoryName(category) }}
            </h1>
            <div class="d-flex swiper-buttons">
                <div
                    slot="button-prev"
                    class="swiper-button-prev swiper-button-black"
                    :class="'swiper-button-prev' + category.id"
                />
                <div
                    slot="button-next"
                    class="swiper-button-next swiper-button-black"
                    :class="'swiper-button-next' + category.id"
                />
            </div>
        </div>
        <swiper
            v-if="
                newCategory && newCategory.books && newCategory.books.length > 1
            "
            class="swiper categoryBooks"
            :class="category.id"
            :options="swiperOption"
            :auto-update="true"
            @slider-move="onSwiperSlideMoveStart"
        >
            <swiper-slide v-for="book in newCategory.books" :key="book.book_id">
                <nuxt-link
                    :id="'tooltip-target-' + book.id + category.id"
                    :to="{ name: 'book', params: { slug: book.slug } }"
                    class="slide-wrapper"
                    tabindex="0"
                    data-toggle="popover"
                    :data-book-id="book.id"
                    data-placement="auto"
                >
                    <b-popover
                        :delay="{ show: 500, hide: 50 }"
                        triggers="hover"
                        placement="auto"
                        :target="'tooltip-target-' + book.id + category.id"
                    >
                        <book-popover :book="book" :text="popoverText" />
                    </b-popover>
                    <LazyImage
                        ref="cover"
                        alt=""
                        class="book-cover img-fluid rounded article-item__image"
                        :source="book['cover_photo']"
                        :style="
                            `box-shadow: rgba(183, 183, 183, 0.4) 0px 1px 14px 2px;`
                        "
                    />

                    <h5
                        class="card-title font-weight-light text-dark mb-2 mt-3"
                    >
                        {{ book.title }}
                    </h5>
                    <p class="author font-weight-lighter mb-3 text-dark">
                        {{ $t('Author:') }}
                        {{ book.author.name }}
                    </p>
                </nuxt-link>
            </swiper-slide>
        </swiper>
        <div
            v-if="
                !newCategory ||
                    !(newCategory.books && newCategory.books.length > 1)
            "
            class="d-flex justify-content-center align-items-center p-5"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">{{ $t('Loading') }}</span>
            </div>
        </div>
        <hr class="my-1 m-5" />
    </div>
</template>

<script>
import { Swiper, SwiperSlide } from 'vue-awesome-swiper'

export default {
    name: 'CategoryBooksSlider',
    components: {
        Swiper,
        SwiperSlide,
        'book-popover': () => import('~/components/BookPopover')
    },
    props: {
        category: {
            type: Object,
            required: true
        }
    },
    async fetch() {
        await this.$store.dispatch(
            'category/fetchCategoryBooks',
            this.category.id
        )
    },
    data() {
        return {
            popoverText: {
                details: this.$t('Details'),
                author: this.$t('Author:')
            },
            swiperOption: {
                loop: false,
                slidesPerView: 'auto',
                spaceBetween: 30,
                height: 'auto',
                keyboard: {
                    enabled: true
                },
                navigation: {
                    nextEl: '.swiper-button-next' + this.category.id,
                    prevEl: '.swiper-button-prev' + this.category.id
                }
            },
            starStyle: {
                fullStarColor: '#FED68D',
                emptyStarColor: '#C2C2C2',
                starWidth: 18,
                starHeight: 18
            }
        }
    },
    computed: {
        newCategory() {
            const getter = this.$store.getters['category/category']
            return getter(this.category.id)
        }
    },
    methods: {
        onSwiperSlideMoveStart() {
            this.$root.$emit('bv::hide::popover')
        }
    }
}
</script>

<style lang="scss">
.btn {
    .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
    }
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

.swiper-button-prev,
.swiper-button-next {
    left: unset;
    right: unset;
    position: inherit;
    top: unset;
    width: unset;
    height: unset;
    margin: 0 11px;
}

.swiper-button-prev:after,
.swiper-button-next:after {
    font-size: 28px;
    font-weight: bold;
}

.popover {
    height: auto;
    width: 320px;
    max-width: unset;
    border: 1px solid #d8d8d8;
    box-shadow: 0px 8px 23px rgba(74, 74, 74, 0.16);
    padding: 0;

    .popover-body {
        height: 100%;
        padding: 18px;

        p {
            font-size: 1rem;
        }

        .book-header {
            img {
                height: 143px;
                width: 92px;
                object-fit: cover;
            }

            .book-info {
                padding-left: 18px;
                padding-right: unset;
            }
        }

        .description {
            overflow: hidden;
            font-size: 16px;
        }

        .buttons a {
            &:first-child {
                flex-grow: 1;
                font-size: 14px;
            }

            display: flex;
            justify-content: center;
            align-items: center;
        }
    }
}

html[dir='rtl'] {
    .popover-body {
        .book-info {
            padding-right: 18px !important;
            padding-left: unset !important;
        }
    }
}
</style>
