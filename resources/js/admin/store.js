import Vue from 'vue';
import Vuex from 'vuex';
import axios from 'axios'

Vue.use(Vuex);

const getDefaultState = () => {
    return {
        user: {},
        currentProject: null,
        currentCollection: null,
        topbarContent: null,
        columnSettings: [],
    }
  }

const store = new Vuex.Store({
    state: {
        user: {},
        settings: {},
        currentProject: null,
        currentCollection: null,
        topbarContent: null,
        columnSettings: [],
    },
    mutations: {
        UPDATE_USER: (state, user) => {
            state.user = user;
        },
        SET_TOPBAR_CONTENT: (state, component) => {
            state.topbarContent = component;
        },
        CLEAR_TOPBAR_CONTENT: (state) => {
            state.topbarContent = null;
        },
        SET_CURRENT_PROJECT: (state, project) => {
            state.currentProject = project;
        },
        CLEAR_CURRENT_PROJECT: (state) => {
            state.currentProject = null;
        },
        SET_CURRENT_COLLECTION: (state, collection) => {
            state.currentCollection = collection;
        },
        CLEAR_CURRENT_COLLECTION: (state) => {
            state.currentCollection = null;
        },
        SET_COLUMNS: (state, obj) => {
            state.columnSettings.push(obj);
        },
        UPDATE_COLUMN: (state, obj) => {
            state.columnSettings.find(o => (o.collection_id === obj.collection_id)).columns = obj.columns;
        },
        LOGOUT: (state) => {
            Object.assign(state, getDefaultState())
        },
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
        async setCurrentCollection({commit}, {projectId, colId}) {
            if (!colId) {
                commit('CLEAR_CURRENT_COLLECTION');
                return;
            }
            try {
                const response = await axios.get('/admin/collections/show/' + projectId + '/' + colId);
                commit('SET_CURRENT_COLLECTION', response.data.collection);
            } catch (error) {
                console.error('Failed to load collection:', error);
                commit('CLEAR_CURRENT_COLLECTION');
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
        currentCollection: state => state.currentCollection,
    }
})

export default store;