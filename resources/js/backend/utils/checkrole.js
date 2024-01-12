export default function checkRole(value) {
  if (value && value instanceof Array && value.length > 0) {
    const roles = this.$store.getters && this.$store.getters.user.roles;

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
