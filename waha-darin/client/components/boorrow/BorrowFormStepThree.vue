<template>
    <div class="card-form text-start">
        <ValidationObserver v-if="!done" v-slot="{ handleSubmit }">
            <form @submit.prevent="handleSubmit(onSubmit)">
                <div class="card-form__inner">
                    <h4 class="pb-3">
                        {{ $t('Contact Info') }}
                    </h4>
                    <ValidationProvider
                        v-slot="{ errors }"
                        :name="$t('Full name')"
                        rules="required|name"
                    >
                        <div class="card-input">
                            <label
                                for="name"
                                class="card-input__label sr-only"
                                >{{ $t('Full name') }}</label
                            >
                            <input
                                id="name"
                                v-model="formData.fullName"
                                v-letter-only
                                type="text"
                                autocomplete="name"
                                class="card-input__input"
                                :class="{ 'is-invalid': errors[0] }"
                                :placeholder="$t('Full name')"
                            />
                            <div class="invalid-feedback" role="alert">
                                <p>{{ errors[0] }}</p>
                            </div>
                        </div>
                    </ValidationProvider>

                    <ValidationProvider
                        v-slot="{ errors }"
                        :name="$t('E-mail address')"
                        rules="required|email"
                    >
                        <div class="card-input">
                            <label
                                for="email"
                                class="card-input__label sr-only"
                                >{{ $t('E-mail address') }}</label
                            >
                            <input
                                id="email"
                                v-model="formData.email"
                                type="text"
                                autocomplete="email"
                                disabled="disabled"
                                class="card-input__input"
                                :class="{ 'is-invalid': errors[0] }"
                                :placeholder="$t('E-mail address')"
                            />

                            <div class="invalid-feedback" role="alert">
                                <p>{{ errors[0] }}</p>
                            </div>
                        </div>
                    </ValidationProvider>

                    <ValidationProvider
                        v-slot="{ errors }"
                        :name="$t('Mobile Number')"
                        rules="required|min:11|max:14"
                    >
                        <div class="card-input">
                            <label
                                for="phone"
                                class="card-input__label sr-only"
                                >{{ $t('Mobile Number') }}</label
                            >
                            <input
                                id="phone"
                                v-model="formData.phone"
                                v-number-only
                                type="text"
                                autocomplete="tel"
                                class="card-input__input"
                                :class="{ 'is-invalid': errors[0] }"
                                :placeholder="$t('Mobile Number')"
                            />

                            <div class="invalid-feedback" role="alert">
                                <p>{{ errors[0] }}</p>
                            </div>
                        </div>
                    </ValidationProvider>

                    <h4 class="pb-3">
                        {{ $t('Shipping Info') }}
                    </h4>

                    <ValidationProvider
                        v-slot="{ errors }"
                        :name="$t('Address Line One')"
                        rules="required"
                    >
                        <div class="card-input">
                            <label
                                for="addressLineOne"
                                class="card-input__label sr-only"
                                >{{ $t('Address Line One') }}</label
                            >
                            <input
                                id="addressLineOne"
                                v-model="formData.addressLineOne"
                                type="text"
                                autocomplete="address-line1"
                                class="card-input__input"
                                :class="{ 'is-invalid': errors[0] }"
                                :placeholder="$t('Address Line One')"
                            />
                            <div class="invalid-feedback" role="alert">
                                <p>{{ errors[0] }}</p>
                            </div>
                        </div>
                    </ValidationProvider>

                    <div class="card-input">
                        <label
                            for="addressLineTwo"
                            class="card-input__label sr-only"
                            >{{ $t('Apartment, suite, etc (optional)') }}</label
                        >
                        <input
                            id="addressLineTwo"
                            v-model="formData.addressLineTwo"
                            type="text"
                            autocomplete="address-line2"
                            class="card-input__input"
                            :placeholder="
                                $t('Apartment, suite, etc (optional)')
                            "
                        />
                    </div>

                    <ValidationProvider
                        v-slot="{ errors }"
                        :name="$t('Country')"
                        rules="required"
                    >
                        <div class="card-input">
                            <label class="card-input__label sr-only">{{
                                $t('Country')
                            }}</label>

                            <country-select
                                v-model="formData.country"
                                :placeholder="$t('Country')"
                                class-name="card-input__input -select"
                                :country-name="true"
                                :country="formData.country"
                                top-country="DE"
                            />
                            <div class="invalid-feedback" role="alert">
                                <p>{{ errors[0] }}</p>
                            </div>
                        </div>
                    </ValidationProvider>

                    <div class="d-flex">
                        <ValidationProvider
                            v-slot="{ errors }"
                            :name="$t('Region')"
                            rules="required"
                            class="flex-grow-1"
                        >
                            <div class="card-input">
                                <label class="card-input__label sr-only">{{
                                    $t('Region')
                                }}</label>

                                <region-select
                                    v-model="formData.region"
                                    :country="formData.country"
                                    :region="formData.region"
                                    :country-name="true"
                                    :region-name="true"
                                    :placeholder="$t('Region')"
                                    class-name="card-input__input -select"
                                />

                                <div class="invalid-feedback" role="alert">
                                    <p>{{ errors[0] }}</p>
                                </div>
                            </div>
                        </ValidationProvider>

                        <ValidationProvider
                            v-slot="{ errors }"
                            :name="$t('Zip Code')"
                            class="flex-grow-1"
                            rules="required|numeric"
                        >
                            <div class="card-input m-start-10">
                                <label
                                    for="zipCode"
                                    class="card-input__label sr-only"
                                    >{{ $t('Zip Code') }}</label
                                >
                                <input
                                    id="zipCode"
                                    v-model="formData.zipCode"
                                    v-number-only
                                    type="text"
                                    autocomplete="postal-code"
                                    class="card-input__input"
                                    :class="{ 'is-invalid': errors[0] }"
                                    :placeholder="$t('Zip Code')"
                                />

                                <div class="invalid-feedback" role="alert">
                                    <p>{{ errors[0] }}</p>
                                </div>
                            </div>
                        </ValidationProvider>
                    </div>

                    <div
                        class="btns-area d-flex justify-content-between align-items-center"
                    >
                        <a
                            href="#"
                            class="flex-grow-1"
                            @click.prevent="goBackwardStep"
                        >
                            <span class="backIconWrap">
                                <Icon name="back" size="small" />
                            </span>
                            {{ $t('Back to dates') }}
                        </a>
                        <button
                            class="card-form__button flex-grow-1 btn-primary m-0"
                        >
                            {{ $t('Place Order') }}
                        </button>
                    </div>
                </div>
            </form>
        </ValidationObserver>

        <div v-if="done" class="card-form__inner response">
            <div
                class="d-flex justify-content-center flex-column align-items-center p-3"
            >
                <div class="icon-wrap">
                    <Icon
                        :name="response.icon"
                        size="full"
                        :color="response.status"
                    />
                </div>
                <h1 class="font-weight-light my-3 text-center">
                    {{ response.message }}
                </h1>
                <h4 class="font-weight-lighter mb-3 text-center">
                    {{ response.subMessage }}
                </h4>
                <nuxt-link
                    v-if="response.status === 'success'"
                    class="btn btn-primary btn-lg"
                    :to="{ name: 'orders' }"
                >
                    {{ $t('Track Orders') }}
                </nuxt-link>
            </div>
        </div>
    </div>
</template>

<script>
// Importing vee-validate with locales and required rules.
import {
    extend,
    localize,
    setInteractionMode,
    ValidationObserver,
    ValidationProvider
} from 'vee-validate'
// eslint-disable-next-line camelcase
import {
    alpha_spaces,
    email,
    max,
    min,
    required,
    numeric
} from 'vee-validate/dist/rules'

import Vue from 'vue'
import vueCountryRegionSelect from 'vue-country-region-select'
import en from '../../utils/validation/en.json'
import ar from '../../utils/validation/ar.json'
import de from '../../utils/validation/de.json'

Vue.use(vueCountryRegionSelect)

export default {
    name: 'BorrowFormStepThree',
    directives: {
        'number-only': {
            bind(el) {
                function checkValue(event) {
                    event.target.value = event.target.value.replace(
                        /[^0-9]/g,
                        ''
                    )
                    if (event.charCode >= 48 && event.charCode <= 57) {
                        return true
                    }
                    event.preventDefault()
                }

                el.addEventListener('keypress', checkValue)
            }
        },
        'letter-only': {
            bind(el) {
                function checkValue(event) {
                    if (event.charCode >= 48 && event.charCode <= 57) {
                        event.preventDefault()
                    }
                    return true
                }

                el.addEventListener('keypress', checkValue)
            }
        }
    },
    components: {
        ValidationProvider,
        ValidationObserver
    },
    props: {
        done: {
            required: true,
            type: Boolean
        },
        lang: {
            required: true,
            type: String
        },
        response: {
            required: true,
            type: Object
        },
        formData: {
            type: Object,
            default: () => {
                return {
                    fullName: '',
                    email: '',
                    phone: '',

                    addressLineOne: '',
                    addressLineTwo: '',

                    country: '',
                    region: ''
                }
            }
        }
    },
    created() {
        this.setLocale()
        this.setRules()
    },
    methods: {
        onSubmit() {
            this.$emit('step-forward')
        },
        goBackwardStep() {
            this.$emit('step-backward')
        },
        setLocale() {
            localize({ en, ar, de })
            localize(this.lang)
        },
        setRules() {
            extend('required', required)
            extend('name', alpha_spaces)
            extend('max', max)
            extend('min', min)
            extend('email', email)
            extend('numeric', numeric)
            setInteractionMode('eager')
        }
    }
}
</script>
<style lang="scss">
.bookSelect {
    li {
        padding: 1rem !important;

        img.book-cover {
            width: 65px;
            border-radius: 3px;
            box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.16);
        }

        .no-books img {
            opacity: 0.3;
        }

        .book-desc {
            a {
                margin: 0.4rem 1rem;
                padding: 0;

                &:hover {
                    color: #1b1e21;
                }
            }

            .book-title {
                font-weight: 300;
                font-size: 16px;
            }
        }
    }
}
html[dir='ltr'] {
    .m-start-10 {
        margin: 0 !important;
        margin-left: 10px !important;
    }
}
html[dir='rtl'] {
    .m-start-10 {
        margin: 0 !important;
        margin-right: 10px !important;
    }
}
</style>
