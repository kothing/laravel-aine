import Vue from 'vue';
import Vuex from 'vuex';
import axios from 'axios';

Vue.use(Vuex);

const getDefaultState = () => {
    return {
        settings: {
            name: null,
            description: null,
            version: '0.0.1',
        },
        settingsLoaded: false,
    };
};

const store = new Vuex.Store({
    state: getDefaultState(),

    mutations: {
        SET_SETTINGS: (state, settings) => {
            state.settings = settings;
            state.settingsLoaded = true;
        },
        RESET_SETTINGS: (state) => {
            Object.assign(state, getDefaultState());
        },
    },

    actions: {
        async loadSettings({ commit, state }) {
            // If already loaded, return directly
            if (state.settingsLoaded) {
                return Promise.resolve(state.settings);
            }
            
            try {
                const response = await axios.get('/settings');
                commit('SET_SETTINGS', response.data);
                return response.data;
            } catch (error) {
                console.warn('Failed to load settings:', error);
                return state.settings;
            }
        },
    },

    getters: {
        settings: state => state.settings,
        settingsLoaded: state => state.settingsLoaded,
    },
});

export default store;