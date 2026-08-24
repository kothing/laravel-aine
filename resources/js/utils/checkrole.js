import { useAdminStore } from '../admin/store';

export default function checkRole(value) {
  if (value && value instanceof Array && value.length > 0) {
    const store = useAdminStore();
    const roles = store.user && store.user.roles ? store.user.roles : [];

    if (roles.includes("super_admin")) {
      return true;
    }

    const hasRole = roles.some((role) => {
      return value.includes(role);
    });

    return hasRole;
  } else {
    console.error(`Need roles! Like v-role="['admin','editor']"`);
    return false;
  }
}
