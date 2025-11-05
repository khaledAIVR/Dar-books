import Vue from 'vue'
import FastAverageColor from 'fast-average-color'

Vue.mixin({
    methods: {
        ImageLoad(e) {
            const fac = new FastAverageColor()
            const image = e.target
            const color = fac.getColor(image, { silent: true })
            image.style.boxShadow = `0px 10px 14px rgba(${color.value[0]}, ${color.value[1]}, ${color.value[2]}, 0.4)`
        }
    }
})
