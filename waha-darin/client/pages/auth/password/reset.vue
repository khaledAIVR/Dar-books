<template>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <alert-success :form="form" :message="statusMessage" />

            <div class="card rounded">
                <h1 class="card-header bg-white text-center pt-3 pb-4">
                    {{ $t('Reset Password') }}
                </h1>
                <div class="card-body">
                    <form
                        class="auth-form"
                        @submit.prevent="reset"
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

                        <!-- Password -->
                        <div class="form-group row">
                            <label
                                for="password"
                                class="col-md-4 col-form-label text-md-right sr-only"
                            >
                                {{ $t('Password') }}</label
                            >
                            <div class="col-12">
                                <div class="input-group mb-3">
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': form.errors.has(
                                                'password'
                                            )
                                        }"
                                        :placeholder="$t('Password')"
                                        autocomplete="new-password"
                                    />
                                    <div class="input-group-append">
                                        <span
                                            class="input-group-text b-start-none"
                                        >
                                            <Icon
                                                name="password"
                                                :title="$t('Menu')"
                                                size="normal"
                                        /></span>
                                    </div>
                                </div>
                                <has-error
                                    class="invalid-feedback"
                                    :form="form"
                                    field="password"
                                />
                            </div>
                        </div>
                        <!-- Password -->
                        <div class="form-group row">
                            <label
                                for="password_confirmation"
                                class="col-md-4 col-form-label text-md-right sr-only"
                            >
                                {{ $t('Password') }}</label
                            >
                            <div class="col-12">
                                <div class="input-group mb-3">
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': form.errors.has(
                                                'password_confirmation'
                                            )
                                        }"
                                        :placeholder="$t('Password')"
                                        autocomplete="new-password"
                                    />
                                    <div class="input-group-append">
                                        <span
                                            class="input-group-text b-start-none"
                                        >
                                            <Icon
                                                name="password"
                                                :title="$t('Menu')"
                                                size="normal"
                                        /></span>
                                    </div>
                                </div>
                                <has-error
                                    class="invalid-feedback"
                                    :form="form"
                                    field="password_confirmation"
                                />
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-12">
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
                                        <span class="sr-only">{{ $t('Loading') }}</span>
                                    </div>
                                    {{ $t('Reset Password') }}
                                </button>
                            </div>
                        </div>
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
        statusKey: '',
        form: new Form({
            token: '',
            email: '',
            password: '',
            password_confirmation: ''
        })
    }),

    computed: {
        statusMessage() {
            return this.statusKey ? this.$t(this.statusKey) : ''
        }
    },

    created() {
        this.form.email = this.$route.query.email
        this.form.token = this.$route.params.token
    },

    methods: {
        async reset() {
            const { data } = await this.form.post('/password/reset')

            this.statusKey = 'reset'

            this.form.reset()
        }
    },
    head() {
        return { title: this.$t('Reset Password') }
    }
}
</script>
<style>
.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
}
</style>
