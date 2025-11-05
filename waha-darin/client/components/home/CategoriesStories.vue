<template>
    <div class="section text-start">
        <h1 class="mb-2">
            {{ $t('Latest Books') }}
        </h1>
        <div id="stories">
            <div
                v-if="loading"
                class="d-flex justify-content-center align-items-center p-5"
                style="min-height: 300px"
            >
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
        <hr class="my-1" />
    </div>
</template>

<script>
import Zuck from 'zuck.js/src/zuck'
import { mapGetters } from 'vuex'

export default {
    name: 'CategoriesStories',

    data() {
        return {
            storiesElement: 'stories',
            stories: [],
            loading: true
        }
    },
    computed: mapGetters({
        categories: 'category/categories'
    }),
    async created() {
        await this.$store.dispatch('category/fetchCategories', {})

        for (const category of this.categories.categories) {
            if (category.books_count > 0) {
                await this.addStory(category)
            }
        }

        this.initStories()
    },

    methods: {
        // Create story for the category
        async addStory(category) {
            const booksArray = []
            // Get Array of books from the category
            if (category.books) {
                for (const book of category.books) {
                    booksArray.push([
                        book.id,
                        'photo',
                        333,
                        book.cover_photo,
                        book.cover_photo,
                        book.slug,
                        'تفاصيل',
                        this.timestamp(),
                        false
                    ])
                }
            }

            // Push Story item using category data
            this.stories.push(
                Zuck.buildTimelineItem(
                    category.slug,
                    category.image_url,
                    category.name,
                    '',
                    this.timestamp(),
                    booksArray
                )
            )
        },

        // Initialize Zuck.js stories
        initStories() {
            new Zuck(this.storiesElement, {
                autoFullScreen: false,
                avatars: true,
                paginationArrows: true,
                list: false,
                cubeEffect: true,
                localStorage: true,
                stories: this.stories,
                language: {
                    unmute: 'اضغط لفتح الصوت ',
                    keyboardTip: 'اضغط مسطرة لعرض القادم',
                    visitLink: 'زيارة الرابط',
                    time: {
                        ago: 'منذ',
                        hour: 'ساعة',
                        hours: 'ساعات',
                        minute: 'دقيقة',
                        minutes: 'دقائق',
                        fromnow: 'منذ الآن',
                        seconds: 'ثواني',
                        yesterday: 'الأمس',
                        tomorrow: 'غدا',
                        days: 'أيام'
                    }
                }
            })
            this.loading = false
        }
    }
}
</script>

<style lang="scss">
@import 'zuck.js/dist/zuck.min.css';
@import '../../assets/sass/elements/stories';

html[dir='rtl'] {
    #zuck-modal-content .story-viewer .head .right {
        float: left;
    }

    #zuck-modal-content .story-viewer .head .left {
        float: right;
    }

    /*Start Overriding Zack.js*/
    #zuck-modal-content .story-viewer .slides-pagination .previous {
        left: unset !important;
        right: 0 !important;
    }

    #zuck-modal-content .story-viewer .slides-pagination .next {
        right: unset !important;
        left: 0 !important;
    }

    #zuck-modal-content .story-viewer .head .right .close {
        color: #fff !important;
    }

    .stories.carousel .story > .item-link > .info .name {
        font-size: 14px;
        white-space: normal;
    }

    #zuck-modal-content .story-viewer .head .right .close {
        display: block !important;
        z-index: 99999999999;
    }

    #zuck-modal-content .story-viewer .slides-pointers {
        z-index: 999 !important;
    }

    /*End Overriding Zack.js*/
}
</style>
