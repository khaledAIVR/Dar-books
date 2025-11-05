<template>
    <div class="row">
        <div class="col-lg-6 m-auto">
            <div class="card">
                <h1 class="card-header bg-white text-center pt-3 pb-4">
                    {{ $t('Reset Password') }}
                </h1>

                <div class="card-body">
                    <alert-success :form="form" :message="status" />

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
                                                title="Menu"
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
                            class="btn btn-primary d-flex w-100 justify-content-center align-items-center btn-50"
                            :disabled="form.busy"
                            :class="{ disabled: form.busy }"
                        >
                            <div
                                v-if="form.busy"
                                class="spinner-border text-light mx-2"
                                role="status"
                            >
                                <span class="sr-only">Loading...</span>
                            </div>
                            {{ $t('Reset Password') }}
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

    data: () => ({
        status: '',
        form: new Form({
            email: ''
        })
    }),

    methods: {
        async send() {
            const { data } = await this.form.post('/password/email')

            this.status = data.status

            this.form.reset()
        }
    },
    head() {
        return { title: this.$t('Reset Password') }
    }
}
</script>
