<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config()->boolean('permission.teams');
        $permissionsTable = config()->string('permission.table_names.permissions');
        $rolesTable = config()->string('permission.table_names.roles');
        $modelHasPermissionsTable = config()->string('permission.table_names.model_has_permissions');
        $modelHasRolesTable = config()->string('permission.table_names.model_has_roles');
        $roleHasPermissionsTable = config()->string('permission.table_names.role_has_permissions');
        $modelMorphKey = config()->string('permission.column_names.model_morph_key');
        $teamForeignKey = config()->string('permission.column_names.team_foreign_key');
        $configuredPivotRole = config('permission.column_names.role_pivot_key');
        $configuredPivotPermission = config('permission.column_names.permission_pivot_key');
        $pivotRole = is_string($configuredPivotRole) ? $configuredPivotRole : 'role_id';
        $pivotPermission = is_string($configuredPivotPermission) ? $configuredPivotPermission : 'permission_id';

        /**
         * See `docs/prerequisites.md` for suggested lengths on 'name' and 'guard_name' if "1071 Specified key was too long" errors are encountered.
         */
        Schema::create($permissionsTable, static function (Blueprint $table): void {
            $table->id(); // permission id
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        /**
         * See `docs/prerequisites.md` for suggested lengths on 'name' and 'guard_name' if "1071 Specified key was too long" errors are encountered.
         */
        Schema::create($rolesTable, static function (Blueprint $table) use ($teams, $teamForeignKey): void {
            $table->id(); // role id
            if ($teams || config()->boolean('permission.testing', false)) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($teamForeignKey)->nullable();
                $table->index($teamForeignKey, 'roles_team_foreign_key_index');
            }

            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            if ($teams || config()->boolean('permission.testing', false)) {
                $table->unique([$teamForeignKey, 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($modelHasPermissionsTable, static function (Blueprint $table) use ($permissionsTable, $modelMorphKey, $pivotPermission, $teamForeignKey, $teams): void {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($modelMorphKey);
            $table->index([$modelMorphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($permissionsTable)
                ->cascadeOnDelete();
            if ($teams) {
                $table->unsignedBigInteger($teamForeignKey);
                $table->index($teamForeignKey, 'model_has_permissions_team_foreign_key_index');

                $table->primary([$teamForeignKey, $pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($modelHasRolesTable, static function (Blueprint $table) use ($rolesTable, $modelMorphKey, $pivotRole, $teamForeignKey, $teams): void {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($modelMorphKey);
            $table->index([$modelMorphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($rolesTable)
                ->cascadeOnDelete();
            if ($teams) {
                $table->unsignedBigInteger($teamForeignKey);
                $table->index($teamForeignKey, 'model_has_roles_team_foreign_key_index');

                $table->primary([$teamForeignKey, $pivotRole, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($roleHasPermissionsTable, static function (Blueprint $table) use ($permissionsTable, $rolesTable, $pivotRole, $pivotPermission): void {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($permissionsTable)
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($rolesTable)
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        $cacheStore = config()->string('permission.cache.store');

        resolve(Factory::class)
            ->store($cacheStore !== 'default' ? $cacheStore : null)
            ->forget(config()->string('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config()->string('permission.table_names.role_has_permissions'));
        Schema::dropIfExists(config()->string('permission.table_names.model_has_roles'));
        Schema::dropIfExists(config()->string('permission.table_names.model_has_permissions'));
        Schema::dropIfExists(config()->string('permission.table_names.roles'));
        Schema::dropIfExists(config()->string('permission.table_names.permissions'));
    }
};
