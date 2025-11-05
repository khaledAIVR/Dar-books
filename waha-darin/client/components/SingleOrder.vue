<template>
    <div class="order border d-flex justify-content-between rounded-more mb-5">
        <div class="d-flex align-items-center">
            <div class="books-covers">
                <img
                    v-for="book in order.books"
                    :key="book['cover_photo']"
                    :src="book['cover_photo']"
                    alt=""
                />
            </div>
            <div class="info text-start p-5">
                <h3 class="order-number mb-0">
                    {{ $t('Order') + ' #' + order.id }}
                </h3>
                <p>{{ order.books.length + ' ' + $t('books') }}</p>
                <div class="order-start">
                    <div class="d-flex flex-column pb-3">
                        <p class="font-weight-light m-0">
                            {{ $t('Starting form') }}
                        </p>
                        <div class="d-flex align-items-center">
                            <h3 class="pe-2 m-0">
                                {{ dates.start.dayNumber }}
                            </h3>
                            <h4 class="font-weight-lighter  m-0">
                                {{
                                    dates.start.month +
                                        ', ' +
                                        dates.start.dayName +
                                        ', ' +
                                        dates.start.year
                                }}
                            </h4>
                        </div>
                    </div>
                </div>
                <div class="order-end">
                    <div class="d-flex flex-column">
                        <p class="font-weight-light m-0">
                            {{ $t('Until') }}
                        </p>
                        <!--                        <p class="font-weight-light m-0">{{translation.endFrom}}</p>-->
                        <div class="d-flex align-items-center">
                            <h3 class="pe-2 m-0">
                                {{ dates.end.dayNumber }}
                            </h3>
                            <h4 class="font-weight-lighter  m-0">
                                {{
                                    dates.end.month +
                                        ', ' +
                                        dates.end.dayName +
                                        ', ' +
                                        dates.end.year
                                }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="d-flex align-items-center flex-column justify-content-center order-status"
        >
            <Timeline :status="order.status" />
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    name: 'SingleOrder',
    components: {
        Timeline: () => import('./Timeline')
    },
    props: {
        order: {
            required: true,
            type: Object
        }
    },
    data() {
        return {
            dates: {}
        }
    },
    computed: mapGetters({
        lang: 'lang/locale'
    }),

    created() {
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }

        const start = new Date(Date.parse(this.order.start_date))
        const end = new Date(Date.parse(this.order.end_date))
        const startDate = start
            .toLocaleDateString(this.lang, options)
            .replace(/,/g, '')
            .replace(/،/g, '')
            .split(' ')
        const endDate = end
            .toLocaleDateString(this.lang, options)
            .replace(/,/g, '')
            .replace(/،/g, '')
            .split(' ')
        this.dates.start = {
            dayName: startDate[0],
            month: this.lang === 'en' ? startDate[1] : startDate[2],
            dayNumber: this.lang === 'en' ? startDate[2] : startDate[1],
            year: startDate[3]
        }
        this.dates.end = {
            dayName: endDate[0],
            month: this.lang === 'en' ? endDate[1] : endDate[2],
            dayNumber: this.lang === 'en' ? endDate[2] : endDate[1],
            year: endDate[3]
        }
    }
}
</script>

<style lang="scss" scoped>
.order {
    padding: 0.5rem 2rem;

    > * {
        flex: 1;
    }

    .books-covers {
        display: flex;
        flex-wrap: wrap;
        max-width: 165px;
        background: rgb(255, 239, 209);
        padding: 10px;
        border-radius: 5px;

        img {
            max-width: 50%;
            padding: 3px;
            object-fit: cover;
            border-radius: 10px;
            flex: 0 50%;
        }

        /* one item */
        img:first-child:nth-last-child(1) {
            width: 100%;
            max-width: 100%;
        }

        /* two items */
        img:first-child:nth-last-child(2),
        img:first-child:nth-last-child(2) ~ img {
            width: 50%;
            height: 50%;
        }
    }
}
</style>
