<template>
    <div class="col-md-12 col-lg-8 mx-auto mb-5" :dir="direction">
        <h1 class="mb-5 text-start">
            {{ $t('Subscribe to plan') }} "{{ planName(plan) }}" ({{ toBePaid }}
            EUR)
        </h1>

        <div class="p-5 mb-5 bg-white rounded">
            <h5>{{ $t('Subscription steps') }}</h5>
            <ul>
                <li>
                    1.
                    {{ $t('Make a bank transfer to the following account:') }}
                    <ul>
                        <li v-if="bankDetails && bankDetails.name">
                            {{ $t('Account name:') }} {{ bankDetails.name }}
                        </li>
                        <li v-if="bankDetails && bankDetails.account_number">
                            {{ $t('Account Number:') }}
                            {{ bankDetails.account_number }}
                        </li>
                        <li v-if="bankDetails && bankDetails.iban">
                            {{ $t('IBAN:') }} {{ bankDetails.iban }}
                        </li>
                        <li v-if="bankDetails && bankDetails.swift_code">
                            {{ $t('Swift Code:') }} {{ bankDetails.swift_code }}
                        </li>
                        <template
                            v-if="
                                !bankDetails ||
                                    (!bankDetails.name &&
                                        !bankDetails.account_number &&
                                        !bankDetails.iban &&
                                        !bankDetails.swift_code)
                            "
                        >
                            <li class="text-muted">
                                {{ $t('Bank details will be displayed here.') }}
                            </li>
                        </template>
                    </ul>
                </li>
                <li>
                    2.
                    {{
                        $t(
                            'Fill the below form after making the payment to confirm your subscription'
                        )
                    }}
                </li>
                <li>
                    3.
                    {{
                        $t(
                            'Your subscription will be confirmed after we review and confirm the transfer'
                        )
                    }}
                </li>
            </ul>
        </div>

        <div class="p-5 mb-5 bg-white rounded">
            <div class="w-100">
                <label for="date">{{ $t('Transaction Date') }}</label>
                <BFormDatepicker
                    id="date"
                    v-model="formData.date"
                    name="date"
                    calendar-width="100%"
                    class="mb-2"
                    :locale="lang"
                    :placeholder="$t('Transaction Date')"
                />
            </div>

            <div class="payment-summary border rounded p-3 my-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ planName(plan) }}</span>
                    <strong>{{ basePrice }} €</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ $t('Donation') }}</span>
                    <strong>{{ donation }} €</strong>
                </div>
                <div class="d-flex flex-wrap donation-options mb-3">
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="
                            donation === 0
                                ? 'btn-primary'
                                : 'btn-outline-secondary'
                        "
                        @click.prevent="setDonation(0)"
                    >
                        {{ $t('No donation') }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="
                            donation === 5
                                ? 'btn-primary'
                                : 'btn-outline-secondary'
                        "
                        @click.prevent="setDonation(5)"
                    >
                        +5 €
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="
                            donation === 10
                                ? 'btn-primary'
                                : 'btn-outline-secondary'
                        "
                        @click.prevent="setDonation(10)"
                    >
                        +10 €
                    </button>
                </div>
                <div
                    class="d-flex justify-content-between font-weight-bold border-top pt-2"
                >
                    <span>{{ $t('Total') }}</span>
                    <span>{{ toBePaid }} €</span>
                </div>
            </div>

            <div>
                <label for="amount">{{
                    $t('Confirm Transaction amount')
                }}</label>
                <BFormInput
                    id="amount"
                    v-model="formData.amount"
                    name="amount"
                    type="number"
                    readonly
                    :placeholder="$t('Transaction amount')"
                />
            </div>
        </div>

        <label class="terms-line d-flex align-items-center mb-3">
            <input
                v-model="termsAgreed"
                type="checkbox"
                class="terms-checkbox"
            />
            <span>{{ $t('I agree to the terms and conditions') }}</span>
            <button
                type="button"
                class="btn btn-link p-0 align-baseline"
                @click.prevent="openTermsModal"
            >
                {{ $t('Terms and conditions') }}
            </button>
        </label>

        <button
            type="submit"
            :disabled="done || loading || !termsAgreed"
            class="btn btn-primary d-flex w-100 justify-content-center align-items-center btn-50"
            @click="submit"
        >
            <div
                v-if="loading"
                class="spinner-border text-light mx-2"
                role="status"
            >
                <span class="sr-only">{{ $t('Loading') }}</span>
            </div>
            {{ $t('Confirm') }}
        </button>

        <b-modal
            v-model="showTermsModal"
            size="lg"
            body-class="p-0"
            hide-header-close
            scrollable
        >
            <template #modal-header>
                <div
                    class="d-flex align-items-center w-100 modal-header-custom"
                    :class="{ 'flex-row-reverse': lang === 'ar' }"
                >
                    <span class="modal-header-spacer" aria-hidden="true"
                        >&times;</span
                    >
                    <h5 class="modal-title mb-0 flex-grow-1 text-center">
                        {{ $t('Terms and conditions') }}
                    </h5>
                    <b-button-close
                        :aria-label="$t('Close')"
                        @click="showTermsModal = false"
                    />
                </div>
            </template>
            <div class="terms-modal-body">
                <div class="terms-content p-4" v-html="termsContentFormatted" />
            </div>
        </b-modal>
    </div>
</template>

<script>
import {
    BFormDatepicker,
    BFormInput,
    BModal,
    BButtonClose
} from 'bootstrap-vue'
import axios from 'axios'
import { getTermsForLocale } from '../../data/terms'

export default {
    name: 'SubscribeForm',
    components: {
        BFormDatepicker,
        BFormInput,
        BModal,
        BButtonClose
    },
    props: {
        plan: {
            required: true,
            type: Object
        },
        bankDetails: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            loading: false,
            done: false,
            formData: {
                date: '',
                amount: ''
            },
            donation: 0,
            response: null,
            showTermsModal: false,
            termsAgreed: false
        }
    },
    computed: {
        direction() {
            return this.lang === 'ar' ? 'rtl' : 'auto'
        },
        lang() {
            return this.$store.getters['lang/locale']
        },
        basePrice() {
            return Number(this.plan.price) || 0
        },
        toBePaid() {
            return this.basePrice + this.donation
        },
        termsContentFormatted() {
            const text = getTermsForLocale(this.lang)
            if (!text) return ''
            return text.replace(/\n/g, '<br>').replace(/ {2}/g, ' &nbsp;')
        }
    },
    mounted() {
        this.syncAmount()
    },
    methods: {
        planName(plan) {
            const key = `plan_name_${plan.id}`
            const t = this.$t(key)
            return t !== key ? t : plan.name
        },
        setDonation(amount) {
            this.donation = amount
            this.syncAmount()
        },
        syncAmount() {
            this.formData.amount = String(this.toBePaid)
        },
        openTermsModal() {
            this.showTermsModal = true
        },
        async submit() {
            if (!this.termsAgreed) {
                this.$toast.error(
                    this.$t('I agree to the terms and conditions')
                )
                return
            }
            const data = {
                plan_id: this.plan.id,
                transaction_amount: this.toBePaid,
                transaction_date: this.formData.date + ' 00:00:00'
            }
            if (Object.values(data).some((val) => !val)) {
                alert(
                    this.$t('Please Complete the transaction date and amount')
                )
                return
            }
            try {
                this.loading = true
                const response = await axios.post(`/subscriptions`, data)
                if (response.data.start && response.data.end) {
                    this.$toast.success(this.$t('Subscribed successfully'))
                    this.done = true
                    setTimeout(() => {
                        this.$router.push({ name: 'home' })
                    }, 1000)
                }
            } catch (e) {
                const res = e.response
                const msg =
                    res &&
                    res.data &&
                    typeof res.data.message === 'string' &&
                    res.data.message
                this.$toast.error(
                    msg || this.$t('Please reload the page and try again.')
                )
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
[dir='rtl'] {
    text-align: right;
}
.terms-line {
    font-size: 0.95rem;
    gap: 0.5rem;
}
.terms-checkbox {
    width: 18px;
    height: 18px;
    flex: 0 0 auto;
}
.terms-line .btn-link {
    font-weight: 600;
}
.donation-options {
    gap: 0.5rem;
}
</style>

<style>
.terms-modal-body {
    max-height: 55vh;
    overflow-y: auto;
}
.terms-content {
    white-space: pre-line;
    line-height: 1.6;
}
[dir='rtl'] .terms-content {
    text-align: right;
}
/* RTL: move modal close (x) button to the left for Arabic */
.modal-header-rtl {
    flex-direction: row-reverse;
}
.modal-header-custom .modal-header-spacer {
    width: 2rem;
    visibility: hidden;
    font-size: 1.5rem;
    line-height: 1;
}
</style>
