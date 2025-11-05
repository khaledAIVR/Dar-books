<template>
    <div class="container">
        <div class="mt-5 card border-0 rounded settings-card">
            <h4 class="card-header py-4 bg-white text-start">
                {{ $t('Change Password') }}
            </h4>
            <div class="card-body py-4 container">
                <form
                    class="auth-form row"
                    @submit.prevent="update"
                    @keydown="form.onKeydown($event)"
                >
                    <input type="hidden" name="email" :value="user.email" />
                    <!-- New password -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label class="text-start w-100">{{
                                $t('New password')
                            }}</label>
                            <input
                                v-model="form.password"
                                :class="{
                                    'is-invalid': form.errors.has('password')
                                }"
                                type="password"
                                name="password"
                                class="form-control"
                                autocomplete="new-password"
                                :placeholder="$t('Full name')"
                            />
                            <has-error :form="form" field="password" />
                        </div>
                    </div>

                    <!-- New password confirmation -->
                    <div class="form-group col-6">
                        <div class="col-md-12">
                            <label class="text-start w-100">{{
                                $t('New password confirmation')
                            }}</label>
                            <input
                                v-model="form.password_confirmation"
                                :class="{
                                    'is-invalid': form.errors.has(
                                        'password_confirmation'
                                    )
                                }"
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                                autocomplete="new-password"
                                :placeholder="$t('New password confirmation')"
                            />
                            <has-error
                                :form="form"
                                field="password_confirmation"
                            />
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
                                    <span class="sr-only">Loading...</span>
                                </span>
                                {{ $t('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import Form from 'vform'
import { mapGetters } from 'vuex'

export default {
    scrollToTop: false,
    middleware: 'auth',

    data: () => ({
        form: new Form({
            password: '',
            password_confirmation: ''
        })
    }),
    computed: mapGetters({
        user: 'auth/user'
    }),

    methods: {
        async update() {
            const { data } = await this.form.patch('/settings/password')
            if (data.status === 200) {
                this.$toast.success(this.$t('Profile Updated successfully'))
                this.$store.dispatch('auth/updateUser', { user: data.user })
            } else {
                this.$toast.error(this.$t('Error Updating profile'))
            }
            this.form.reset()
        }
    },

    head() {
        return { title: this.$t('Change Password') }
    }
}
</script>
