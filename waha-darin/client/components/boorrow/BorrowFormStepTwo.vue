<template>
    <div class="card-form text-start">
        <div class="card-form__inner">
            <div class="bookSelect">
                <h5>{{ $t('Choose your borrow dates') }}</h5>
                <p>
                    {{
                        $t(
                            'We deliver books on wednesday, so the start date must be a wednesday'
                        )
                    }}
                </p>
                <p v-if="error" class="alert-danger rounded py-2 px-4">
                    {{ error }}
                </p>

                <div v-if="dates.length > 0" class="form-group">
                    <div
                        v-for="date in dates"
                        :key="
                            date.start.dayNumber +
                                date.start.dayName +
                                date.start.month
                        "
                        class="d-flex align-items-center date-item"
                        :class="{ selected: date.selected === true }"
                        @click="toggleDateSelection(date, $event)"
                    >
                        <div class="checkIconWrap pl-3">
                            <icon
                                :name="
                                    date.selected
                                        ? 'radioChecked'
                                        : 'radioEmpty'
                                "
                            />
                        </div>
                        <div
                            class="d-flex book-desc px-3 py-2 w-100 justify-content-between"
                        >
                            <div class="d-flex flex-column">
                                <p class="font-weight-lighter m-0">
                                    {{ $t('Starting form') }}
                                </p>
                                <div class="d-flex align-items-center">
                                    <h3 class="pe-2 m-0">
                                        {{ date.start.dayNumber }}
                                    </h3>
                                    <h4 class="font-weight-lighter  m-0">
                                        {{
                                            date.start.month +
                                                ', ' +
                                                date.start.dayName
                                        }}
                                    </h4>
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <p class="font-weight-lighter m-0">
                                    {{ $t('Until') }}
                                </p>
                                <div class="d-flex align-items-center">
                                    <h3 class="pe-2 m-0">
                                        {{ date.end.dayNumber }}
                                    </h3>
                                    <h4 class="font-weight-lighter  m-0">
                                        {{
                                            date.end.month +
                                                ', ' +
                                                date.end.dayName
                                        }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="btns-area d-flex justify-content-between align-items-center"
            >
                <a href="#" class="flex-grow-1" @click.prevent="goBackwardStep">
                    <span class="backIconWrap">
                        <icon name="back" size="small" />
                    </span>
                    {{ $t('Back to books') }}
                </a>
                <button
                    class="card-form__button flex-grow-1 btn-primary m-0"
                    @click.prevent="goForwardStep"
                >
                    {{ $t('continue to Shipping') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BorrowFormStepTwo',
    props: {
        dates: {
            required: true,
            type: Array
        },
        lang: {
            required: true,
            type: String
        },
        formData: {
            type: Object,
            default: () => {
                return {
                    dates: {
                        selectedDateStart: {},
                        selectedDateEnd: {},
                        selected: false
                    }
                }
            }
        }
    },
    data() {
        return {
            error: null
        }
    },
    methods: {
        goForwardStep() {
            if (
                this.formData.selectedDateStart.month &&
                this.formData.selectedDateEnd.month
            ) {
                this.error = null
                this.$emit('step-forward')
            } else {
                this.error = this.$t('You must select dates')
            }
        },
        goBackwardStep() {
            this.$emit('step-backward')
        },

        toggleDateSelection(date, e) {
            // Disable any selected ones
            e.target.classList.remove('selected')
            this.dates.forEach(function(date) {
                date.selected = false
            })

            // Empty selected
            this.formData.selectedDateStart = ''
            this.formData.selectedDateEnd = ''

            // Select styling
            e.target.classList.add('selected')
            date.selected = true

            // fill the formData
            this.formData.selectedDateStart = date.start
            this.formData.selectedDateEnd = date.end
            this.error = null
        }
    }
}
</script>
<style lang="scss">
.btns-area {
    display: flex;
    align-items: center;

    svg path {
        fill: currentColor;
    }
}

.card-form__button {
    box-shadow: none !important;
    width: unset !important;
}

.bookSelect {
    .date-item {
        border: 2px #eeeeee solid;
        border-radius: 15px;
        margin-bottom: 1.2rem;
        padding: 0.3rem !important;
        cursor: pointer;

        * {
            pointer-events: none;
        }

        &:hover {
            border: 2px #91939a solid !important;

            .checkIconWrap {
                svg path {
                    fill: #91939a;
                }
            }
        }

        &.selected {
            border: 2px #28a745 solid !important;

            .checkIconWrap {
                svg path {
                    fill: #28a745;
                }
            }
        }
    }
}
</style>
