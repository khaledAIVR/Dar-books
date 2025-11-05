<template>
    <div class="col-md-12 col-lg-10 mx-auto mb-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div
                    v-if="loading"
                    class="d-flex justify-content-center align-items-center p-5"
                >
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div v-if="!loading && orders.length <= 0">
                    <div class="row justify-content-center">
                        <div class="col-6">
                            <div
                                class="w-75 d-flex justify-content-center m-auto"
                            >
                                <img
                                    src="~static/empty-cart.svg"
                                    class="img-fluid "
                                    alt=""
                                />
                            </div>
                            <div
                                class="d-flex justify-content-center flex-column align-items-center"
                            >
                                <h1 class="my-5">
                                    {{ $t('you dont have any orders yet') }}
                                </h1>
                                <nuxt-link
                                    :to="{ name: 'home' }"
                                    class="btn btn-primary btn-lg"
                                >
                                    {{ $t('Browse Books') }}
                                </nuxt-link>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!loading && orders && orders[0]"
                    class="card orders-wrapper rounded-more border-0"
                >
                    <div class="card-header bg-transparent text-center p-0">
                        <ul id="myTab" class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a
                                    id="current-tab"
                                    class="nav-link active"
                                    data-toggle="tab"
                                    href="#current"
                                    role="tab"
                                    aria-controls="Current Orders"
                                    aria-selected="false"
                                >
                                    <h4 class="font-weight-light">
                                        {{ $t('Current Orders') }}
                                    </h4>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    id="part-tab"
                                    class="nav-link"
                                    data-toggle="tab"
                                    href="#completed"
                                    role="tab"
                                    aria-controls="Past Orders"
                                    aria-selected="true"
                                >
                                    <h4 class="font-weight-light">
                                        {{ $t('Completed Orders') }}
                                    </h4>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div id="myTabContent" class="card-body tab-content">
                        <div
                            id="current"
                            class="tab-pane fade show active"
                            role="tabpanel"
                            aria-labelledby="current-tab"
                        >
                            <single-order
                                v-for="(order, index) in orders[0]"
                                v-if="orders[0].length > 0"
                                :key="index"
                                :order="order"
                            />
                            <div v-else>
                                No Orders
                            </div>
                        </div>
                        <div
                            id="completed"
                            class="tab-pane fade"
                            role="tabpanel"
                            aria-labelledby="completed-tab"
                        >
                            <single-order
                                v-for="(order, index) in orders[1]"
                                v-if="orders[0].length > 0"
                                :key="index"
                                :order="order"
                            />
                            <div v-else>
                                No Orders
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'Orders',
    middleware: 'auth',
    components: {
        'single-order': () => import('~/components/SingleOrder')
    },
    async fetch() {
        try {
            const { data } = await axios.get(`/orders`)
            if (data && data[0]) {
                this.orders = data
            }
            this.loading = false
        } catch (e) {
            this.$router.push({ name: 'pricing' })
        }
    },
    data() {
        return {
            orders: [],
            loading: true
        }
    },
    mounted() {
        this.$fetch()
    }
}
</script>

<style scoped lang="scss">
@import '~assets/sass/_variables';

.orders-wrapper {
    .card-header {
        .nav {
            justify-content: space-evenly;

            li {
                a.nav-link {
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    align-items: center;
                    margin: 0;
                    padding: 1.7rem;
                    color: $text-dark;
                    background: transparent;
                    border: none;
                    border-bottom: 3px solid transparent;

                    &.active {
                        color: $primary;
                        border-bottom: 3px solid $primary;
                    }

                    img {
                        max-height: 15px;
                    }
                }
            }
        }
    }
}
</style>
