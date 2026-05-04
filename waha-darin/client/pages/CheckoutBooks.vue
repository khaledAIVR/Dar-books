<template>
    <div class="container">
        <div class="row">
            <div class="col-8">
                <div v-if="!loading && books.length > 0">
                    <borrow-form-step-one
                        v-if="currentStep === 1"
                        :books="books"
                        :form-data="formData"
                        :lang="lang"
                        :max-books="booksMaxNumber"
                        @step-backward="goBackwardStep"
                        @step-forward="goForwardStep"
                    />
                    <borrow-form-step-two
                        v-if="currentStep === 2"
                        :dates="dates"
                        :form-data="formData"
                        :lang="lang"
                        @step-backward="goBackwardStep"
                        @step-forward="goForwardStep"
                    />
                    <borrow-form-step-three
                        v-if="currentStep === 3"
                        :done="done"
                        :form-data="formData"
                        :lang="lang"
                        :response="serverResponse"
                        @step-backward="goBackwardStep"
                        @step-forward="goForwardStep"
                    />
                </div>

                <div v-if="!loading && books.length <= 0">
                    <div class="row justify-content-center">
                        <div class="col-6">
                            <div
                                class="w-75 d-flex justify-content-center m-auto"
                            >
                                <img
                                    alt=""
                                    class="img-fluid "
                                    src="~static/empty-cart.svg"
                                />
                            </div>
                            <div
                                class="d-flex justify-content-center flex-column align-items-center"
                            >
                                <h1 class="my-5 text-center">
                                    {{ $t('Your cart is empty') }}
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

                <div v-if="loading">
                    <div class="card-form">
                        <div class="card-form__inner">
                            <div
                                class="d-flex justify-content-center align-items-center p-5"
                            >
                                <div
                                    class="spinner-border text-primary"
                                    role="status"
                                >
                                    <span class="sr-only">{{ $t('Loading') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <order-summery :form-data="formData" />
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'CheckoutBooks',
    middleware: 'auth',
    layout: 'edit',
    components: {
        'borrow-form-step-one': () =>
            import('~/components/boorrow/BorrowFormStepOne'),
        'borrow-form-step-two': () =>
            import('~/components/boorrow/BorrowFormStepTwo'),
        'borrow-form-step-three': () =>
            import('~/components/boorrow/BorrowFormStepThree'),
        'order-summery': () => import('~/components/boorrow/OrderSummery')
    },
    async asyncData() {
        try {
            window.$nuxt.$loading.start()
        } catch (e) {}
        try {
            const { data } = await axios.get(`/cart`)
            const booksMaxNumber = Math.max(
                0,
                parseInt(data.available, 10) || 0
            )
            return {
                books: data.books || [],
                booksMaxNumber
            }
        } catch (e) {
            return { books: [], booksMaxNumber: 0 }
        }
    },
    data: () => ({
        dates: [],
        formData: {
            // Order
            selectedBooks: [],
            // Dates
            selectedDateStart: {},
            selectedDateEnd: {},
            // Contact info
            fullName: '',
            email: '',
            phone: '',
            // Shipping info
            addressLineOne: '',
            addressLineTwo: '',

            country: '',
            region: '',
            zipCode: ''
        },
        serverResponse: {
            code: null,
            status: null,
            icon: null,
            message: null,
            subMessage: null
        },
        currentStep: 1,
        done: false,
        loading: false
    }),

    computed: {
        ...mapGetters({ lang: 'lang/locale', user: 'auth/user' })
    },
    watch: {
        lang() {
            this.refreshDatesForLocale()
        }
    },
    mounted() {
        this.initDates(3)
        this.initUserData()
    },
    methods: {
        refreshDatesForLocale() {
            const savedStart = this.formData.selectedDateStart?.dateString
            const savedEnd = this.formData.selectedDateEnd?.dateString
            this.dates = []
            this.initDates(3)
            if (savedStart && savedEnd) {
                const match = this.dates.find(
                    (d) =>
                        d.start.dateString === savedStart &&
                        d.end.dateString === savedEnd
                )
                if (match) {
                    match.selected = true
                    this.formData.selectedDateStart = match.start
                    this.formData.selectedDateEnd = match.end
                }
            }
        },
        goForwardStep() {
            if (this.currentStep !== 3) {
                this.currentStep++
            } else {
                this.submitBorrowForm()
            }
        },
        goBackwardStep() {
            if (this.currentStep !== 1) {
                this.currentStep--
            } else {
                this.$router.push({ name: 'cart' })
            }
        },
        initUserData() {
            this.formData.fullName = this.user.name
            this.formData.email = this.user.email
            this.formData.phone = this.user.phone

            this.formData.addressLineOne = this.user.address_line_one
            this.formData.addressLineTwo = this.user.address_line_two
            this.formData.zipCode = this.user.postal_code
        },
        initDates(deliveryDayIndex) {
            const today = new Date()
            const nextDeliveryDay = new Date()
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }

            // Get next deliveryDayIndex in this case Wednesday
            nextDeliveryDay.setDate(
                today.getDate() +
                    ((deliveryDayIndex - 1 - today.getDay() + 7) % 7) +
                    1
            )

            // Get 4 more delivery days
            for (let i = 0; i < 5; i++) {
                const start = new Date()
                const end = new Date()

                start.setDate(nextDeliveryDay.getDate() + 7 * i)
                end.setDate(nextDeliveryDay.getDate() + (7 * i + 30))

                const startDate = start
                    .toLocaleDateString(this.lang, options)
                    .replace(/,/g, '')
                    .replace(/،/g, '')
                    .split(' ')
                const endDate = end
                    .toLocaleDateString(this.lang, options)
                    .replace(/,/g, '')
                    .replace(/،/g, '')
                    .split(' ')
                this.dates.push({
                    start: {
                        dayName: startDate[0],
                        month: this.lang === 'en' ? startDate[1] : startDate[2],
                        dayNumber:
                            this.lang === 'en' ? startDate[2] : startDate[1],
                        dateString: start.toDateString()
                        // year: startDate[3],
                    },
                    end: {
                        dayName: endDate[0],
                        month: this.lang === 'en' ? endDate[1] : endDate[2],
                        dayNumber: this.lang === 'en' ? endDate[2] : endDate[1],
                        dateString: end.toDateString()
                        // year: endDate[3],
                    },
                    selected: false
                })
            }
        },
        async submitBorrowForm() {
            if (!this.formData.selectedDateStart || !this.formData.selectedDateStart.dateString) {
                return this.$toast.error(this.$t('Please select a delivery date'))
            }
            this.loading = true
            const books = this.formData.selectedBooks.map((b) => b.id)
            const submitFormData = {
                books,
                startDate: this.formData.selectedDateStart.dateString,
                name: this.formData.fullName,
                phone: this.formData.phone,
                addressLineOne: this.formData.addressLineOne,
                addressLineTwo: this.formData.addressLineTwo,
                country: this.formData.country,
                region: this.formData.region,
                zipCode: this.formData.zipCode
            }
            try {
                const res = await axios.post('orders', submitFormData)
                this.serverResponse = { ...this.serverResponse, ...res.data }
                this.done = true
            } catch (error) {
                const data = error?.response?.data
                // Subscription not active → redirect to pricing
                if (error?.response?.status === 403 && data?.subscription === false) {
                    this.$toast.error(this.$t('Your subscription is not active. Please subscribe first.'))
                    return this.$router.push({ name: 'pricing' })
                }
                this.serverResponse.message = data?.message || this.$t('An error occurred. Please try again.')
                this.done = true
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style coped lang="scss">
@import '~assets/sass/variables';
@import '~assets/sass/mixins/placeholder';
@import '~assets/sass/elements/CheckoutForm.scss';

.card-form__inner {
    padding-top: 35px;
    box-shadow: 0 5px 24px 0 rgba(76, 116, 148, 0.4);
}

html[dir='ltr'] {
    .pe-2 {
        padding-right: 0.5rem;
    }
}

html[dir='rtl'] {
    .backIconWrap svg {
        transform: rotate(180deg);
    }

    .checkIconWrap.pl-3 {
        padding-right: 1rem !important;
    }

    .pe-2 {
        padding-left: 0.5rem;
    }

    .card-input__input.-select {
        background-position: 5% center !important;
    }
}
</style>
