<template>
  <div class="order border d-flex justify-content-between rounded-more mb-5">
    <div class="d-flex align-items-center">
      <div class="books-covers">
        <img
          v-for="book in order.books"
          :key="book.cover_photo"
          :src="book.cover_photo"
          :alt="book.title || $t('Untitled')"
        />
      </div>

      <div class="info text-start p-5">
        <h3 class="order-number mb-0">
          {{ $t('Order') + ' #' + order.id }}
        </h3>
        <p>{{ order.books.length + ' ' + $t('books') }}</p>

        <div class="order-start">
          <div class="d-flex flex-column pb-3">
            <p class="font-weight-light m-0">{{ $t('Starting form') }}</p>
            <div class="d-flex align-items-center">
              <h3 class="pe-2 m-0">{{ dates.start.dayNumber }}</h3>
              <h4 class="font-weight-lighter m-0">
                {{ dates.start.month + ', ' + dates.start.dayName + ', ' + dates.start.year }}
              </h4>
            </div>
          </div>
        </div>

        <div class="order-end">
          <div class="d-flex flex-column">
            <p class="font-weight-light m-0">{{ $t('Until') }}</p>
            <div class="d-flex align-items-center">
              <h3 class="pe-2 m-0">{{ dates.end.dayNumber }}</h3>
              <h4 class="font-weight-lighter m-0">
                {{ dates.end.month + ', ' + dates.end.dayName + ', ' + dates.end.year }}
              </h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT SIDE: TIMELINE + TRACK BUTTON -->
    <div class="d-flex align-items-center flex-column justify-content-center order-status">
      <Timeline :status="timelineStatus" />

      <div class="mt-3 text-center w-100">
        <!-- Show tracking button when we have a usable link -->
        <template v-if="showTracking">
          <a
            :href="trackingLink"
            class="btn btn-outline-primary btn-sm w-100"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ $t('Track Shipment') }}
          </a>
          <div v-if="order.shipment_number" class="text-muted small mt-2">
            {{ $t('Shipment number') }}: <strong>{{ order.shipment_number }}</strong>
          </div>
        </template>

        <!-- Optional: show cancelled badge -->
        <div v-else-if="isCancelled" class="badge badge-danger py-2 px-3 d-inline-block">
          {{ $t('Order Cancelled') }}
        </div>
      </div>

      <!-- Delivered: return countdown + return shipment input -->
      <div v-if="isDelivered" class="mt-3 w-100 text-center">
        <div
          v-if="timeLeftToReturn && !returnShipmentSaved && !isReturnOverdue"
          class="text-muted small mb-2"
        >
          {{ $t('Time left to return') }}:
          <strong>{{ timeLeftToReturn.days }}</strong> {{ $t('Days') }}
          <strong>{{ timeLeftToReturn.hours }}</strong> {{ $t('Hours') }}
        </div>

        <div v-else-if="isReturnOverdue" class="text-muted small mb-2">
          {{ $t('WaitingReturnShipment') }}
        </div>

        <template v-if="returnShipmentSaved">
          <div class="text-muted small">
            {{ $t('Return shipment number') }}:
            <strong>{{ order.return_shipment_number || savedReturnShipmentNumber }}</strong>
          </div>
        </template>

        <form v-else @submit.prevent="saveReturnShipment" class="w-100">
          <input
            v-model="returnShipmentNumber"
            type="text"
            class="form-control form-control-sm mb-2"
            :placeholder="$t('Return shipment number')"
            :disabled="savingReturnShipment"
          />
          <button
            type="submit"
            class="btn btn-outline-primary btn-sm w-100"
            :disabled="savingReturnShipment"
          >
            {{ $t('Submit return shipment') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
  name: 'SingleOrder',
  components: {
    Timeline: () => import('./Timeline')
  },
  props: {
    order: { type: Object, required: true }
  },
  data() {
    return {
      // Input value (not saved until the user clicks submit).
      returnShipmentNumber: '',
      // Local echo of server-saved number for instant UI feedback.
      savedReturnShipmentNumber: null,
      savingReturnShipment: false
    }
  },
  computed: {
    ...mapGetters({ lang: 'lang/locale' }),

    // Formatted dates reactive to current locale (updates when language changes).
    dates() {
      const startStr = this.order.start_date
      const endStr = this.order.end_date
      const empty = { dayName: '', month: '', dayNumber: '', year: '' }
      if (!startStr || !endStr) return { start: { ...empty }, end: { ...empty } }
      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
      const start = new Date(Date.parse(startStr))
      const end = new Date(Date.parse(endStr))
      const startDate = start.toLocaleDateString(this.lang, options).replace(/[,،]/g, '').split(' ')
      const endDate = end.toLocaleDateString(this.lang, options).replace(/[,،]/g, '').split(' ')
      return {
        start: {
          dayName: startDate[0],
          month: this.lang === 'en' ? startDate[1] : startDate[2],
          dayNumber: this.lang === 'en' ? startDate[2] : startDate[1],
          year: startDate[3]
        },
        end: {
          dayName: endDate[0],
          month: this.lang === 'en' ? endDate[1] : endDate[2],
          dayNumber: this.lang === 'en' ? endDate[2] : endDate[1],
          year: endDate[3]
        }
      }
    },

    isCancelled() {
      return this.order.status === 'Cancelled' || this.order.shipment_status === 'cancelled'
    },

    // Build a usable DHL tracking URL:
    // 1) Use backend-provided tracking_url if it's a valid URL.
    // 2) Else build from shipment_number (Express vs Paket heuristic).
    trackingLink() {
      const raw = this.order.tracking_url
      if (raw) {
        const normalized = this.normalizeUrl(String(raw))
        if (normalized) return normalized
      }

      const num = (this.order.shipment_number || '').toString().trim()
      if (!num) return null

      // Heuristic: 10 digits → DHL Express (AWB), else → DHL Paket (Germany)
      const isTenDigit = /^[0-9]{10}$/.test(num)
      if (isTenDigit) {
        return `https://www.dhl.com/en/express/tracking.html?AWB=${encodeURIComponent(num)}&brand=DHL`
      }
      return `https://www.dhl.de/en/privatkunden/dhl-sendungsverfolgung.html?piececode=${encodeURIComponent(num)}`
    },

    hasTracking() {
      return !!this.trackingLink
    },

    showTracking() {
      // Tracking is irrelevant after delivery (per requirement).
      return this.hasTracking && !this.isDelivered && this.order.status !== 'Completed'
    },

    isDelivered() {
      // Delivered phase includes post-delivery return states.
      return ['Delivered', 'WaitingReturnShipment', 'ReturnedBack'].includes(
        this.order.status
      )
    },

    returnShipmentSaved() {
      return !!(this.order.return_shipment_number || this.savedReturnShipmentNumber)
    },

    isReturnOverdue() {
      if (!this.isDelivered || this.returnShipmentSaved || !this.order.end_date) return false
      const end = new Date(`${this.order.end_date}T23:59:59`)
      return new Date() > end
    },

    timelineStatus() {
      // We no longer show a separate "Completed" step in the timeline.
      // Map it to the closest delivered-phase status.
      if (this.order.status === 'Completed') {
        return this.returnShipmentSaved ? 'ReturnedBack' : 'Delivered'
      }
      if (this.isReturnOverdue) return 'WaitingReturnShipment'
      return this.order.status
    },

    timeLeftToReturn() {
      if (!this.order.end_date) return null
      const end = new Date(`${this.order.end_date}T23:59:59`)
      const now = new Date()
      const diffMs = end.getTime() - now.getTime()
      if (Number.isNaN(diffMs)) return null
      const clamped = Math.max(0, diffMs)
      const totalHours = Math.floor(clamped / (1000 * 60 * 60))
      const days = Math.floor(totalHours / 24)
      const hours = totalHours % 24
      return { days, hours }
    }
  },

  methods: {
    normalizeUrl(url) {
      try {
        new URL(url)       // absolute URL OK
        return url
      } catch {
        try {
          new URL(`https://${url}`) // add scheme if missing
          return `https://${url}`
        } catch {
          return null
        }
      }
    },

    async saveReturnShipment() {
      const value = (this.returnShipmentNumber || '').toString().trim()
      if (!value) {
        this.$toast.error(this.$t('Please enter return shipment number'))
        return
      }

      if (this.order.return_shipment_number || this.savedReturnShipmentNumber) {
        return
      }

      this.savingReturnShipment = true
      try {
        const { data } = await axios.post(
          `/orders/${this.order.id}/return-shipment`,
          { return_shipment_number: value }
        )
        const saved = data?.order?.return_shipment_number || value
        this.savedReturnShipmentNumber = saved
        this.returnShipmentNumber = ''
        this.$toast.success(this.$t('Return shipment saved'))
      } catch (e) {
        const status = e?.response?.status
        const message = e?.response?.data?.message
        if (status === 409) {
          // If it was already saved, show it as saved locally.
          this.savedReturnShipmentNumber = value
          this.returnShipmentNumber = ''
          this.$toast.success(this.$t('Return shipment saved'))
        } else if (status === 422 && message) {
          this.$toast.error(String(message))
        } else {
          this.$toast.error(this.$t('Something went wrong'))
        }
      } finally {
        this.savingReturnShipment = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.order {
  padding: 0.5rem 2rem;

  > * { flex: 1; }

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

  .order-status { min-width: 170px; }
}
</style>
