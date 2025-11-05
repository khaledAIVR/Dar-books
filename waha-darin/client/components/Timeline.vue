<template>
    <div class="timeline">
        <ul>
            <li
                v-for="(isDone, index) in states"
                :key="index"
                :class="{ 'bg-dim': !isDone }"
            >
                <div class="circ">
                    <Icon
                        v-if="isDone"
                        name="check"
                        size="tiny"
                        color="white"
                    />
                </div>
                <p class="ttl">
                    {{ $t(index) }}
                </p>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'Timeline',
    props: {
        status: {
            required: true,
            type: String
        }
    },
    data: () => {
        return {
            states: {
                Received: true,
                Processing: false,
                Shipped: false,
                Delivered: false,
                Completed: false
            }
        }
    },
    created() {
        switch (this.status) {
            case 'Processing':
                this.states.Received = true
                this.states.Processing = true
                break
            case 'Shipped':
                this.states.Received = true
                this.states.Processing = true
                this.states.Shipped = true
                break
            case 'Delivered':
                this.states.Received = true
                this.states.Processing = true
                this.states.Shipped = true
                this.states.Delivered = true
                break
            case 'Completed':
                this.states.Received = true
                this.states.Processing = true
                this.states.Shipped = true
                this.states.Delivered = true
                this.states.Completed = true
                break
            default:
                this.states.Received = true
                break
        }
    }
}
</script>

<style lang="scss" scoped>
.timeline {
    padding: 5px 45px;

    ul {
        position: relative;
    }

    li {
        position: relative;
        width: 100%;
        list-style: none;
        line-height: 25px;
        display: flex;
        align-items: center;
        margin-bottom: 10px;

        .circ {
            width: 22px;
            height: 22px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        &:not(:last-child)::after {
            width: 4px;
            top: 17px;
            left: 9px;
            content: '';
            height: 79%;
            bottom: 0;
            position: absolute;
            background: #28a745;
        }

        &.bg-dim {
            .circ {
                background: transparent;
                border: 5px solid #c2c2c2;
            }

            &:not(:last-child)::after {
                background: #c2c2c2;
            }
        }

        p {
            margin: 0;
            font-size: 18px;
            font-weight: 200;
        }
    }
}

html[dir='rtl'] {
    .timeline {
        li {
            .circ {
                margin-right: 0;
                margin-left: 15px;
            }

            &:not(:last-child)::after {
                right: 9px;
                left: unset;
            }
        }
    }
}
</style>
