<template>
    <div>
        <div class="container">
            <div class="card border-0 rounded settings-card">
                <h4 class="card-header py-4 bg-white text-start">
                    {{ $t('Account settings') }}
                </h4>
                <div class="card-body py-4">
                    <ul class="nav flex-row nav-pills">
                        <li
                            v-for="tab in tabs"
                            :key="tab.route"
                            class="nav-item"
                        >
                            <nuxt-link
                                :to="{ name: tab.route }"
                                class="nav-link"
                                active-class="active"
                            >
                                <icon
                                    :name="tab.icon"
                                    size="dark-light"
                                    color="dark"
                                />
                                {{ tab.name }}
                            </nuxt-link>
                        </li>
                    </ul>
                </div>
            </div>
            <transition name="fade" mode="out-in">
                <router-view />
            </transition>
        </div>
    </div>
</template>

<script>
export default {
    middleware: 'auth',
    layout: 'edit',
    computed: {
        tabs() {
            return [
                {
                    icon: 'user',
                    name: this.$t('Profile Info'),
                    route: 'settings.profile'
                },
                {
                    icon: 'password',
                    name: this.$t('Change Password'),
                    route: 'settings.password'
                },
                {
                    icon: 'grid',
                    name: this.$t('Favourite categories'),
                    route: 'settings.categories'
                },
                {
                    icon: 'pen',
                    name: this.$t('Favourite authors'),
                    route: 'settings.authors'
                }
            ]
        }
    }
}
</script>

<style lang="scss" scoped>
.settings-card {
    .card-body {
        padding: 0;
    }

    .nav {
        padding: 0;
        justify-content: space-evenly;

        .nav-link {
            font-size: 16px;
            font-weight: 200;
            padding: 1rem;

            svg {
                margin-left: 10px;
            }

            &.active {
                font-weight: 600;
                border-radius: 6px;
                color: #f99d0f;
                background: #ffe8be;
            }
        }
    }
}
</style>
