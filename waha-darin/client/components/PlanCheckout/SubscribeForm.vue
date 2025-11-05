<template>
    <div class="col-md-12 col-lg-8 mx-auto mb-5" :dir="direction">
        <h1 class="mb-5 text-start">
            {{ $t('Subscribe to plan') }} "{{ plan.name }}" ({{ toBePaid }} EUR)
        </h1>

        <div class="p-5 mb-5 bg-white rounded">
            <h5>{{ $t('Subscription steps') }}</h5>
            <ul>
                <li>
                    1.
                    {{ $t('Make a bank transfer to the following account:') }}
                    <ul>
                        <li>{{ $t('Account Number:') }} 33333</li>
                        <li>{{ $t('IBAN:') }} 33333</li>
                        <li>{{ $t('Swift Code:') }} 33333</li>
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

            <div>
                <label for="amount">{{
                    $t('Confirm Transaction amount')
                }}</label>
                <BFormInput
                    id="amount"
                    v-model="formData.amount"
                    name="amount"
                    type="number"
                    :placeholder="$t('Transaction amount')"
                />
            </div>
        </div>

        <button
            type="submit"
            :disabled="done || loading"
            class="btn btn-primary d-flex w-100 justify-content-center align-items-center btn-50"
            @click="submit"
        >
            <div
                v-if="loading"
                class="spinner-border text-light mx-2"
                role="status"
            >
                <span class="sr-only">Loading...</span>
            </div>
            {{ $t('Confirm') }}
        </button>
    </div>
</template>

<script>
import { ValidationObserver, ValidationProvider } from 'vee-validate'
import { BFormDatepicker, BFormInput } from 'bootstrap-vue'
import axios from 'axios'

export default {
    name: 'SubscribeForm',
    components: {
        ValidationProvider,
        ValidationObserver,
        BFormDatepicker,
        BFormInput
    },
    props: {
        plan: {
            required: true,
            type: Object
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
            response: null
        }
    },
    computed: {
        direction() {
            return this.lang === 'ar' ? 'rtl' : 'auto'
        },
        lang() {
            return this.$store.getters['lang/locale']
        },
        toBePaid() {
            return this.plan.price
        }
    },
    methods: {
        async submit() {
            const data = {
                plan_id: this.plan.id,
                transaction_amount: this.formData.amount,
                transaction_date: this.formData.date + ' 00:00:00'
            }
            if (Object.values(data).some((val) => !val)) {
                alert(
                    this.$t('Please Complete the transaction date and amount')
                )
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
                console.error(e)
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
</style>
