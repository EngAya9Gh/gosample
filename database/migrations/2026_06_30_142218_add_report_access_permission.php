<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ensure the permission exists (all permissions in this app use the 'web' guard).
        $permission = \DB::table('permissions')->where('name', 'report_access')->first();

        if ($permission) {
            $permissionId = $permission->id;
        } else {
            $permissionId = \DB::table('permissions')->insertGetId([
                'name' => 'report_access',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign it to the Admin role (role_id 1). Spatie pivot is role_has_permissions.
        $alreadyLinked = \DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->where('role_id', 1)
            ->exists();

        if (!$alreadyLinked) {
            \DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId,
                'role_id' => 1,
            ]);
        }

        // Flush Spatie's cached permission map so the new grant takes effect immediately.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permission = \DB::table('permissions')->where('name', 'report_access')->first();
        if ($permission) {
            \DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            \DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
