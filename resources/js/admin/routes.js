import { createRouter, createWebHistory } from 'vue-router';
import { useAdminStore } from './store';
import axios from 'axios';

/**
 * Route Guard Function
 */
const checkPermission = async (to, from, next, options = {}) => {
    const store = useAdminStore();

    if (!store.user.roles || store.user.roles.length === 0) {
        await store.getUser();
    }

    const roles = store.user.roles;
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

const Dashboard = () => import("./views/Dashboard.vue");
const Settings = () => import("./views/Settings.vue");
const Language = () => import("./views/Language.vue");
const Translations = () => import("./views/Translations.vue");
const Localization = () => import("./views/Localization.vue");
const Profile = () => import("./views/Profile.vue");
const Projects = () => import("./views/Projects.vue");
const ProjectIndex = () => import("./views/Project.Index/Index.vue");
const ProjectCollectionIndex = () => import("./views/Project.Collection/CollectionIndex.vue");
const ProjectCollectionList = () => import("./views/Project.Collection/CollectionList.vue");
const ProjectContentIndex = () => import("./views/Project.Content/ContentIndex.vue");
const ProjectContentList = () => import("./views/Project.Content/List.vue");
const ProjectContentNew = () => import("./views/Project.Content/New.vue");
const ProjectContentEdit = () => import("./views/Project.Content/Edit.vue");
const ProjectContentForms = () => import("./views/Project.Content/Forms.vue");
const ProjectContentFormsDetail = () => import("./views/Project.Content/FormsDetail.vue");
const ProjectContentMedia = () => import("./views/Project.Content/Media.vue");
const ProjectSettingsIndex = () => import("./views/Project.Settings/SettingsIndex.vue");
const ProjectSettingsLocales = () => import("./views/Project.Settings/Locales.vue");
const ProjectSettingsUsers = () => import("./views/Project.Settings/Users.vue");
const ProjectSettingsAPI = () => import("./views/Project.Settings/API.vue");
const ProjectSettingsWebhooks = () => import("./views/Project.Settings/Webhooks.vue");
const ProjectSettingsWebhookLogs = () => import("./views/Project.Settings/WebhookLogs.vue");
const ProjectSettingsTranslations = () => import("./views/Project.Settings/ProjectTranslations.vue");
const ProjectSettingsLanguage = () => import("./views/Project.Settings/ProjectLanguage.vue");
const ProjectSettingsAuditLogs = () => import("./views/Project.Settings/AuditLogs.vue");

/**
 * Routes
 */
const routes = [
  { path: "/", name: "dashboard", component: Dashboard },
  { path: "/settings", name: "settings", component: Settings },
  { path: "/language", name: "language", component: Language },
  { path: "/localization", name: "localization", component: Localization },
  { path: "/translations", name: "translations", component: Translations },
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
        checkPermission(to, from, next, { requireSuperAdmin: true }),
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
        checkPermission(to, from, next, { requireSuperAdmin: true }),
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
    path: "/project/:project_id/settings/translations",
    name: "projects.settings.translations",
    component: ProjectSettingsTranslations,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, { requireSuperAdmin: true }),
  },
  {
    path: "/project/:project_id/settings/language",
    name: "projects.settings.language",
    component: ProjectSettingsLanguage,
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
    path: "/project/:project_id/settings/audit-logs",
    name: "projects.settings.audit-logs",
    component: ProjectSettingsAuditLogs,
    beforeEnter: async (to, from, next) =>
        checkPermission(to, from, next, {
            requiredRoles: ["admin" + to.params.project_id],
        }),
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

const router = createRouter({
    history: createWebHistory('/admin'),
    routes: routes,
});

router.afterEach(() => {
    const store = useAdminStore();
    // Route navigation (lazy chunk + async guard data) finished: hide the
    // transition overlay shown in the Layout shell.
    store.routeLoading = false;
});

router.beforeEach(async (to, from, next) => {
    const store = useAdminStore();
    // Show the route transition overlay while the lazy chunk and the async
    // guard data (user / project / collection) load, so switches never flash
    // a blank or half-rendered page. Cleared in afterEach.
    store.routeLoading = true;
    const hasRoles = store.user.roles && store.user.roles.length > 0;

    if (!hasRoles) {
        await store.getUser();
    }

    // Check if entering a project page
    const isProjectPage = to.path.includes('/project/') && to.params.project_id;
    const wasProjectPage = from && from.path && from.path.includes('/project/') && from.params && from.params.project_id;

    // If it's a project page, ensure project data is loaded
    if (isProjectPage) {
        const currentProjectId = store.currentProject?.id;
        const projectChanged = !currentProjectId || currentProjectId != to.params.project_id;

        if (projectChanged) {
            // No cached data for this project (first visit or switching
            // projects): drop the stale copy and block until the fresh data
            // is in the store so pages never render the wrong project shell.
            store.currentProject = null;
            store.currentCollection = null;
            await store.setCurrentProject(to.params.project_id);
        } else {
            // Same project: navigate immediately without waiting on the
            // network, and silently re-validate the data in the background
            // (stale-while-revalidate) so it never goes stale. The refresh
            // is flagged silent so it doesn't trigger the NProgress bar.
            if (Date.now() - (store.currentProjectLoadedAt || 0) > 30000) {
                store.setCurrentProject(to.params.project_id, { silent: true });
            }
        }

        if (to.params.col_id) {
            const currentCollectionId = store.currentCollection?.id;
            if (!currentCollectionId || currentCollectionId != to.params.col_id) {
                await store.setCurrentCollection({
                    projectId: to.params.project_id,
                    colId: to.params.col_id,
                });
            }
        } else {
            store.currentCollection = null;
        }
    } else if (wasProjectPage && !isProjectPage) {
        // Clear current project when leaving a project page
        store.setCurrentProject(null);
        store.currentCollection = null;
    }

    next();
});

export default router;
