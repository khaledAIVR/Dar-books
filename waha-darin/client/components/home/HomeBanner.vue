<template>
    <div class="jumbotron text-start section pt-5 mb-5">
        <div class="row">
            <div class="col-12 col-md-4">
                <h1 class="font-weight-normal mb-5">
                    {{ $t('Dont ever leave') }}
                </h1>
                <p class="lead font-weight-lighter mt-5 mb-5">
                    {{ $t('Subscribe') }}
                </p>
                <div
                    class="btn-group d-flex pr-3 pl-3"
                    role="group"
                    aria-label="Auth buttons"
                >
                    <nuxt-link
                        class="nav-link btn btn-primary btn-lg"
                        :to="{ name: 'register' }"
                    >
                        {{ $t('Register Now') }}
                    </nuxt-link>
                    <nuxt-link
                        class="nav-link btn-outline-primary btn-lg"
                        :to="{ name: 'login' }"
                    >
                        {{ $t('Login') }}
                    </nuxt-link>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <img
                    src="~static/banner.png"
                    class="img-fluid"
                    :alt="$t('Dont ever leave')"
                />
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'HomeBanner'
}
</script>

<style lang="scss">
.jumbotron {
    border-radius: 17px;
    background: #f4f2f2;
    border: 1px solid #f99d0f;
}
.btn-outline-primary {
    margin: 0 15px;
}
</style>
