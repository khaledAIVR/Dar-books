<template functional>
    <svg
        viewBox="0 0 24 24"
        class="icon"
        :class="$options.methods.getStyleClasses(props)"
        v-bind="data.attrs"
        role="img"
        v-on="listeners"
    >
        <title>{{ props.title || props.name }}</title>
        <desc v-if="props.desc">{{ props.desc }}</desc>
        <path :d="$options.methods.getPath(props)" />
    </svg>
</template>

<script>
import icons from './Icons/icons.json'

export default {
    name: 'Icon',
    props: {
        name: {
            type: String,
            required: true
        },
        size: {
            type: String,
            default: 'normal'
        },
        color: {
            type: String,
            default: '#C2C2C2'
        },
        title: {
            type: String,
            default: 'Icon'
        },
        desc: {
            type: String,
            default: 'Icon'
        }
    },
    methods: {
        getPath(props) {
            return icons[props.name]
        },
        getStyleClasses(props) {
            return [props.size, props.color].map((val) => val && `is-${val}`)
        }
    }
}
</script>

<style>
/* default */
.icon {
    width: 1.2em;
    height: 1.2em;
    fill: #c2c2c2;
}

/* sizes */
.icon.is-full {
    width: 100%;
    height: 100%;
}
.icon.is-vlarge {
    width: 4rem;
    height: 4rem;
}
.icon.is-large {
    width: 2em;
    height: 2em;
}

.icon.is-medium {
    width: 1.6em;
    height: 1.6em;
}

.icon.is-normal {
    width: 1.2em;
    height: 1.2em;
}

.icon.is-small {
    width: 1em;
    height: 1em;
}

.icon.is-tiny {
    width: 0.8em;
    height: 0.8em;
}

/* colors */
.icon.is-primary,
.icon.is-blue {
    fill: #f99d0f;
}

.icon.is-dark {
    fill: #43425d;
}
.icon.is-dark-light {
    fill: #7e8082;
}

.icon.is-success,
.icon.is-green {
    fill: #4caf50;
}

.icon.is-danger,
.icon.is-red {
    fill: #f44336;
}

.icon.is-warning,
.icon.is-orange {
    fill: #ff9800;
}
</style>
