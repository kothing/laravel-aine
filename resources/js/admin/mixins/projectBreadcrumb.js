/**
 * projectBreadcrumb mixin
 *
 * Generates a unified Topbar breadcrumb for project-related pages.
 *
 * Dependencies:
 *  - useAdminStore().currentProject (router.beforeEach already dispatches
 *    setCurrentProject before entering any /project/ page)
 *  - this.$route.name / this.$route.params (project_id, col_id)
 */
import { useAdminStore } from '../store';

const SEGMENT_DEFS = {
    'projects.collections': [
        { label: 'Collections', icon: 'fa fa-table' },
    ],
    'projects.collections.list': [
        { label: 'Collections', route: 'projects.collections', icon: 'fa fa-table' },
        { label: 'Collection', storeKey: 'currentCollection', icon: 'fa fa-folder' },
    ],
    'projects.content': [
        { label: 'Content', icon: 'fa fa-edit' },
    ],
    'projects.content.list': [
        { label: 'Content', route: 'projects.content', icon: 'fa fa-edit' },
        { label: 'Collection', storeKey: 'currentCollection', icon: 'fa fa-folder' },
    ],
    'projects.content.new': [
        { label: 'Content', route: 'projects.content', icon: 'fa fa-edit' },
        { label: 'Collection', storeKey: 'currentCollection', icon: 'fa fa-folder' },
        { label: 'Create Content', icon: 'fa fa-plus' },
    ],
    'projects.content.edit': [
        { label: 'Content', route: 'projects.content', icon: 'fa fa-edit' },
        { label: 'Collection', storeKey: 'currentCollection', icon: 'fa fa-folder' },
        { label: 'Edit Content', icon: 'fa fa-pen' },
    ],
    'projects.content.forms': [
        { label: 'Content', route: 'projects.content', icon: 'fa fa-edit' },
        { label: 'Collection', storeKey: 'currentCollection', icon: 'fa fa-folder' },
        { label: 'Forms', icon: 'fa fa-wpforms' },
    ],
    'projects.content.forms.detail': [
        { label: 'Content', route: 'projects.content', icon: 'fa fa-edit' },
        { label: 'Collection', storeKey: 'currentCollection', icon: 'fa fa-folder' },
        { label: 'Forms', route: 'projects.content.forms', icon: 'fa fa-wpforms' },
        { label: 'Form', nameKey: 'breadcrumbFormName', icon: 'fa fa-file-alt' },
    ],
    'projects.media_library': [
        { label: 'Media Library' },
    ],
    'projects.settings': [
        { label: 'Settings', icon: 'fa fa-cog' },
    ],
    'projects.settings.locales': [
        { label: 'Settings', route: 'projects.settings', icon: 'fa fa-cog' },
        { label: 'Localization' },
    ],
    'projects.settings.translations': [
        { label: 'Settings', route: 'projects.settings', icon: 'fa fa-cog' },
        { label: 'Translations' },
    ],
    'projects.settings.language': [
        { label: 'Settings', route: 'projects.settings', icon: 'fa fa-cog' },
        { label: 'Language' },
    ],
    'projects.settings.users': [
        { label: 'Settings', route: 'projects.settings', icon: 'fa fa-cog' },
        { label: 'Users & Roles' },
    ],
    'projects.settings.api': [
        { label: 'Settings', route: 'projects.settings', icon: 'fa fa-cog' },
        { label: 'API Access' },
    ],
    'projects.settings.webhooks': [
        { label: 'Settings', route: 'projects.settings', icon: 'fa fa-cog' },
        { label: 'Webhooks' },
    ],
    'projects.settings.webhooks.logs': [
        { label: 'Settings', route: 'projects.settings', icon: 'fa fa-cog' },
        { label: 'Webhooks', route: 'projects.settings.webhooks' },
        { label: 'Logs' },
    ],
};

export default {
    computed: {
        breadcrumb() {
            const store = useAdminStore();
            const route_name = this.$route.name;
            const project_id = this.$route.params.project_id;
            const project = store.currentProject;
            const project_name = (project && project.name) || 'Project';

            const items = [
                { name: 'Dashboard', url: '/', icon: 'fa fa-tachometer-alt' },
                { name: 'Projects List', url: { name: 'projects' }, icon: 'fas fa-list' },
            ];

            if (!route_name || route_name === 'projects' || !project_id) {
                return items;
            }

            if (route_name === 'projects.index') {
                items.push({ name: project_name, icon: 'fa fa-cubes' });
            } else {
                items.push({
                    name: project_name,
                    url: { name: 'projects.index', params: { project_id } },
                    icon: 'fa fa-cubes',
                });
            }

            const defs = SEGMENT_DEFS[route_name];
            if (defs) {
                defs.forEach((seg, index) => {
                    const is_last = index === defs.length - 1;
                    const item = { name: seg.label };
                    if (seg.icon) {
                        item.icon = seg.icon;
                    }
                    if (seg.storeKey) {
                        const storeData = store[seg.storeKey];
                        if (storeData && storeData.name) {
                            item.name = storeData.name;
                        }
                    } else if (seg.nameKey && this[seg.nameKey]) {
                        item.name = this[seg.nameKey];
                    }
                    if (!is_last && seg.route) {
                        item.url = {
                            name: seg.route,
                            params: { ...this.$route.params },
                        };
                    }
                    items.push(item);
                });
            }

            return items;
        },
    },

    methods: {
        setTopbar() {
            const store = useAdminStore();
            store.setTopbarContent({
                breadcrumb: this.breadcrumb,
            });
        },
    },

    created() {
        this.setTopbar();
    },

    watch: {
        '$route.params.project_id'() {
            this.setTopbar();
        },
        '$route.params.col_id'() {
            this.setTopbar();
        },
    },
};
