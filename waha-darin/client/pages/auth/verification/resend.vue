<template>
    <div class="row">
        <div class="col-lg-12 m-auto">
            <alert-success :form="form" :message="statusMessage" />

            <div class="card rounded-more border-primary p-5">
                <h1 class="card-header bg-white text-center pt-3 border-0">
                    {{ $t('Verify Your Email Address') }}
                </h1>
                <div
                    class="card-body p-5 d-flex flex-column text-center justify-content-between align-items-center"
                >
                    <h4 class="font-weight-light mb-4">
                        {{ $t('Before check your email') }}
                    </h4>
                    <h4 class="font-weight-lighter text-dark-light mb-3">
                        {{ $t('If you did not receive the email') }}
                    </h4>

                    <form
                        class="d-inline"
                        @submit.prevent="send"
                        @keydown="form.onKeydown($event)"
                    >
                        <!-- Email -->
                        <div class="form-group row">
                            <label
                                for="email"
                                class="col-md-4 col-form-label text-md-right sr-only"
                                >{{ $t('E-mail address') }}</label
                            >

                            <div class="col-12">
                                <div class="input-group mb-3">
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': form.errors.has(
                                                'email'
                                            )
                                        }"
                                        name="email"
                                        :placeholder="$t('E-mail address')"
                                        autocomplete="email"
                                        autofocus
                                    />
                                    <div class="input-group-append">
                                        <span
                                            class="input-group-text b-start-none"
                                        >
                                            <Icon
                                                name="email"
                                                :title="$t('Menu')"
                                                size="normal"
                                        /></span>
                                    </div>
                                </div>

                                <has-error
                                    class="invalid-feedback"
                                    :form="form"
                                    field="email"
                                />
                            </div>
                        </div>
                        <button
                            type="submit"
                            class="btn btn-primary-light btn-lg p-0 m-0 align-baseline"
                            :disabled="form.busy"
                            :class="{ disabled: form.busy }"
                        >
                            <div
                                v-if="form.busy"
                                class="spinner-border text-primary mx-2"
                                role="status"
                            >
                                <span class="sr-only">{{ $t('Loading') }}</span>
                            </div>
                            {{ $t('click here to request another') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Form from 'vform'

export default {
    layout: 'auth',
    middleware: 'guest',

    metaInfo() {
        return { title: this.$t('Verify Your Email Address') }
    },

    data: () => ({
        statusKey: '',
        form: new Form({
            email: ''
        })
    }),

    computed: {
        statusMessage() {
            return this.statusKey
                ? this.$t('verification_link_sent')
                : ''
        }
    },

    created() {
        if (this.$route.query.email) {
            this.form.email = this.$route.query.email
        }
    },

    methods: {
        async send() {
            try {
                await this.form.post('/email/resend')

                this.statusKey = 'verification_link_sent'

                this.form.reset()
            } catch (e) {
                if (e?.response?.status === 503) {
                    const msg = e.response?.data?.message
                    if (msg) this.$toast.error(msg)
                    return
                }

                throw e
            }
        }
    }
}
</script>
<style>
.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
}
</style>
