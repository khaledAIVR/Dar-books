<template>
    <div>
        <p>{{ $t('Popular Authors') }}</p>

        <ul v-if="!loading" class="list-group">
            <li
                v-for="author in authors.authors.slice(0, 15)"
                :key="author.id"
                class="list-group-item list-group-item-action d-flex align-items-center mb-3"
            >
                <div class="d-flex">
                    <LazyImage
                        ref="cover"
                        width="65px"
                        alt=""
                        class="img-fluid article-item__image auhtor-sm-image"
                        :source="author['avatar_photo']"
                    />
                </div>
                <div class="d-flex flex-column book-desc">
                    <nuxt-link
                        :to="{
                            name: 'author',
                            params: { author: author['id'] }
                        }"
                        class="author-name"
                    >
                        {{ author.name }}
                    </nuxt-link>
                </div>
            </li>
        </ul>
        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center p-5"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div role="separator" class="dropdown-divider" />
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    name: 'AuthorsListSmall',
    data() {
        return {
            loading: true
        }
    },
    computed: mapGetters({
        authors: 'author/authors'
    }),
    async created() {
        await this.$store.dispatch('author/fetchAuthors', {})
    },
    mounted() {
        this.loading = false
    }
}
</script>

<style scoped lang="scss">
li {
    padding: 0.2rem 0.6rem !important;
}

.auhtor-sm-image {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.16);
}

.book-desc {
    a {
        margin: 0.4rem 1rem;
        padding: 0;

        &:hover {
            color: #1b1e21;
        }
    }

    .author-name {
        font-weight: 300;
        font-size: 16px;
    }

    .author {
        font-weight: 100;
        font-size: 14px;
    }
}
</style>
