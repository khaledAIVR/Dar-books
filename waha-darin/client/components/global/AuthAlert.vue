<template>
    <modal
        name="auth-alert"
        classes="rounded-more v--modal"
        transition="pop-out"
        :width="modalWidth"
        :height="300"
    >
        <div class="box p-5">
            <div v-if="!loading">
                <div class="d-flex flex-column align-items-center">
                    <h2 class="mt-2 text-center">
                        {{
                            $t('Please login or create an account to continue')
                        }}
                    </h2>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <nuxt-link
                        :to="{ name: 'register' }"
                        class="btn btn-primary btn-lg p-0 m-0 align-baseline"
                    >
                        {{ $t('Create New Account') }}
                    </nuxt-link>
                    <nuxt-link
                        :to="{ name: 'login' }"
                        class="btn btn-primary-light btn-lg p-0 m-0 align-baseline"
                    >
                        {{ $t('Login') }}
                    </nuxt-link>
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
    name: 'AuthAlert',
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
    mounted() {
        this.loading = false
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
