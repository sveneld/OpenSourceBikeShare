import axios from 'axios'

export const state = {
    currentUser: getSavedState('auth.currentUser'),
    currentToken: getSavedState('auth.currentToken')
}

export const mutations = {
    SET_CURRENT_USER(state, newValue) {
        state.currentUser = newValue
        saveState('auth.currentUser', newValue)
    },
    SET_CURRENT_USER_TOKEN(state, newValue) {
        state.currentToken = newValue
        saveState('auth.currentToken', newValue)
        setDefaultAuthHeaders(state)
    },
}

export const getters = {
    // Whether the user is currently logged in (=has token).
    loggedIn(state) {
        console.log('getters:auth/loggedIn')
        return !!state.currentToken
    },
}

export const actions = {
    // This is automatically run in `src/state/store.js` when the app
    // starts, along with any other actions named `init` in other modules.
    init({ state, dispatch }) {
        setDefaultAuthHeaders(state)
        dispatch('validate')
    },

    // Logs in the current user.
    logIn({ commit, dispatch, getters }, { username, password } = {}) {
        // TODO: validate login
        // if (getters.loggedIn) return dispatch('validate')

        return axios.post('/api/auth/authenticate', {
            phone_number: username,
            password: password
        }).then(response => {
            const data = response.data
            const token = data.token
            commit('SET_CURRENT_USER_TOKEN', token)
            return token
        })
    },

    // Logs out the current user.
    logOut({ commit }) {
        commit('SET_CURRENT_USER_TOKEN', null)
    },

    // Validates the current user's token and refreshes user data
    // with new data from the API.
    validate({ commit, state }) {
        if (!state.currentToken) return Promise.resolve(null)

        return axios
            .get('/api/me')
            .then(response => {
                const user = response.data
                commit('SET_CURRENT_USER', user)
                return user
            })
            .catch(error => {
                if (error.response.status === 401) {
                    commit('SET_CURRENT_USER', null)
                }
                return null
            })
    },
}

// ===
// Private helpers
// ===

function getSavedState(key) {
    return JSON.parse(window.localStorage.getItem(key))
}

function saveState(key, state) {
    window.localStorage.setItem(key, JSON.stringify(state))
}

function setDefaultAuthHeaders(state) {
    axios.defaults.headers.common.Authorization = state.currentToken
        ? "Bearer " + state.currentToken
        : ''
}
