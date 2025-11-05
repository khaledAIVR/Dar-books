<template>
    <div class="text-start">
        <h1 class="mb-4">
            {{ $t('Book Authors') }}
        </h1>
        <div class="row justify-content-center">
            <div
                v-for="(author, index) in authors.authors"
                :key="author.id + '' + index"
                class="col-3 my-5 text-center"
            >
                <nuxt-link
                    :to="{ name: 'author', params: { author: author.id } }"
                    class="author"
                    :data-author-id="author.id"
                >
                    <div class="hoverArea">
                        <img
                            data-lazy-load
                            :src="author['avatar_photo']"
                            class="cat-img img-fluid rounded-circle"
                            alt=""
                        />
                        <div class="middle">
                            <icon
                                name="bookOpen"
                                title="Use"
                                size="vlarge"
                                color="white"
                            />
                        </div>
                    </div>
                    <h2 class="mt-2 text-dark">
                        {{ author.name }}
                    </h2>
                </nuxt-link>
            </div>
        </div>
        <div class="my-5" />
        <infinite-loading spinner="spiral" @infinite="infiniteScroll" />
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    name: 'AuthorsList',
    computed: mapGetters({
        authors: 'author/authors'
    }),
    async created() {
        await this.$store.dispatch('author/fetchAuthors', {})
    },
    data() {
        return {
            page: 1
        }
    },
    methods: {
        infiniteScroll($state) {
            if (
                this.authors.authors.length < this.authors.total ||
                this.page === 1
            ) {
                this.page++
                this.$store
                    .dispatch('author/fetchAuthors', { page: this.page })
                    .then(() => {
                        $state.loaded()
                    })
            } else {
                $state.complete()
            }
        }
    }
}
</script>

<style lang="scss" scoped>
.author {
    * {
        pointer-events: none;
    }

    .hoverArea {
        display: flex;
        justify-content: center;
        align-items: center;
        .cat-img {
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
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
        }
    }

    &:hover {
        .hoverArea {
            img {
                background: #43425d;
                filter: brightness(0.4);
                /*transform: scale(1.1);*/
            }

            .middle {
                opacity: 1;
            }
        }
    }
}
</style>
