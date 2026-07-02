import Vue from "vue";
import VueRouter from "vue-router";
import store from "./store";

Vue.use(VueRouter);

/**
 * Route Guard Function
 */
const checkPermission = async (to, from, next, options = {}) => {
    if (!store.getters.user.roles || store.getters.user.roles.length === 0) {
        await store.dispatch("getUser");
    }

    const roles = store.getters.user.roles;
    const { requireSuperAdmin = false, requiredRoles = [] } = options;

    if (roles.includes("super_admin")) {
        return next();
    }

    if (requireSuperAdmin) {
        return next("/");
    }

    if (requiredRoles.length > 0) {
        const hasRole = requiredRoles.some(role => roles.includes(role));
        if (!hasRole) {
            return next("/");
        }
    }

    return next();
};

const Dashboard = () => import(/* webpackChunkName: "backend.dashboard" */ "./views/Dashboard");
const Settings = () => import(/* webpackChunkName: "backend.settings" */ "./views/Settings");
const Profile = () => import(/* webpackChunkName: "backend.profile" */ "./views/Profile");
const Projects = () => import(/* webpackChunkName: "backend.projects" */ "./views/Projects");
const ProjectIndex = () => import(/* webpackChunkName: "project.index" */ "./views/Project.Index/Index");
const ProjectCollectionIndex = () => import(/* webpackChunkName: "project.collection" */ "./views/Project.Collection/CollectionIndex");
const ProjectCollectionList = () => import(/* webpackChunkName: "project.collection.list" */ "./views/Project.Collection/CollectionList");
const ProjectContentIndex = () => import(/* webpackChunkName: "project.content" */ "./views/Project.Content/ContentIndex");
const ProjectContentList = () => import(/* webpackChunkName: "project.content.list" */ "./views/Project.Content/List");
const ProjectContentNew = () => import(/* webpackChunkName: "project.content.new" */ "./views/Project.Content/New");
const ProjectContentEdit = () => import(/* webpackChunkName: "project.content.edit" */ "./views/Project.Content/Edit");
const ProjectContentForms = () => import(/* webpackChunkName: "project.content.forms" */ "./views/Project.Content/Forms");
const ProjectContentFormsDetail = () => import(/* webpackChunkName: "project.content.forms.detail" */ "./views/Project.Content/FormsDetail");
const ProjectContentMedia = () => import(/* webpackChunkName: "project.content.media" */ "./views/Project.Content/Media");
const ProjectSettingsIndex = () => import(/* webpackChunkName: "project.settings" */ "./views/Project.Settings/SettingsIndex");
const ProjectSettingsLocales = () => import(/* webpackChunkName: "project.settings.locales" */ "./views/Project.Settings/Locales");
const ProjectSettingsUsers = () => import(/* webpackChunkName: "project.settings.users" */ "./views/Project.Settings/Users");
const ProjectSettingsAPI = () => import(/* webpackChunkName: "project.settings.api" */ "./views/Project.Settings/API");
const ProjectSettingsWebhooks = () => import(/* webpackChunkName: "project.settings.webhooks" */ "./views/Project.Settings/Webhooks");
const ProjectSettingsWebhookLogs = () => import(/* webpackChunkName: "project.settings.webhookslogs" */ "./views/Project.Settings/WebhookLogs");


/**
 * Routes
 */
const routes = [
  { path: "/", name: "dashboard", component: Dashboard },
  { path: "/settings", name: "settings", component: Settings },
  { path: "/profile", name: "profile", component: Profile },
  { path: "/projects/", name: "projects", component: Projects },
  {
    path: "/project/:project_id",
    name: "projects.index",
    component: ProjectIndex,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
        }),
  },
  {
    path: "/project/:project_id/collections",
    name: "projects.collections",
    component: ProjectCollectionIndex,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: ["admin" + to.params.project_id],
        }),
  },
  {
    path: "/project/:project_id/collections/:col_id",
    name: "projects.collections.list",
    component: ProjectCollectionList,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: ["admin" + to.params.project_id],
        }),
  },
  {
    path: "/project/:project_id/content",
    name: "projects.content",
    component: ProjectContentIndex,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
        }),
  },
  {
    path: "/project/:project_id/content/:col_id",
    name: "projects.content.list",
    component: ProjectContentList,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
        }),
  },
  {
    path: "/project/:project_id/content/:col_id/new",
    name: "projects.content.new",
    component: ProjectContentNew,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
        }),
  },
  {
    path: "/project/:project_id/content/:col_id/edit/:content_id",
    name: "projects.content.edit",
    component: ProjectContentEdit,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
        }),
  },
  {
    path: "/project/:project_id/content/:col_id/forms",
    name: "projects.content.forms",
    component: ProjectContentForms,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
        }),
  },
  {
    path: "/project/:project_id/content/:col_id/forms/:form_id",
    name: "projects.content.forms.detail",
    component: ProjectContentFormsDetail,
    beforeEnter: async (to, from, next) =>
      checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
      }),
  },
  {
    path: "/project/:project_id/settings",
    name: "projects.settings",
    component: ProjectSettingsIndex,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: ["admin" + to.params.project_id],
        }),
  },
  {
    path: "/project/:project_id/settings/locales",
    name: "projects.settings.locales",
    component: ProjectSettingsLocales,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: ["admin" + to.params.project_id],
        }),
  },
  {
    path: "/project/:project_id/settings/users",
    name: "projects.settings.users",
    component: ProjectSettingsUsers,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: ["admin" + to.params.project_id],
        }),
  },
  {
    path: "/project/:project_id/settings/api",
    name: "projects.settings.api",
    component: ProjectSettingsAPI,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, { requireSuperAdmin: true }),
  },
  {
    path: "/project/:project_id/settings/webhooks",
    name: "projects.settings.webhooks",
    component: ProjectSettingsWebhooks,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, { requireSuperAdmin: true }),
  },
  {
    path: "/project/:project_id/settings/webhooks/:webhook_id/logs",
    name: "projects.settings.webhooks.logs",
    component: ProjectSettingsWebhookLogs,
    beforeEnter: async (to, from, next) =>
      checkPermission(to, from, next, { requireSuperAdmin: true }),
  },
  {
    path: "/project/:project_id/media_library",
    name: "projects.media_library",
    component: ProjectContentMedia,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: [
                "admin" + to.params.project_id,
                "editor" + to.params.project_id,
            ],
        }),
  },
];

const router = new VueRouter({
    routes: routes,
    linkExactActiveClass: "bg-gray-100",
});

router.beforeEach(async (to, from, next) => {
    const hasRoles = store.getters.user.roles && store.getters.user.roles.length > 0;

    if (!hasRoles) {
        await store.dispatch("getUser");
    }

    // Check if entering a project page
    const isProjectPage = to.path.includes('/project/') && to.params.project_id;
    const wasProjectPage = from && from.path && from.path.includes('/project/') && from.params && from.params.project_id;

    // If it's a project page, ensure project data is loaded
    if (isProjectPage) {
        const currentProjectId = store.getters.currentProject?.id;
        // Load project data on first visit or when switching projects
        if (!currentProjectId || currentProjectId != to.params.project_id) {
            await store.dispatch('setCurrentProject', to.params.project_id);
        }
    } else if (wasProjectPage && !isProjectPage) {
        // Clear current project when leaving a project page
        store.dispatch('setCurrentProject', null);
    }

    next();
});
export default router;
