<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;

/**
 * Voyager BREAD for categories (and belongsToMany on books/borrow_orders) expects
 * `parent_id` and `order`; without these columns the DB raises errors (browse 500).
 * Hooks routes require Voyager hooks permissions granted to admin.
 */
class AddVoyagerCategoryColumnsAndHooksPermissions extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable();
            }
            if (! Schema::hasColumn('categories', 'order')) {
                $table->unsignedInteger('order')->default(0);
            }
        });

        if (Schema::hasColumn('categories', 'order')) {
            DB::table('categories')->update(['order' => DB::raw('id')]);
        }

        Permission::generateFor('hooks');

        $role = Role::where('name', 'admin')->first();
        if ($role !== null) {
            $hooksPermissionIds = Permission::where('table_name', 'hooks')->pluck('id')->all();
            if ($hooksPermissionIds !== []) {
                $role->permissions()->syncWithoutDetaching($hooksPermissionIds);
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'parent_id')) {
                    $table->dropColumn('parent_id');
                }
                if (Schema::hasColumn('categories', 'order')) {
                    $table->dropColumn('order');
                }
            });
        }

        $hookIds = Permission::where('table_name', 'hooks')->pluck('id');
        if ($hookIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $hookIds->all())->delete();
            Permission::whereIn('id', $hookIds->all())->delete();
        }
    }
}
