<template>
    <b-dropdown id="dropdown-locale" class="m-md-2" variant="outline">
        <template v-slot:button-content>
            <img
                :alt="locales[locale].name"
                :src="locales[locale].flag"
                class="rounded-circle profile-photo"
                width="24px"
            />
            <p class="mx-2 my-0">
                {{ locales[locale].name }}
            </p>
        </template>
        <b-dropdown-item
            v-for="(value, key) in locales"
            :key="key"
            @click.prevent="setLocale(key)"
        >
            <img
                :src="value.flag"
                alt=""
                class="ml-1 mr-1"
                title="English language"
                width="24px"
            />
            {{ value.name }}
        </b-dropdown-item>
    </b-dropdown>
</template>

<script>
import { mapGetters } from 'vuex'
import { BDropdown, BDropdownItem } from 'bootstrap-vue'
import { loadMessages } from '~/plugins/i18n'

export default {
    components: {
        BDropdown,
        BDropdownItem
    },
    computed: mapGetters({
        locale: 'lang/locale',
        locales: 'lang/locales'
    }),

    methods: {
        setLocale(locale) {
            if (this.$i18n.locale !== locale) {
                loadMessages(locale)

                this.$store.dispatch('lang/setLocale', { locale })
            }
        }
    }
}
</script>
<style lang="scss">
@import '../../assets/sass/variables';

.dropdown-toggle a {
    color: $text-dark;
}

.dropdown-toggle::after {
    color: $text-light;
}

.dropdown-menu {
    border-radius: $border-radius;

    a {
        color: $body-color;
        font-weight: 200;
    }

    .dropdown-item {
        margin: 10px 0;
    }
}
</style>
