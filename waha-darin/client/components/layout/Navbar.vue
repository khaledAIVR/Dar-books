<template>
    <nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
        <div class="col-10 mx-auto nav-wrap">
            <nuxt-link :to="{ name: 'home' }" class="navbar-brand">
                <img
                    class="logo-img"
                    alt="Wahat Darin"
                    src="~static/darin-logo-ltr.png"
                />
            </nuxt-link>

            <div>
                <div class="col-10 justify-content-center">
                    <div id="navbarToggler" class="collapse navbar-collapse">
                        <NavMenu />
                        <locale-dropdown />
                    </div>
                </div>
            </div>
            <!--     End     -->
            <ul class="navbar-nav">
                <template v-if="!user">
                    <li class="nav-item">
                        <nuxt-link
                            :to="{ name: 'register' }"
                            active-class="active"
                            class="nav-link mx-2 btn btn-primary btn-lg"
                        >
                            {{ $t('Register Now') }}
                        </nuxt-link>
                    </li>
                    <li class="nav-item">
                        <nuxt-link
                            class="nav-link btn-outline-primary btn-lg login"
                            :to="{ name: 'login' }"
                        >
                            {{ $t('Login') }}
                        </nuxt-link>
                    </li>
                </template>
            </ul>
        </div>
    </nav>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    components: {
        LocaleDropdown: () => import('./LocaleDropdown')
    },

    data: () => ({
        appName: process.env.appName
    }),

    computed: mapGetters({
        user: 'auth/user'
    })
}
</script>
<style lang="scss">
.login {
    &:hover {
        color: #fff !important;
    }
}
</style>

<style lang="scss" scoped>
.nav-item {
    display: flex !important;
    align-items: center;
}

.nav-wrap {
    display: flex;
    justify-content: center;
}

@media (max-width: 1000px) {
    .nav-wrap {
        flex-direction: column;
        align-items: center;
    }
}
</style>
