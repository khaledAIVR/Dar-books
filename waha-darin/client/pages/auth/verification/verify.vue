<template>
    <div class="row">
        <div class="col-lg-12 m-auto">
            <div class="card rounded-more border-primary p-5">
                <h1 class="card-header bg-white text-center pt-3 border-0">
                    {{ $t('Verification Error') }}
                </h1>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'

const qs = (params) =>
    Object.keys(params)
        .map((key) => `${key}=${params[key]}`)
        .join('&')

export default {
    layout: 'auth',
    middleware: 'guest',

    metaInfo() {
        return { title: this.$t('verify_email') }
    },

    async asyncData({ params, query }) {
        try {
            const { data } = await axios.post(
                `/email/verify/${params.id}/${params.hash}?${qs(query)}`
            )
            return { success: true, status: data.status }
        } catch (e) {
            return { success: false, status: e.response.data.status }
        }
    },
    mounted() {
        this.$router.push({ name: 'home' })
    }
}
</script>
