<template>
    <div class="faq-page">
        <div class="faq-header">
            <h1 class="faq-title">{{ $t('FAQ') }}</h1>
            <p class="faq-subtitle">{{ $t('faq_subtitle') }}</p>
        </div>

        <div class="faq-list">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="faq-item"
                :class="{ 'faq-item--open': openIndex === index }"
                @click="toggle(index)"
            >
                <button
                    type="button"
                    class="faq-item__trigger"
                    :aria-expanded="openIndex === index"
                    :aria-controls="'faq-answer-' + index"
                    :id="'faq-question-' + index"
                >
                    <span class="faq-item__number">{{ String(index + 1).padStart(2, '0') }}</span>
                    <span class="faq-item__question">{{ item.question }}</span>
                    <span class="faq-item__icon" aria-hidden="true">
                        <span class="faq-chevron" />
                    </span>
                </button>
                <div
                    :id="'faq-answer-' + index"
                    class="faq-item__answer-wrap"
                    role="region"
                    :aria-labelledby="'faq-question-' + index"
                >
                    <div class="faq-item__answer">
                        <p>{{ item.answer }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FaqPage',
    head() {
        return {
            title: this.$t('FAQ')
        }
    },
    data() {
        return {
            openIndex: null
        }
    },
    computed: {
        items() {
            return [
                { question: this.$t('faq_q1'), answer: this.$t('faq_a1') },
                { question: this.$t('faq_q2'), answer: this.$t('faq_a2') },
                { question: this.$t('faq_q3'), answer: this.$t('faq_a3') },
                { question: this.$t('faq_q4'), answer: this.$t('faq_a4') },
                { question: this.$t('faq_q5'), answer: this.$t('faq_a5') },
                { question: this.$t('faq_q6'), answer: this.$t('faq_a6') },
                { question: this.$t('faq_q7'), answer: this.$t('faq_a7') },
                { question: this.$t('faq_q8'), answer: this.$t('faq_a8') },
                { question: this.$t('faq_q9'), answer: this.$t('faq_a9') },
                { question: this.$t('faq_q10'), answer: this.$t('faq_a10') },
                { question: this.$t('faq_q11'), answer: this.$t('faq_a11') },
                { question: this.$t('faq_q12'), answer: this.$t('faq_a12') },
                { question: this.$t('faq_q13'), answer: this.$t('faq_a13') },
                { question: this.$t('faq_q14'), answer: this.$t('faq_a14') },
                { question: this.$t('faq_q15'), answer: this.$t('faq_a15') },
                { question: this.$t('faq_q16'), answer: this.$t('faq_a16') }
            ]
        }
    },
    methods: {
        toggle(index) {
            this.openIndex = this.openIndex === index ? null : index
        }
    }
}
</script>

<style lang="scss" scoped>
@import '~assets/sass/_variables';

.faq-page {
    max-width: 720px;
    margin: 0 auto 4rem;
    padding: 0 0.5rem;
}

.faq-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.faq-title {
    font-size: 2rem;
    font-weight: 300;
    color: $text-dark;
    margin-bottom: 0.5rem;
}

.faq-subtitle {
    font-size: 1rem;
    font-weight: 300;
    color: $text-light;
    opacity: 0.85;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.faq-item {
    background: #fff;
    border-radius: $border-radius-more;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    transition: box-shadow 0.25s ease-out;
    contain: layout style;

    &:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    &--open {
        box-shadow: 0 8px 28px rgba(249, 157, 15, 0.15);

        .faq-item__icon {
            transform: rotate(180deg);
        }

        .faq-item__answer-wrap {
            grid-template-rows: 1fr;
        }

        .faq-item__trigger {
            color: $primary;
            border-bottom-color: rgba(249, 157, 15, 0.2);
        }
    }
}

.faq-item__trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    text-align: left;
    border: none;
    background: transparent;
    cursor: pointer;
    font-family: inherit;
    font-size: 1rem;
    font-weight: 500;
    color: $text-dark;
    border-bottom: 1px solid transparent;
    transition: color 0.25s ease, border-color 0.25s ease, background 0.2s ease;

    &:hover {
        background: rgba(249, 157, 15, 0.06);
    }

    &:focus {
        outline: none;
        background: rgba(249, 157, 15, 0.08);
    }
}

.faq-item__number {
    flex-shrink: 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: $primary;
    letter-spacing: 0.02em;
}

.faq-item__question {
    flex: 1;
    line-height: 1.4;
}

.faq-item__icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(249, 157, 15, 0.12);
    color: $primary;
    transition: transform 0.3s cubic-bezier(0.34, 1.2, 0.64, 1), background 0.2s ease;
    will-change: transform;
}

.faq-chevron {
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 6px solid currentColor;
    margin-top: 2px;
}

.faq-item__answer-wrap {
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows 0.35s cubic-bezier(0.34, 1.2, 0.64, 1);
    contain: layout style;
}

.faq-item__answer {
    min-height: 0;
    overflow: hidden;
    backface-visibility: hidden;
}

.faq-item__answer p {
    margin: 0;
    padding: 0 1.25rem 1.25rem 3.5rem;
    font-size: 0.95rem;
    font-weight: 300;
    line-height: 1.7;
    color: $text-light;
    white-space: pre-line;
}

/* RTL: align answer padding */
html[dir='rtl'] .faq-item__answer p {
    padding: 0 3.5rem 1.25rem 1.25rem;
}

@media (max-width: 576px) {
    .faq-item__answer p {
        padding-left: 1.25rem;
    }

    html[dir='rtl'] .faq-item__answer p {
        padding-right: 1.25rem;
    }
}
</style>
