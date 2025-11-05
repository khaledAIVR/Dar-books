<template>
    <div class="col-md-12 col-lg-8 mx-auto mb-5">
        <div class="row pricing justify-content-center">
            <div v-for="plan in plans" class="col-4">
                <div
                    class="package d-flex flex-column justify-content-between rounded-more bg-white border-primary"
                >
                    <h1 class="font-weight-light package-title">
                        {{ plan.name }}
                    </h1>

                    <h1 class="font-weight-light package-note">
                        {{ plan.note }}
                    </h1>
                    <div class="price">
                        <h1>{{ plan.price }} {{ $t('EUR') }}</h1>
                        <h6 class="font-weight-lighter">
                            {{ $t('Per year') }}
                        </h6>
                    </div>
                    <ul class="features list-unstyled">
                        <li>
                            <div class="bg-success m-2 rounded-circle">
                                <Icon name="check" size="small" color="white" />
                            </div>
                            <span v-if="plan.price < 25">
                                {{ plan.books_quota }}
                                {{ $t('Book per month') }}
                            </span>
                            <span v-else>
                                {{ $t('6 orders each year') }}
                            </span>
                        </li>
                        <!--                        <li>
                            <div class="bg-success m-2 rounded-circle">
                                <Icon name="check" size="small" color="white" />
                            </div>
                            <span>{{ $t('Free shipping') }}</span>
                        </li>-->
                        <li>
                            <div class="bg-success m-2 rounded-circle">
                                <Icon name="check" size="small" color="white" />
                            </div>
                            <span v-if="plan.price < 25">{{
                                $t('Order once per month')
                            }}</span>
                            <span v-else>{{
                                $t('Order once each 2 months')
                            }}</span>
                        </li>
                    </ul>
                    <nuxt-link
                        class="btn btn-primary btn-lg m-auto"
                        :to="{ name: 'checkout', params: { planId: plan.id } }"
                    >
                        {{ $t('Subscribe Now') }}
                    </nuxt-link>
                </div>
            </div>
        </div>
        <p class="foot-note">{{ $t('Get more plans') }}</p>
    </div>
</template>
<script>
import axios from 'axios'

export default {
    name: 'Pricing',
    async asyncData() {
        try {
            window.$nuxt.$loading.start()
        } catch (e) {}
        const { data } = await axios.get(`/plans`)
        return { plans: data.plans }
    }
}
</script>

<style lang="scss" scoped>
@import '~assets/sass/_variables';

.pricing {
    .package {
        padding: 3rem 0;
        .package-title {
            flex: 1;
            font-size: 1.75rem;
        }
        .package-note {
            flex: 1;
            font-size: 1.2rem;
            color: #6d6d6d;
        }

        text-align: center;
        min-height: 60vh;

        .price {
            flex: 2;

            h1 {
                font-size: 3rem;
                color: $primary;
                line-height: 1;
                margin-bottom: 0;
            }
        }

        .features {
            flex: 3;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            padding: 0;
            margin: 1rem auto;

            li {
                margin-bottom: 8px;

                .bg-success {
                    width: 18px;
                    height: 18px;
                    padding: 5px;
                    display: inline-flex;
                    justify-content: center;
                    align-items: center;
                    margin: 0.2rem !important;
                }

                span {
                    font-weight: 200;
                    font-size: 16px;
                    color: $text-dark;
                }
            }
        }
    }
}

.foot-note {
    margin: 4rem auto;
    text-align: center;
}
</style>
