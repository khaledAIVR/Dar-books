<template>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card rounded">
                <h1 class="card-header bg-white text-center pt-3 pb-4">
                    {{ $t('Create New Account') }}
                </h1>
                <div class="card-body">
                    <form
                        class="auth-form"
                        @submit.prevent="register"
                        @keydown="form.onKeydown($event)"
                    >
                        <!-- Name -->
                        <div class="form-group row">
                            <label
                                for="name"
                                class="col-md-4 col-form-label text-md-right sr-only"
                                >{{ $t('Full name') }}</label
                            >
                            <div class="col-12">
                                <div class="input-group mb-3">
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': form.errors.has(
                                                'name'
                                            )
                                        }"
                                        name="name"
                                        :placeholder="$t('Full name')"
                                        autocomplete="name"
                                        autofocus
                                    />
                                    <div class="input-group-append">
                                        <span
                                            class="input-group-text b-start-none"
                                        >
                                            <Icon
                                                name="user"
                                                :title="$t('Menu')"
                                                size="normal"
                                            />
                                        </span>
                                    </div>
                                </div>

                                <has-error
                                    class="invalid-feedback"
                                    :form="form"
                                    field="name"
                                />
                            </div>
                        </div>

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

                        <div class="form-group row">
                            <div class="col-12 d-flex align-items-center">
                                <input
                                    id="terms"
                                    v-model="agreedToTerms"
                                    type="checkbox"
                                    class="ml-1 mr-2"
                                />
                                <label for="terms" class="mb-0 font-weight-light" style="cursor:pointer">
                                    {{ $t('I agree to the Terms and Conditions') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-12">
                                <button
                                    type="submit"
                                    class="btn btn-primary d-flex w-100 justify-content-center align-items-center btn-50"
                                    :disabled="form.busy || !agreedToTerms"
                                    :class="{ disabled: form.busy || !agreedToTerms }"
                                >
                                    <div
                                        v-if="form.busy"
                                        class="spinner-border text-light mx-2"
                                        role="status"
                                    >
                                        <span class="sr-only">{{
                                            $t('Loading')
                                        }}</span>
                                    </div>
                                    {{ $t('Create New Account') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <img class="register-img" src="~static/registration.png" alt="" />
        </div>
    </div>
</template>

<script>
import Form from 'vform'

export default {
    layout: 'auth',
    middleware: 'guest',

    data: () => ({
        agreedToTerms: false,
        form: new Form({
            name: '',
            email: '',
            password: '',
            password_confirmation: ''
        })
    }),

    methods: {
        async register() {
            try {
                // Register the user.
                const { data } = await this.form.post('/register')

                // Must verify email first.
                if (data.status) {
                    this.$router.push({
                        name: 'verification.resend',
                        query: { email: this.form.email }
                    })
                    return
                }

                await this.afterRegisterLogin(data)
            } catch (e) {
                if (e?.response?.status === 503) {
                    const msg = e.response?.data?.message
                    if (msg) this.$toast.error(msg)
                    this.$router.push({
                        name: 'verification.resend',
                        query: { email: this.form.email }
                    })
                    return
                }

                throw e
            }
        },

        async afterRegisterLogin(userPayload) {
            // Log in the user.
            const {
                data: { token }
            } = await this.form.post('/login')

            if (!token) {
                return
            }

            // Save the token.
            this.$store.dispatch('auth/saveToken', { token })

            // Update the user.
            await this.$store.dispatch('auth/updateUser', { user: userPayload })

            // Redirect home.
            this.$router.push({ name: 'home' })
        }
    },
    head() {
        return { title: this.$t('Create New Account') }
    }
}
</script>
<style>
.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
}
.register-img {
    width: 100%;
}
@media (max-width: 425px) {
    .register-img {
        margin-top: 1rem;
    }
}
</style>
