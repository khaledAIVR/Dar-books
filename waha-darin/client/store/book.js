import axios from 'axios'
import { shuffledCopy } from '~/utils'

let fetchBooksRequest = null

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
        state.books = books
    }
}

// actions
export const actions = {
    fetchBooks({ commit, state }) {
        if (state.books.length > 0) {
            return
        }
        if (fetchBooksRequest) {
            return fetchBooksRequest
        }

        fetchBooksRequest = (async () => {
            try {
                const { data } = await axios.get('/books')
                commit('SET_BOOKS', shuffledCopy(data.data))
            } catch (e) {
                commit('FETCH_BOOKS_FAILURE')
            } finally {
                fetchBooksRequest = null
            }
        })()

        return fetchBooksRequest
    }
}
