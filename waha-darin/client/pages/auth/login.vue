<template>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card rounded">
                <h1 class="card-header bg-white text-center pt-3 pb-4">
                    {{ $t('Login') }}
                </h1>
                <div class="card-body">
                    <form
                        class="auth-form"
                        @submit.prevent="login"
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
                                        autocomplete="password"
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
                                    {{ $t('Login') }}
                                </button>
                                <div
                                    class="pb-5 pt-2 d-flex justify-content-between align-items-center"
                                >
                                    <div class="form-check">
                                        <input
                                            id="remember"
                                            v-model="remember"
                                            name="remember"
                                            type="checkbox"
                                        />
                                        <label
                                            class="form-check-label"
                                            for="remember"
                                        >
                                            {{ $t('Remember Me') }}
                                        </label>
                                    </div>
                                    <router-link
                                        class="btn btn-link"
                                        :to="{ name: 'password.request' }"
                                    >
                                        {{ $t('Forgot Your Password?') }}
                                    </router-link>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!--                    <div class="form-group row mb-0 auth-form">
                        <div class="col-12">
                            <p class="text-center">
                                {{ $t('Or register using') }}
                            </p>
                        </div>
                    </div>-->
                    <!--                    <div class="form-group row mb-0 auth-form">
                        <div class="col-12">
                            <div class="d-flex w-100 social-login">
                                <button
                                    type="submit"
                                    class="btn btn-facebook m-1 flex-grow-1 h-50"
                                >
                                    <Icon
                                        name="facebook"
                                        :title="$t('Login with Facebook')"
                                        color="white"
                                        size="small"
                                    />
                                    {{ $t('Facebook') }}
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-twitter m-1 flex-grow-1 h-50"
                                >
                                    <Icon
                                        name="twitter"
                                        :title="$t('Login with Twitter')"
                                        color="white"
                                        size="small"
                                    />
                                    {{ $t('Twitter') }}
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-google m-1 flex-grow-1 h-50"
                                >
                                    <img
                                        src="google.png"
                                        class="img-fluid"
                                        :alt="$t('Login with Google')"
                                        :title="$t('Login with Google')"
                                    />
                                    {{ $t('Google') }}
                                </button>
                            </div>
                        </div>
                    </div>-->
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

    data: () => ({
        form: new Form({
            email: '',
            password: ''
        }),
        remember: false
    }),

    methods: {
        async login() {
            let data

            // Submit the form.
            try {
                const response = await this.form.post('/login')
                data = response.data
            } catch (e) {
                return
            }

            // Save the token.
            this.$store.dispatch('auth/saveToken', {
                token: data.token,
                remember: this.remember
            })

            // Fetch the user.
            await this.$store.dispatch('auth/fetchUser')

            // Redirect home.
            this.$router.push({ name: 'home' })
        }
    },
    head() {
        return { title: this.$t('login') }
    }
}
</script>
<style lang="scss">
.btn {
    .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
    }
}
.invalid-feedback {
    font-size: 14px;

    a {
        margin: 0 10px;
    }
}
</style>
