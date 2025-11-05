<template>
    <div class="bg-white rounded-more p-4 card-form__inner text-start">
        <h4>{{ $t('Selected Books') }}</h4>
        <p>{{ $t('This is the book that will be delivered to you') }}</p>
        <ul
            v-if="!loading && formData.selectedBooks.length > 0"
            class="list-group"
        >
            <li
                v-for="book in formData.selectedBooks"
                :key="book.book_id"
                class="d-flex align-items-start border-info"
            >
                <div class="d-flex">
                    <img
                        :src="book['cover_photo']"
                        class="book-cover img-fluid"
                        alt=""
                    />
                </div>
                <div class="d-flex flex-column book-desc px-3">
                    <div class="book-title">
                        {{ book.title }}
                    </div>
                </div>
            </li>
        </ul>

        <div
            v-if="formData.selectedDateStart.dayNumber"
            class="d-flex flex-column date-item selected mt-3"
        >
            <h4>{{ $t('Selected Dates') }}</h4>
            <p class="mb-1">
                {{ $t('This is the start and end date for your borrow') }}
            </p>
            <div
                class="d-flex flex-column book-desc px-3 py-2 w-100 justify-content-between"
            >
                <div class="d-flex flex-column pb-3">
                    <p class="font-weight-light m-0">
                        {{ $t('Starting form') }}
                    </p>
                    <div class="d-flex align-items-center">
                        <h3 class="pe-2 m-0">
                            {{ formData.selectedDateStart.dayNumber }}
                        </h3>
                        <h4 class="font-weight-lighter  m-0">
                            {{
                                formData.selectedDateStart.month +
                                    ', ' +
                                    formData.selectedDateStart.dayName
                            }}
                        </h4>
                    </div>
                </div>
                <div class="d-flex flex-column">
                    <p class="font-weight-light m-0">
                        {{ $t('Until') }}
                    </p>
                    <div class="d-flex align-items-center">
                        <h3 class="pe-2 m-0">
                            {{ formData.selectedDateEnd.dayNumber }}
                        </h3>
                        <h4 class="font-weight-lighter  m-0">
                            {{
                                formData.selectedDateEnd.month +
                                    ', ' +
                                    formData.selectedDateEnd.dayName
                            }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center p-5"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div
            v-if="!loading && formData.selectedBooks.length <= 0"
            class="no-books d-flex flex-column justify-content-center align-items-center p-4"
        >
            <img src="~static/order.svg" class="img-fluid mb-3" alt="" />
            <p class="text-dark-light">
                {{ $t('Select books to confirm your order') }}
            </p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'OrderSummery',
    props: {
        formData: {
            type: Object,
            default: () => {
                return {
                    dates: {
                        selectedDateStart: {},
                        selectedDateEnd: {}
                    },
                    selectedBooks: {
                        required: true,
                        type: Array
                    }
                }
            }
        }
    },
    data() {
        return {
            loading: true
        }
    },
    mounted() {
        this.loading = false
    }
}
</script>

<style scoped lang="scss">
li {
    padding: 0.5rem !important;
}

img.book-cover {
    width: 50px;
    height: auto;
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
</style>
