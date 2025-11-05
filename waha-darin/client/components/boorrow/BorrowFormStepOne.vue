<template>
    <div class="card-form text-start">
        <div class="card-form__inner">
            <div class="bookSelect">
                <h5>{{ $t('Choose books from your cart') }}</h5>
                <p>
                    {{ $t('Allowed Number of books:') }}
                    <span>{{ maxBooks - formData.selectedBooks.length }}</span>
                </p>
                <p v-if="error" class="alert-danger rounded py-2 px-4">
                    {{ error }}
                </p>
                <div v-if="books.length > 0" class="form-group">
                    <div
                        v-for="book in books"
                        :key="book.book_id"
                        class="d-flex align-items-center book-item"
                        :class="{ selected: book.selected === true }"
                        @click="toggleBookSelection(book, $event)"
                    >
                        <div class="checkIconWrap mx-4">
                            <Icon
                                :name="
                                    book.selected
                                        ? 'selectChecked'
                                        : 'selectEmpty'
                                "
                            />
                        </div>
                        <div class="d-flex">
                            <img
                                :src="book['cover_photo']"
                                class="book-cover img-fluid"
                                alt=""
                            />
                        </div>
                        <div class="d-flex flex-column book-desc p-4">
                            <div class="book-title">
                                {{ book.title }}
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
                        <Icon name="back" size="small" />
                    </span>
                    {{ $t('Back to cart') }}</a
                >
                <button
                    class="card-form__button flex-grow-1 btn-primary m-0"
                    @click.prevent="goForwardStep"
                >
                    {{ $t('Continue to dates') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BorrowFormStepOne',
    props: {
        maxBooks: {
            required: true,
            type: Number
        },
        books: {
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
                    selectedBooks: []
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
            if (this.formData.selectedBooks.length <= 0) {
                this.error = this.$t('You must select books')
            } else {
                this.error = null
                this.$emit('step-forward')
            }
        },
        goBackwardStep() {
            this.$emit('step-backward')
        },
        toggleBookSelection(book, e) {
            if (!book.selected) {
                if (this.formData.selectedBooks.length + 1 <= this.maxBooks) {
                    book.selected = true
                    e.target.classList.add('selected')
                    this.formData.selectedBooks.push(book)
                    this.error = null
                }
            } else if (book.selected === true) {
                book.selected = false
                e.target.classList.remove('selected')
                for (const [
                    index,
                    bookItem
                ] of this.formData.selectedBooks.entries()) {
                    if (bookItem.id === book.id) {
                        this.formData.selectedBooks.splice(index, 1)
                    }
                }
            }
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
    .book-item {
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

        img.book-cover {
            width: 65px;
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
    }
}
</style>
