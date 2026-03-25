<template>
    <subscribe-form
        v-if="plan"
        :form-data="formData"
        :plan="plan"
        :bank-details="bankDetails"
        :loading="loading"
        :done="done"
        :response="serverResponse"
        @submit-form="submitForm"
    />
</template>

<script>
import axios from 'axios'

export default {
    name: 'Checkout',
    layout: 'edit',
    middleware: 'auth',
    components: {
        'subscribe-form': () =>
            import('~/components/PlanCheckout/SubscribeForm')
    },
    async asyncData({ params }) {
        try {
            window.$nuxt.$loading.start()
        } catch (e) {}
        const [plansRes, bankRes] = await Promise.all([
            axios.get(`/plans/${params.planId}`),
            axios.get('/bank-details').catch(() => ({ data: null }))
        ])
        return {
            plan: plansRes.data.plan,
            bankDetails: bankRes.data && typeof bankRes.data === 'object' ? bankRes.data : null
        }
    },
    data: () => ({
        formData: {
            date: '',
            amount: ''
        },
        serverResponse: {
            code: null,
            status: null,
            icon: null,
            message: null,
            subMessage: null
        },
        done: false,
        loading: false
    }),
    methods: {
        async submitForm(data) {}
    }
}
</script>

<style lang="scss">
@import '~assets/sass/variables';
</style>
