import axios from 'axios'
import Vue from 'vue'

// state
export const state = () => ({
    authors: {
        total: 0,
        authors: []
    }
})

// getters
export const getters = {
    authors: (state) => state.authors,
    author: (state) => (id) =>
        state.authors.authors.find((auth) => auth.id === id)
}

// mutations
export const mutations = {
    SET_AUTHORS(state, authors) {
        if (state.authors.authors.length <= 0) {
            state.authors.total = authors.total
        }
        for (const author of authors.data) {
            state.authors.authors.push(author)
        }
    },
    SET_AUTHOR(state, author) {
        state.authors.authors.push(author)
    },
    SET_AUTHOR_BOOKS(state, data) {
        const author = state.authors.authors.find(
            (author) => author.id === Number(data.author_id)
        )
        if (!author.books) {
            Vue.set(author, 'books', data.data)
        } else {
            for (const book of data.data) {
                author.books.push(book)
            }
        }
    },
    FETCH_AUTHORS_FAILURE() {},
    FETCH_CATEGORIES_FAILURE() {},
    SET_AUTHOR_BOOKS_FAILURE() {}
}

// actions
export const actions = {
    async fetchAuthors({ state, commit }, { page = 1, per_page = 15 }) {
        if (page === 1 && state.authors.authors.length > 1) {
            return
        }
        try {
            const { data } = await axios.get(
                `/authors?page=${page}&per_page=${per_page}`
            )
            commit('SET_AUTHORS', data)
        } catch (e) {
            commit('FETCH_AUTHORS_FAILURE')
            if (typeof console !== 'undefined' && console.error) {
                console.error('[author/fetchAuthors]', e.response?.status, e.message)
            }
        }
    },
    async fetchAuthorBooks({ state, commit }, authorId) {
        let author = state.authors.authors.find(
            (cat) => cat.id === Number(authorId)
        )
        if (!author) {
            author = await axios.get(`/authors/${authorId}`)
            commit('SET_AUTHOR', author.data)
        }
        if (author.books) {
            return
        }
        try {
            const { data } = await axios.get(`/books?author_id=${authorId}`)
            data.author_id = authorId
            commit('SET_AUTHOR_BOOKS', data)
        } catch (e) {
            commit('SET_AUTHOR_BOOKS_FAILURE')
            if (typeof console !== 'undefined' && console.error) {
                console.error('[author/fetchAuthorBooks]', e.response?.status, e.message)
            }
        }
    },
    async fetchAllAuthorBooksPaginated(
        { state, commit },
        { authorId, page = 1, perPage = 10 }
    ) {
        authorId = Number(authorId)
        const author = state.authors.authors.find(
            (auth) => auth.id === authorId
        )
        if (author.books && author.books.length === author.books_count) {
            return
        }

        try {
            const { data } = await axios.get(
                `/books?author_id=${authorId}&page=${page}&per_page=${perPage}`
            )
            data.category_id = authorId
            commit('SET_AUTHOR_BOOKS', data)
        } catch (e) {
            commit('FETCH_CATEGORIES_FAILURE')
            if (typeof console !== 'undefined' && console.error) {
                console.error('[author/fetchAllAuthorBooksPaginated]', e.response?.status, e.message)
            }
        }
    }
}
