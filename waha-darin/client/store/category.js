import axios from 'axios'
import Vue from 'vue'

// state
export const state = () => ({
    categories: {
        total: 0,
        categories: []
    }
})

// getters
export const getters = {
    categories: (state) => state.categories,
    category: (state) => (id) =>
        state.categories.categories.find((cat) => cat.id === id)
}

// mutations
export const mutations = {
    SET_CATEGORIES(state, { data, page = 1 }) {
        if (page === 1) {
            state.categories.categories = []
            state.categories.total = data.total
        }
        const names = new Set(state.categories.categories.map((c) => c.name))
        for (const category of data.data) {
            if (!names.has(category.name)) {
                names.add(category.name)
                state.categories.categories.push(category)
            }
        }
        if (page > 1 && data.data.length === 0) {
            state.categories.total = state.categories.categories.length
        }
    },
    SET_CATEGORY(state, category) {
        state.categories.categories.push(category[0])
    },
    SET_CATEGORY_BOOKS(state, data) {
        const category = state.categories.categories.find(
            (cat) => cat.id === Number(data.category_id)
        )
        if (!category.books) {
            Vue.set(category, 'books', data.data)
        } else {
            for (const book of data.data) {
                category.books.push(book)
            }
        }
    }
}

// actions
export const actions = {
    async fetchCategories({ state, commit }, { page = 1, per_page = 15 }) {
        if (page === 1 && state.categories.categories.length > 1) {
            return
        }
        try {
            const { data } = await axios.get(
                `/categories?page=${page}&per_page=${per_page}`
            )
            commit('SET_CATEGORIES', { data, page })
        } catch (e) {
            commit('FETCH_CATEGORIES_FAILURE')
        }
    },
    async fetchCategoryBooks({ state, commit }, categoryId) {
        let category = state.categories.categories.find(
            (cat) => cat.id === Number(categoryId)
        )
        if (!category) {
            category = await axios.get(`/categories/${categoryId}`)
            commit('SET_CATEGORY', category.data)
        }
        if (category.books) {
            return
        }

        try {
            const { data } = await axios.get(`/books?category_id=${categoryId}`)
            data.category_id = categoryId
            commit('SET_CATEGORY_BOOKS', data)
        } catch (e) {
            commit('FETCH_CATEGORIES_FAILURE')
        }
    },
    async fetchAllCategoryBooksPaginated(
        { state, commit },
        { categoryId, page = 1, perPage = 10 }
    ) {
        categoryId = Number(categoryId)
        const category = state.categories.categories.find(
            (cat) => cat.id === categoryId
        )
        if (category.books && category.books.length === category.books_count) {
            return
        }

        try {
            const { data } = await axios.get(
                `/books?category_id=${categoryId}&page=${page}&per_page=${perPage}`
            )
            data.category_id = categoryId
            commit('SET_CATEGORY_BOOKS', data)
        } catch (e) {
            commit('FETCH_CATEGORIES_FAILURE')
        }
    }
}
