<template>
    <b-list-group-item
        tag="div"
        class="d-flex align-items-center border-0 item-head rounded rounded-more"
    >
        <nuxt-link :to="{ name: item.routeName }" class="d-flex menu-a">
            <b-avatar
                v-if="item.icon"
                tag="div"
                size="6rem"
                class="mr-3 bg-transparent"
            >
                <icon
                    :name="item.icon"
                    :title="item.title"
                    size="large"
                    color="dark"
                />
            </b-avatar>
            <div :class="{ 'mr-3': !item.icon }">
                <h4
                    class="mb-0 text-start"
                    :class="{ 'py-4': !item.description }"
                >
                    {{ item.title }}
                </h4>
                <p
                    v-if="item.description"
                    class="light-text mt-2 text-start text-secondary"
                >
                    {{ item.description }}
                </p>
            </div>
        </nuxt-link>
    </b-list-group-item>
</template>

<script>
import { BAvatar, BListGroupItem } from 'bootstrap-vue'

export default {
    name: 'NavMenuItem',
    components: {
        BListGroupItem,
        BAvatar
    },
    props: {
        item: {
            type: Object,
            required: true
        }
    }
}
</script>

<style lang="scss">
.item-head {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
    &:last-child {
        border-bottom: none !important;
    }
}
.menu-a {
    display: flex;
    align-items: center;
    padding: 0 !important;
}
.light-text {
    font-weight: 200 !important;
}
</style>
