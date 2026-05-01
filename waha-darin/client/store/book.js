import axios from 'axios'

// state
export const state = () => ({
    books: []
})

// getters
export const getters = {
    books: (state) => state.books
}

// mutations
export const mutations = {
    SET_BOOKS(state, books) {
        for (const book of books) {
            state.books.push(book)
        }
    }
}

// actions
export const actions = {
    async fetchBooks({ commit }) {
        try {
            const { data } = await axios.get('/books')
            commit('SET_BOOKS', data.data)
        } catch (e) {
            // debugger
            commit('FETCH_BOOKS_FAILURE')
        }
    }
}
