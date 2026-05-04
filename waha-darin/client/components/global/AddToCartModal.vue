<template>
    <modal
        name="add-to-cart"
        classes="rounded-more v--modal"
        transition="pop-out"
        :width="modalWidth"
        :height="300"
        @opened="AddBookToCart"
    >
        <div class="box p-5">
            <div v-if="!loading">
                <div class="d-flex flex-column align-items-center">
                    <div class="rounded-circle bg-success check-circle">
                        <Icon
                            name="check"
                            :title="$t('Done')"
                            size="large"
                            color="white"
                        />
                    </div>
                    <h2 class="mt-2">
                        {{ $t('Book added') }}
                    </h2>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <nuxt-link
                        :to="{ name: 'cart' }"
                        class="btn btn-primary btn-lg p-0 m-0 align-baseline"
                        @click.native="$modal.hide('add-to-cart')"
                    >
                        {{ $t('View cart') }}
                    </nuxt-link>
                    <button
                        class="btn btn-primary-light btn-lg p-0 m-0 align-baseline"
                        @click="$modal.hide('add-to-cart')"
                    >
                        {{ $t('Browse more books') }}
                    </button>
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
    </modal>
</template>
<script>
const MODAL_WIDTH = 656
export default {
    name: 'AddToCartModal',
    data() {
        return {
            modalWidth: MODAL_WIDTH,
            loading: true,
            book: {}
        }
    },
    created() {
        this.modalWidth =
            window.innerWidth < MODAL_WIDTH ? MODAL_WIDTH / 2 : MODAL_WIDTH
    },
    methods: {
        AddBookToCart() {
            this.loading = false
        }
    }
}
</script>
<style lang="scss" scoped>
.cover-img {
    max-width: 100px;
    height: auto;
}

.check-circle {
    width: 50px;
    height: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 12px;
}

.btn-lg {
    padding: 1rem 2rem !important;
    margin: 5px !important;
}
</style>
