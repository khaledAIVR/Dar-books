import Vue from 'vue'

Vue.filter('truncate', (value, limit) => {
    const regex = /(<([^>]+)>)/gi
    let newValue = value.replace(regex, '')
    newValue = newValue.trim()

    if (newValue.length > limit) {
        newValue = newValue.substring(0, limit - 3) + '...'
    }

    return newValue
})
