<template>
    <vsm-menu
        :base-height="400"
        :base-width="380"
        :menu="menu"
        :screen-offset="10"
        element="header"
    >
        <template #default="{ item }">
            <div class="wrap-content sidebar-block py-3">
                <NavMenuItem v-for="subItem in item.items" :item="subItem" />
            </div>
        </template>

        <template #title="data">
            <div class="d-flex align-items-center">
                <div v-if="data.item.icon">
                    <icon
                        :name="data.item.icon"
                        :title="data.item.title"
                        color="dark-light"
                        size="medium"
                    />
                </div>
                <p class="mx-2 mb-0 font-weight-light text-dark menu-title">
                    {{ data.item.title }}
                </p>
            </div>
        </template>
    </vsm-menu>
</template>

<script>
/* eslint-disable */
    /*
     * This is an example of possible settings, you can also control
     * scss variables, and also you need to add a little style.
     * So copy and delete what you don’t need.
     *
     * After #after-nav and #before-nav it is recommended to use
     * to maintain the correct HTML structure:
     *   <li class="vsm-section">
     */

    import {mapGetters} from "vuex";

    export default {
        name: 'NavMenu',
        data() {
            return {
                menu: [
                    {
                        title: this.$t('Library'),
                        icon: 'books',
                        dropdown: 'news',
                        items: [
                            {
                                title: this.$t('Most popular'),
                                routeName: 'home',
                                icon: 'fire',
                                description: this.$t('There you will find some recommended books to read'),
                            },
                            {
                                title: this.$t('Popular Categories'),
                                routeName: 'categories',
                                icon: 'grid',
                                description: this.$t('The most popular book categories')
                            },
                            {
                                title: this.$t('Popular Authors'),
                                routeName: 'authors',
                                icon: 'pen',
                                description: this.$t('The most popular book authors')
                            },
                        ]
                    },
                    {
                        title: this.$t("About Darin"),
                        dropdown: 'about',
                        items: [
                            // {
                            //     title: this.$t('About Us'),
                            //     routeName: 'about',
                            //     // icon:'books',
                            //     // description: this.$t("Control your preferences & info")
                            // },
                            {
                                title: this.$t('Pricing'),
                                routeName: 'pricing',
                                // icon:'books',
                                // description: this.$t("Control your preferences & info")
                            },
                            {
                                title: this.$t('FAQ'),
                                routeName: 'favourite',
                                // icon:'heart',
                                // description: this.$t("Control your preferences & info")
                            },
                            // {
                            //     title: this.$t('Terms and conditions'),
                            //     routeName: 'terms',
                            //     // icon:'gear',
                            //     // description: this.$t("Control your preferences & info")
                            // },
                        ]
                    },
                ]
            }
        },
        computed: mapGetters({
            user: 'auth/user'
        }),
        created() {
            if (this.user) {
                this.menu.push({
                        title: this.$t('My Account'),
                        icon: 'user-thin',
                        dropdown: 'account',
                        items: [
                            {
                                title: this.$t('My Cart'),
                                routeName: 'cart',
                                icon: 'cart',
                                description: this.$t("The books you added to your cart"),
                            },
                            {
                                title: this.$t('My Orders'),
                                routeName: 'orders',
                                icon: 'books',
                                description: this.$t("View and track your past orders")
                            },
                            {
                                title: this.$t('Favorite books'),
                                routeName: 'favourite',
                                icon: 'heart',
                                description: this.$t("Your favorite books list")
                            },
                            {
                                title: this.$t('settings'),
                                routeName: 'settings.profile',
                                icon: 'gear',
                                description: this.$t("Control your preferences & info")
                            },
                        ]
                    },
                )
            }
        }
    }
</script>

<style lang="scss">
    // Styles, to quickly start using the component
    // You can delete, change or add your own

    // Limit the width to 1024px and center
    .vsm-menu {
        margin: 10px;
        width: 100%;
        z-index: 99;

        ul {
            max-width: 1024px;
            margin: 0 auto;
        }
    }

    // Let's simplify the work with menu items (logo, menu, buttons, etc)
    .vsm-root {
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }


    // Move all the content to the right and reduce the logo
    .logo-section {
        flex: 1 1 auto;

        img {
            user-select: none;
            max-width: 40px;
        }
    }

    // All menu items (element props: a, button, span, etc) are
    // made the same in style
    .vsm-section_menu {
        > * {
            padding: 0 25px;
            font-weight: 500;
            font-family: inherit;
        }
    }

    // Styles for Dropdown Content:
    .wrap-content {
        padding: 0;
        // Set the width manually so that it does not depend
        // on changing content
        width: 400px;
    }

    .wrap-content__block {
        font-weight: bold;
    }

    .wrap-content__item {
        font-style: italic;
        font-size: .8rem;
    }

    .menu-title {
        font-size: 18px;
        margin-top: -5px;
    }

    .vsm-has-dropdown {
        border-right: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    .vsm-background {
        border-radius: 15px;
    }

    html[dir=rtl] {
        .vsm-has-dropdown {
            border-right: none !important;
            border-left: 1px solid rgba(0, 0, 0, 0.05) !important;
        }
    }
</style>
