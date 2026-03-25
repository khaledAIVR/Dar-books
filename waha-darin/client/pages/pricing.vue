<template>
    <div class="pricing-page" :class="{ 'pricing-page--rtl': isRtl }">
        <div class="pricing-container">
            <div v-if="plansLoadError" class="pricing-error text-center py-5">
                <p class="text-muted mb-3">{{ $t('Too many requests, please try again in a moment') }}</p>
                <button type="button" class="btn btn-primary" @click="retryLoadPlans">
                    {{ $t('Try again') }}
                </button>
            </div>
            <template v-else>
            <p class="pricing-tagline" :key="locale">
                {{ $t('pricing_tagline') }}
            </p>
            <div class="row pricing-row justify-content-center" :key="locale">
                <div
                    v-for="plan in displayPlans"
                    :key="`${locale}-${plan.id}`"
                    class="col-12 col-sm-10 col-md-6 col-lg-4 pricing-col"
                >
                    <article
                        class="package card d-flex flex-column rounded-more bg-white border-0 shadow-sm"
                    >
                        <div class="package-header text-center pt-4 px-4">
                            <h2 class="package-title font-weight-light mb-2">
                                {{ planName(plan) }}
                            </h2>
                            <p class="package-note font-weight-lighter mb-0">
                                {{ planNote(plan) }}
                            </p>
                        </div>

                        <div class="package-price text-center py-3">
                            <template v-if="hasFixedPrice(plan)">
                                <span class="price-amount">{{ plan.price }}</span>
                                <span class="price-currency"> {{ $t('EUR') }}</span>
                                <p class="price-period font-weight-lighter mb-0">
                                    {{ $t('Per year') }}
                                </p>
                            </template>
                            <template v-else>
                                <span class="price-label">{{ priceLabel(plan) }}</span>
                            </template>
                        </div>

                        <div class="package-features flex-grow-1 px-4 py-2">
                            <ul class="features-list list-unstyled mb-0">
                                <li
                                    v-for="(feat, key) in planFeatures(plan)"
                                    :key="key"
                                    class="feature-item"
                                >
                                    <span class="feature-icon">
                                        <Icon
                                            name="check"
                                            size="small"
                                            color="white"
                                        />
                                    </span>
                                    <span class="feature-text">{{ feat }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="package-actions p-4 px-5 text-center mt-auto">
                            <template v-if="hasAnySubscription">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-lg w-100 rounded-more"
                                    disabled
                                >
                                    {{
                                        isSubscribedToPlan(plan.id)
                                            ? $t('Subscribed')
                                            : $t('Subscribed in another plan')
                                    }}
                                </button>
                            </template>
                            <template v-else-if="hasFixedPrice(plan)">
                                <nuxt-link
                                    class="btn btn-primary btn-lg w-100 rounded-more"
                                    :to="{
                                        name: 'checkout',
                                        params: { planId: plan.id }
                                    }"
                                >
                                    {{ $t('Subscribe Now') }}
                                </nuxt-link>
                            </template>
                            <template v-else>
                                <a
                                    :href="contactLibraryEmail"
                                    class="btn btn-outline-primary btn-lg w-100 rounded-more"
                                >
                                    {{ $t('Contact library') }}
                                </a>
                            </template>
                        </div>
                    </article>
                </div>
            </div>

            <section class="benefits-section mt-5">
                <h2 class="benefits-section-title">{{ $t('Membership benefits') }}</h2>
                <div class="benefits-expandables">
                    <div
                        v-for="(section, index) in benefitSections"
                        :key="index"
                        class="benefit-expandable"
                        :class="{ 'benefit-expandable--open': openBenefitIndex === index }"
                        @click="toggleBenefit(index)"
                    >
                        <button
                            type="button"
                            class="benefit-expandable__trigger"
                            :aria-expanded="openBenefitIndex === index"
                            :aria-controls="'benefit-content-' + index"
                            :id="'benefit-trigger-' + index"
                        >
                            <span class="benefit-expandable__label">{{ section.title }}</span>
                            <span class="benefit-expandable__icon" aria-hidden="true">
                                <span class="benefit-chevron" />
                            </span>
                        </button>
                        <div
                            :id="'benefit-content-' + index"
                            class="benefit-expandable__content-wrap"
                            role="region"
                            :aria-labelledby="'benefit-trigger-' + index"
                        >
                            <div class="benefit-expandable__content">
                                <ul class="benefit-list list-unstyled mb-0">
                                    <li
                                        v-for="key in section.keys"
                                        :key="key"
                                        class="benefit-list-item"
                                    >
                                        <span class="benefit-list-icon">
                                            <Icon name="check" size="small" color="white" />
                                        </span>
                                        <span class="benefit-list-text">{{ $t(key) }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <p class="foot-note text-center mt-5 mb-0">
                {{ $t('Get more plans') }}
            </p>
            <p class="internal-cta text-center mt-3 mb-0">
                {{ $t('internal_subscription_cta') }}
                <a
                    :href="internalFormUrl"
                    class="internal-cta__link"
                    download="Anmeldeformular.docx"
                >
                    {{ $t('internal_subscription_form_link') }}
                </a>{{ $t('internal_subscription_cta_after') }}
                <span class="internal-cta__send-hint"> {{ $t('internal_subscription_send_hint') }} <a :href="contactLibraryEmail" class="internal-cta__link">{{ contactLibraryEmailAddress }}</a></span>
            </p>
            </template>
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

// Plans are fetched from API (id, name, note, price, books_quota). Translation is done on the frontend:
// - Plan names and notes: lang files use plan_name_<id> and plan_note_<id> (ar/de). Add new keys when you add plans in admin.
// - Feature bullets: PLAN_FEATURE_KEYS below + translated strings in lang files (ar/de). No English on pricing; locale is ar or de only.
// Plan IDs per Article 2: 2=رفيق الكتاب, 3=صديق الكتاب, 4=عائلي, 5=داخلي.
const PLAN_FEATURE_KEYS = {
    1: ['6 orders each year', 'Order once each 2 months'],
    2: ['6 orders each year', '2 books every 2 months', '12 books per year'],
    3: ['2 books per month', '24 books per year'],
    4: ['By agreement with library'],
    5: ['Deposit 10 EUR per person, max 50 EUR per family']
}

const BENEFIT_KEYS_SECTION_1 = [
    'benefit_diverse_books',
    'benefit_wishlist',
    'benefit_flexible_programs',
    'benefit_onsite_or_delivery',
    'benefit_free_borrowing',
    'benefit_support_initiative',
    'benefit_personal_advice',
    'benefit_reading_culture',
    'benefit_shared_reading'
]
const BENEFIT_KEYS_SECTION_2 = [
    'benefit_thursday_meeting',
    'benefit_community',
    'benefit_cultural_identity',
    'benefit_children_languages',
    'benefit_healthy_alternative',
    'benefit_safe_space',
    'benefit_dialogue_skills',
    'benefit_cultural_content'
]

export default {
    name: 'Pricing',
    async asyncData({ store }) {
        try {
            if (typeof window !== 'undefined' && window.$nuxt && window.$nuxt.$loading) {
                window.$nuxt.$loading.start()
            }
        } catch (e) {}
        if (store.getters['auth/token']) {
            await store.dispatch('auth/fetchUser')
        }
        try {
            const { data } = await axios.get(`/plans`)
            return { plans: data.plans || [], plansLoadError: false }
        } catch (err) {
            return { plans: [], plansLoadError: true }
        }
    },
    data() {
        return {
            plansLoadError: false,
            openBenefitIndex: null
        }
    },
    computed: {
        ...mapGetters('auth', ['user']),
        ...mapGetters('lang', ['locale']),
        displayPlans() {
            return (this.plans || []).filter((p) => Number(p.id) !== 5)
        },
        benefitSections() {
            return [
                { title: this.$t('Membership benefits'), keys: BENEFIT_KEYS_SECTION_1 },
                { title: this.$t('benefit_section_community'), keys: BENEFIT_KEYS_SECTION_2 }
            ]
        },
        internalFormUrl() {
            return '/Anmeldeformular.docx'
        },
        contactLibraryEmail() {
            return 'mailto:info@darbooks.de'
        },
        contactLibraryEmailAddress() {
            return 'info@darbooks.de'
        },
        isRtl() {
            return this.locale === 'ar'
        },
        hasAnySubscription() {
            return !!(
                this.user &&
                this.user.subscription &&
                this.user.subscription.plan_id != null
            )
        }
    },
    async mounted() {
        if (
            this.$store.getters['auth/token'] &&
            (!this.user || !this.user.subscription)
        ) {
            await this.$store.dispatch('auth/fetchUser')
        }
    },
    methods: {
        planName(plan) {
            const key = `plan_name_${plan.id}`
            const t = this.$t(key)
            return t !== key ? t : plan.name
        },
        planNote(plan) {
            const key = `plan_note_${plan.id}`
            const t = this.$t(key)
            return t !== key ? t : (plan.note || '')
        },
        planFeatures(plan) {
            const keys = PLAN_FEATURE_KEYS[plan.id]
            if (keys) {
                return keys.map((k) => this.$t(k))
            }
            const quota = plan.books_quota != null ? Number(plan.books_quota) : 0
            if (quota > 0 && plan.price < 25) {
                return [
                    `${quota} ${this.$t('Book per month')}`,
                    this.$t('Order once per month')
                ]
            }
            return [
                this.$t('6 orders each year'),
                this.$t('Order once each 2 months')
            ]
        },
        isSubscribedToPlan(planId) {
            if (!this.user || !this.user.subscription) return false
            return (
                Number(this.user.subscription.plan_id) === Number(planId)
            )
        },
        hasFixedPrice(plan) {
            const p = plan.price
            return p != null && Number(p) > 0
        },
        priceLabel(plan) {
            const id = Number(plan.id)
            if (id === 5) return this.$t('Deposit only')
            return this.$t('On request')
        },
        toggleBenefit(index) {
            this.openBenefitIndex = this.openBenefitIndex === index ? null : index
        },
        async retryLoadPlans() {
            this.plansLoadError = false
            try {
                const { data } = await axios.get(`/plans`)
                this.plans = data.plans || []
            } catch (err) {
                this.plansLoadError = true
            }
        }
    }
}
</script>

<style lang="scss" scoped>
@import '~assets/sass/_variables';

.pricing-page {
    width: 100%;
    min-height: 60vh;
    padding: 2rem 0 4rem;

    &.pricing-page--rtl {
        direction: rtl;
        text-align: right;
    }
}

.pricing-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

@media (min-width: 768px) {
    .pricing-container {
        padding: 0 2rem;
    }
}

.pricing-tagline {
    font-size: clamp(1.1rem, 2.5vw, 1.4rem);
    color: $text-dark;
    font-weight: 500;
    line-height: 1.5;
    margin-bottom: 2rem;
    max-width: 40em;
    margin-left: auto;
    margin-right: auto;
    text-align: center;
}

.pricing-row {
    margin-left: -0.75rem;
    margin-right: -0.75rem;
}

.pricing-col {
    margin-bottom: 2rem;
}

.package {
    height: 100%;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-top: 3px solid $primary;
    transition: box-shadow 0.2s ease, transform 0.2s ease;

    &:hover {
        box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.08);
    }
}

.package-header {
    .package-title {
        font-size: clamp(1.25rem, 2.5vw, 1.75rem);
        color: $text-dark;
        line-height: 1.3;
    }
    .package-note {
        font-size: clamp(0.85rem, 1.5vw, 1rem);
        color: #6d6d6d;
        line-height: 1.4;
    }
}

.package-price {
    text-align: center;

    .price-amount {
        font-size: clamp(2rem, 4vw, 2.75rem);
        font-weight: 600;
        color: $primary;
        line-height: 1;
    }
    .price-currency {
        font-size: clamp(1.25rem, 2.5vw, 1.5rem);
        color: $primary;
    }
    .price-period {
        font-size: 0.9rem;
        color: $text-light;
        margin-top: 0.25rem;
        text-align: center;
    }
    .price-label {
        font-size: clamp(1.25rem, 2.5vw, 1.75rem);
        font-weight: 600;
        color: $primary;
        display: block;
    }
}

.package-features {
    min-height: 0;
}

.features-list {
    padding: 0;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    color: $text-dark;
    line-height: 1.4;

    .pricing-page--rtl & {
        flex-direction: row-reverse;
        text-align: right;
    }
}

.feature-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #28a745;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
}

.feature-text {
    flex: 1;
    font-weight: 300;
}

.package-actions {
    border-top: 1px solid rgba(0, 0, 0, 0.06);

    .btn {
        font-weight: 500;
    }
}

.foot-note {
    font-size: 0.9rem;
    color: $text-light;
}

.benefits-section {
    max-width: 720px;
    margin-left: auto;
    margin-right: auto;
}

.benefits-section-title {
    font-size: 1.5rem;
    font-weight: 500;
    color: $text-dark;
    margin-bottom: 1.25rem;
    text-align: center;
}

.benefits-expandables {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.benefit-expandable {
    background: #fff;
    border-radius: $border-radius-more;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    transition: box-shadow 0.25s ease-out;
    contain: layout style;

    &:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    &--open {
        box-shadow: 0 8px 28px rgba(249, 157, 15, 0.15);

        .benefit-expandable__icon {
            transform: rotate(180deg);
        }

        .benefit-expandable__content-wrap {
            grid-template-rows: 1fr;
        }

        .benefit-expandable__trigger {
            color: $primary;
            border-bottom-color: rgba(249, 157, 15, 0.2);
        }
    }
}

.benefit-expandable__trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    text-align: left;
    border: none;
    background: transparent;
    cursor: pointer;
    font-family: inherit;
    font-size: 1rem;
    font-weight: 500;
    color: $text-dark;
    border-bottom: 1px solid transparent;
    transition: color 0.25s ease, border-color 0.25s ease, background 0.2s ease;

    .pricing-page--rtl & {
        text-align: right;
    }

    &:hover {
        background: rgba(249, 157, 15, 0.06);
    }

    &:focus {
        outline: none;
        background: rgba(249, 157, 15, 0.08);
    }
}

.benefit-expandable__label {
    flex: 1;
    line-height: 1.4;
}

.benefit-expandable__icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(249, 157, 15, 0.12);
    color: $primary;
    transition: transform 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
    will-change: transform;
}

.benefit-chevron {
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 6px solid currentColor;
    margin-top: 2px;
}

.benefit-expandable__content-wrap {
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows 0.35s cubic-bezier(0.34, 1.2, 0.64, 1);
    contain: layout style;
}

.benefit-expandable__content {
    min-height: 0;
    overflow: hidden;
    backface-visibility: hidden;
}

.benefit-list {
    padding: 0 1.25rem 1.25rem 3.5rem;
    list-style: none;

    .pricing-page--rtl & {
        padding: 0 3.5rem 1.25rem 1.25rem;
    }
}

.benefit-list-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    color: $text-dark;
    line-height: 1.4;

    .pricing-page--rtl & {
        flex-direction: row-reverse;
        text-align: right;
    }
}

.benefit-list-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #28a745;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
}

.benefit-list-text {
    flex: 1;
    font-weight: 300;
}

.internal-cta {
    font-size: 0.95rem;
    color: $text-light;
}

.internal-cta__send-hint {
    display: block;
    margin-top: 0.5rem;
}

.internal-cta__link {
    color: $primary;
    font-weight: 500;
    text-decoration: underline;

    &:hover {
        color: darken($primary, 8%);
    }
}

@media (max-width: 576px) {
    .benefit-list {
        padding-left: 1.25rem;
    }

    .pricing-page--rtl .benefit-list {
        padding-right: 1.25rem;
    }
}

@media (min-width: 576px) {
    .pricing-col {
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
}

@media (min-width: 768px) {
    .pricing-row {
        align-items: stretch;
    }
    .pricing-col {
        max-width: none;
    }
}
</style>
