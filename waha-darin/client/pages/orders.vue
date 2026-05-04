<template>
    <div class="col-md-12 col-lg-10 mx-auto mb-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div
                    v-if="loading"
                    class="d-flex justify-content-center align-items-center p-5"
                >
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">{{ $t('Loading') }}</span>
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
                    v-if="!loading && orders && orders.length"
                    class="card orders-wrapper rounded-more border-0"
                >
                    <div class="card-header bg-transparent text-center p-0">
                        <ul id="myTab" class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a
                                    id="current-tab"
                                    class="nav-link"
                                    :class="{ active: activeTab === 'current' }"
                                    href="#current"
                                    role="tab"
                                    aria-controls="current"
                                    :aria-selected="
                                        activeTab === 'current'
                                            ? 'true'
                                            : 'false'
                                    "
                                    @click.prevent="setActiveTab('current')"
                                >
                                    <h4 class="font-weight-light">
                                        {{ $t('Current Orders') }}
                                    </h4>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    id="completed-tab"
                                    class="nav-link"
                                    :class="{
                                        active: activeTab === 'completed'
                                    }"
                                    href="#completed"
                                    role="tab"
                                    aria-controls="completed"
                                    :aria-selected="
                                        activeTab === 'completed'
                                            ? 'true'
                                            : 'false'
                                    "
                                    @click.prevent="setActiveTab('completed')"
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
                            class="tab-pane fade"
                            :class="{
                                show: activeTab === 'current',
                                active: activeTab === 'current'
                            }"
                            role="tabpanel"
                            aria-labelledby="current-tab"
                        >
                            <div v-if="currentOrders.length">
                                <single-order
                                    v-for="order in currentOrders"
                                    :key="order.id"
                                    :order="order"
                                />
                            </div>
                            <div v-else class="text-center text-muted py-4">
                                {{ $t('No Orders') }}
                            </div>
                        </div>
                        <div
                            id="completed"
                            class="tab-pane fade"
                            :class="{
                                show: activeTab === 'completed',
                                active: activeTab === 'completed'
                            }"
                            role="tabpanel"
                            aria-labelledby="completed-tab"
                        >
                            <div v-if="completedOrders.length">
                                <single-order
                                    v-for="order in completedOrders"
                                    :key="order.id"
                                    :order="order"
                                />
                            </div>
                            <div v-else class="text-center text-muted py-4">
                                {{ $t('No Orders') }}
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

            // Normalize API response into [currentOrders, completedOrders].
            // Backend should return an array of two arrays, but we also tolerate legacy object shapes.
            let current = []
            let completed = []
            if (Array.isArray(data)) {
                current = data[0] || []
                completed = data[1] || []
            } else if (data && typeof data === 'object') {
                // Legacy groupBy('completed') JSON can come back as { "0": [...], "1": [...] }
                current = data[0] || data['0'] || data.false || []
                completed = data[1] || data['1'] || data.true || []
            }
            this.orders = [current, completed]

            this.loading = false
        } catch (e) {
            this.$router.push({ name: 'pricing' })
        }
    },
    data() {
        return {
            orders: [],
            loading: true,
            activeTab: 'current'
        }
    },
    computed: {
        currentOrders() {
            return this.orders && this.orders[0] ? this.orders[0] : []
        },
        completedOrders() {
            return this.orders && this.orders[1] ? this.orders[1] : []
        }
    },
    watch: {
        '$route.hash'() {
            this.activateTabFromHash()
        }
    },
    mounted() {
        this.$fetch().finally(() => {
            this.$nextTick(() => {
                this.activateTabFromHash()
                if (!this.$route.hash) {
                    this.$router.replace({ hash: '#current' }).catch(() => {})
                }
            })
        })
        document.addEventListener(
            'visibilitychange',
            this.handleVisibilityChange
        )
        this.startPolling()
    },
    beforeDestroy() {
        document.removeEventListener(
            'visibilitychange',
            this.handleVisibilityChange
        )
        this.stopPolling()
    },
    methods: {
        setActiveTab(tab) {
            this.activeTab = tab
            // keep URL in sync (so refresh preserves the selected tab)
            const hash = tab === 'completed' ? '#completed' : '#current'
            if (this.$route && this.$route.hash !== hash) {
                this.$router.replace({ hash }).catch(() => {})
            }
        },
        activateTabFromHash() {
            if (!process.client) return
            const hash = this.$route && this.$route.hash ? this.$route.hash : ''
            if (hash === '#completed') {
                this.activeTab = 'completed'
            } else if (hash === '#current') {
                this.activeTab = 'current'
            }
        },
        startPolling() {
            if (this._pollTimer || (process.client && document.hidden)) return
            this._pollTimer = setInterval(() => this.$fetch(), 30000)
        },
        stopPolling() {
            if (!this._pollTimer) return
            clearInterval(this._pollTimer)
            this._pollTimer = null
        },
        handleVisibilityChange() {
            if (document.hidden) {
                this.stopPolling()
            } else {
                this.$fetch()
                this.startPolling()
            }
        }
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
