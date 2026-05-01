<template>
    <div class="container">
        <div class="mt-5 card border-0 rounded settings-card">
            <h4 class="card-header py-4 bg-white text-start">
                {{ $t('Profile Info') }}
            </h4>
            <div class="card-body py-4 container">
                <form
                    class="auth-form row"
                    @submit.prevent="update"
                    @keydown="form.onKeydown($event)"
                >
                    <!-- Name -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label class="text-start w-100">{{
                                $t('Full name')
                            }}</label>
                            <input
                                v-model="form.name"
                                :class="{
                                    'is-invalid': form.errors.has('name')
                                }"
                                type="text"
                                name="name"
                                class="form-control"
                                :placeholder="$t('Full name')"
                            />
                            <has-error :form="form" field="name" />
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label for="email" class="text-start w-100">{{
                                $t('E-mail address')
                            }}</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                name="email"
                                disabled="disabled"
                                class="form-control"
                            />
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label for="phone" class="text-start w-100">{{
                                $t('Mobile Number')
                            }}</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                :class="{
                                    'is-invalid': form.errors.has('phone')
                                }"
                                type="text"
                                name="phone"
                                class="form-control"
                                :placeholder="$t('Mobile Number')"
                            />
                            <has-error :form="form" field="phone" />
                        </div>
                    </div>

                    <!-- Age -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label for="age" class="text-start w-100">{{
                                $t('Age')
                            }}</label>
                            <input
                                id="age"
                                v-model="form.age"
                                :class="{
                                    'is-invalid': form.errors.has('age')
                                }"
                                type="text"
                                name="phageone"
                                class="form-control"
                                :placeholder="$t('Age')"
                            />
                            <has-error :form="form" field="age" />
                        </div>
                    </div>

                    <!-- Address line one -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label
                                for="address_line_one"
                                class="text-start w-100"
                                >{{ $t('Address line one') }}</label
                            >
                            <input
                                id="address_line_one"
                                v-model="form.address_line_one"
                                :class="{
                                    'is-invalid': form.errors.has(
                                        'address_line_one'
                                    )
                                }"
                                type="text"
                                name="address_line_one"
                                class="form-control"
                                :placeholder="$t('Address line one')"
                            />
                            <has-error :form="form" field="address_line_one" />
                        </div>
                    </div>
                    <!-- Address line فصخ -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label
                                for="address_line_two"
                                class="text-start w-100"
                                >{{ $t('Address line two') }}</label
                            >
                            <input
                                id="address_line_two"
                                v-model="form.address_line_two"
                                :class="{
                                    'is-invalid': form.errors.has(
                                        'address_line_two'
                                    )
                                }"
                                type="text"
                                name="address_line_one"
                                class="form-control"
                                :placeholder="$t('Address line two')"
                            />
                            <has-error :form="form" field="address_line_two" />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group col-md-6">
                        <div class="col-md-12 ml-md-auto">
                            <button
                                type="submit"
                                class="btn btn-primary d-flex w-100 justify-content-center align-items-center btn-50"
                                :disabled="form.busy"
                                :class="{ disabled: form.busy }"
                            >
                                <span
                                    v-if="form.busy"
                                    class="spinner-border text-light mx-2"
                                    role="status"
                                >
                                    <span class="sr-only">{{ $t('Loading') }}</span>
                                </span>
                                {{ $t('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="mt-5 card border-0 rounded settings-card">
            <h4 class="card-header py-4 bg-white text-start">
                {{ $t('logout') }}
            </h4>
            <div class="card-body py-4 container">
                <div class="row">
                    <!-- Submit Button -->
                    <div class="form-group col-md-3">
                        <div class="col-md-12 ml-md-auto">
                            <button
                                class="btn btn-danger d-flex w-100 justify-content-center align-items-center btn-50"
                                @click.prevent="logout"
                            >
                                {{ $t('logout') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'

export default {
    middleware: 'auth',
    scrollToTop: false,

    data: () => ({
        prefetchedUser: {},
        form: new Form({
            name: '',
            email: '',
            phone: '',
            age: '',
            address_line_one: '',
            address_line_two: ''
        })
    }),
    computed: mapGetters({
        user: 'auth/user'
    }),

    async asyncData({ store }) {
        // Ensure user is in the store before the component renders
        if (!store.getters['auth/user']) {
            try {
                await store.dispatch('auth/fetchUser')
            } catch (e) {}
        }
        const user = store.getters['auth/user']
        return {
            prefetchedUser: user || {}
        }
    },

    created() {
        const source = this.prefetchedUser && this.prefetchedUser.email
            ? this.prefetchedUser
            : this.user
        if (source) {
            this.form.keys().forEach((key) => {
                this.form[key] = source[key] || ''
            })
        }
    },

    watch: {
        user(val) {
            if (val) this.fillForm()
        }
    },

    methods: {
        fillForm() {
            this.form.keys().forEach((key) => {
                this.form[key] = this.user[key]
            })
        },

        async update() {
            const { data } = await this.form.patch('/settings/profile')
            if (data.status === 200) {
                this.$toast.success(this.$t('Profile Updated successfully'))
                this.$store.dispatch('auth/updateUser', { user: data.user })
            } else {
                this.$toast.error(this.$t('Error Updating profile'))
            }
        },

        async logout() {
            // Log out the user.
            await this.$store.dispatch('auth/logout')

            // Redirect to login.
            this.$router.push({ name: 'login' })
        }
    },
    head() {
        return { title: this.$t('Profile Info') }
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

.form-group {
    margin-bottom: 30px;
}
</style>
