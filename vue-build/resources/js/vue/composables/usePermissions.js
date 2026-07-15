/**
 * Permission gate. In production, replace the mock set with the authenticated
 * user's permissions (Spatie). Components call `can('task_create')` to
 * conditionally render actions — graceful empty when denied.
 */
import { reactive } from 'vue';

// Mock: everything granted. Swap for real perms at boot via setPermissions().
const state = reactive({ perms: new Set(['*']), canDelete: true });

export function setPermissions(list = [], canDelete = true) {
  state.perms = new Set(list);
  state.canDelete = canDelete;
}

export function usePermissions() {
  function can(perm) {
    return state.perms.has('*') || state.perms.has(perm);
  }
  return { can, canDelete: () => state.canDelete };
}
