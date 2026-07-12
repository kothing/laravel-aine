import Vue from 'vue';
import Vuex from 'vuex';
import axios from 'axios'

Vue.use(Vuex);

const getDefaultState = () => {
    return {
        user: {},
        columnSettings: [],
        currentProject: null
    }
  }
  
const store = new Vuex.Store({
    state: {
        user: {},
        settings: {},
        columnSettings: [],
        currentProject: null
    },
    mutations: {
        UPDATE_USER: (state, user) => {
            state.user = user;
        },
        SET_COLUMNS: (state, obj) => {
            state.columnSettings.push(obj);
        },
        UPDATE_COLUMN: (state, obj) => {
            state.columnSettings.find(o => (o.collection_id === obj.collection_id)).columns = obj.columns;
        },
        SET_CURRENT_PROJECT: (state, project) => {
            state.currentProject = project;
        },
        CLEAR_CURRENT_PROJECT: (state) => {
            state.currentProject = null;
        },
        LOGOUT: (state) => {
            Object.assign(state, getDefaultState())
        }
    },
    actions: {
        async getUser({commit}) {
            return await axios
                .get('admin/user')
                .then((response) => { commit('UPDATE_USER', response.data) });
        },
        async setCurrentProject({commit}, projectId) {
            if (!projectId) {
                commit('CLEAR_CURRENT_PROJECT');
                return;
            }
            try {
                const response = await axios.get('/admin/projects/' + projectId);
                commit('SET_CURRENT_PROJECT', response.data);
            } catch (error) {
                console.error('Failed to load project:', error);
                commit('CLEAR_CURRENT_PROJECT');
            }
        },
        setColumns({commit}, obj){
            commit('SET_COLUMNS', obj)
        },
        updateColumn({commit}, obj){
            commit('UPDATE_COLUMN', obj)
        }
    },
    getters: {
        user: state => state.user,
        columnSettings: state => state.columnSettings,
        currentProject: state => state.currentProject,
    }
})

export default store;