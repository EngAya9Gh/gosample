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
        $exists = \DB::table('permissions')->where('name', 'report_access')->exists();
        
        if (!$exists) {
            $permissionId = \DB::table('permissions')->insertGetId([
                'name' => 'report_access',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Also assign it to Admin Role (assuming Admin is role_id 1)
            \DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => 1
            ]);
        }
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
            \DB::table('permission_role')->where('permission_id', $permission->id)->delete();
            \DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
