<template>
    <div class="mt-5 container">
        <div class="text-center">
            <h1 class="mb-4">
                {{ $t('Select your favorite categories') }}
            </h1>
            <!--    <h4 class="font-weight-lighter">
                            {{ smallTitle }}
                          </h4>-->
            <div class="row justify-content-center">
                <div
                    v-for="(category, index) in categories.categories"
                    :key="category.id + '' + index"
                    class="col-3 my-5"
                >
                    <a
                        href="#"
                        class="category"
                        @click.prevent="SelectCategory($event, category.id)"
                    >
                        <div class="hoverArea">
                            <LazyImage
                                alt=""
                                class="img-fluid rounded-circle -fluid article-item__image auhtor-sm-image"
                                :source="getCategoryImageUrl(category)"
                                :img-styles="'border-radius:50%'"
                            />
                            <div class="middle">
                                <Icon
                                    name="check"
                                    :title="$t('Use')"
                                    size="large"
                                    color="white"
                                />
                            </div>
                        </div>
                        <h2 class="mt-2 text-dark">{{ $i18n.categoryName(category) }}</h2>
                    </a>
                </div>
                <div class="my-5" />
                <div class="col-12 mt-5 d-flex justify-content-center">
                    <button
                        type="submit"
                        class="btn btn-primary btn-lg d-flex justify-content-center align-items-center btn-50"
                        :disabled="loading"
                        :class="{ disabled: loading }"
                        @click.prevent="update"
                    >
                        <div
                            v-if="loading"
                            class="spinner-border text-light mx-2"
                            role="status"
                        >
                            <span class="sr-only">{{ $t('Loading') }}</span>
                        </div>
                        {{ $t('Save Changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'
import axios from 'axios'
import { getCategoryImageUrl } from '~/data/categoryImages'

export default {
    middleware: 'auth',
    scrollToTop: false,
    async fetch() {
        await this.$store.dispatch('category/fetchCategories', {
            page: this.page,
            per_page: 15
        })
    },
    data() {
        return {
            page: 1,
            loading: false,
            selectedCategories: []
        }
    },
    computed: mapGetters({
        categories: 'category/categories'
    }),
    methods: {
        getCategoryImageUrl,
        async update() {
            const formData = { categories: this.selectedCategories }
            const { data } = await axios.patch('/settings/categories', formData)
            if (data.status === 200) {
                this.$toast.success(this.$t('Profile Updated successfully'))
            } else {
                this.$toast.error(this.$t('Error Updating profile'))
            }
        },

        SelectCategory(event, authorId) {
            event.target.classList.toggle('selected')
            const category = this.selectedCategories.find(
                (id) => id === authorId
            )

            if (!category) {
                this.selectedCategories.push(authorId)
            } else {
                for (const [
                    index,
                    category
                ] of this.selectedCategories.entries()) {
                    if (category === authorId) {
                        this.selectedCategories.splice(index, 1)
                    }
                }
            }
            if (this.selectedCategories.length > 2) {
                this.loadMore()
            }
        },
        loadMore() {
            if (
                this.categories.categories.length < this.categories.total ||
                this.page === 1
            ) {
                this.page++
                this.$store
                    .dispatch('category/fetchCategories', {
                        page: this.page,
                        per_page: 5
                    })
                    .then(() => {})
            }
        }
    },
    head() {
        return { title: this.$t('Submit and continue') }
    }
}
</script>
<style lang="scss" scoped>
.btn {
    .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
    }
}

.category {
    * {
        pointer-events: none;
    }

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    .hoverArea {
        figure {
            height: 183px;
            width: 183px;
            max-width: unset;
            object-fit: cover;
            transition: 0.5s ease;
            backface-visibility: hidden;
        }

        .middle {
            transition: 0.5s ease;
            opacity: 0;
            position: absolute;
            top: 37%;
            left: 50%;
            transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
        }
    }

    &:hover {
        .hoverArea {
            figure {
                background: #43425d;
                filter: brightness(0.4);
                /*transform: scale(1.1);*/
            }

            .middle {
                opacity: 1;
            }
        }
    }

    &.selected {
        .hoverArea {
            figure {
                background: #43425d;
                filter: brightness(0.4);
                transform: scale(1.1);
            }

            .middle {
                opacity: 1;
            }
        }
    }
}
</style>
