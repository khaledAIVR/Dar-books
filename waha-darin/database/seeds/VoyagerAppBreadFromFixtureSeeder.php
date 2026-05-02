<?php

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\DataRow;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;

class VoyagerAppBreadFromFixtureSeeder extends Seeder
{
    /**
     * Imports BREAD metadata from database/fixtures/voyager_app_bread.json (exported from a dev Voyager DB).
     * Safe to run multiple times — skips DataTypes/DataRows that already exist.
     */
    public function run(): void
    {
        $path = database_path('fixtures/voyager_app_bread.json');
        if (! is_readable($path)) {
            return;
        }

        /** @var array<string,array{data_type:array,data_rows:array<int,array>}> $fixtures */
        $fixtures = json_decode((string) file_get_contents($path), true);
        if (! is_array($fixtures)) {
            return;
        }

        foreach ($fixtures as $slug => $payload) {
            if (! is_array($payload) || empty($payload['data_type'])) {
                continue;
            }

            $dtData = $payload['data_type'];
            if (($dtData['slug'] ?? '') !== $slug) {
                continue;
            }

            /** @var \TCG\Voyager\Models\DataType $dt */
            $dt = DataType::firstOrNew(['slug' => $slug]);
            if ($dt->exists) {
                continue;
            }

            $dt->fill($dtData)->save();

            Permission::generateFor($dt->name);

            foreach ($payload['data_rows'] ?? [] as $row) {
                if (empty($row['field'])) {
                    continue;
                }
                $details = $row['details'] ?? null;
                if ($details !== null && ! is_array($details)) {
                    $details = [];
                }

                DataRow::firstOrCreate(
                    ['data_type_id' => $dt->id, 'field' => $row['field']],
                    [
                        'type' => $row['type'] ?? 'text',
                        'display_name' => $row['display_name'] ?? $row['field'],
                        'required' => isset($row['required']) ? (int) $row['required'] : 0,
                        'browse' => isset($row['browse']) ? (int) $row['browse'] : 0,
                        'read' => isset($row['read']) ? (int) $row['read'] : 0,
                        'edit' => isset($row['edit']) ? (int) $row['edit'] : 0,
                        'add' => isset($row['add']) ? (int) $row['add'] : 0,
                        'delete' => isset($row['delete']) ? (int) $row['delete'] : 0,
                        'details' => $details ?? [],
                        'order' => isset($row['order']) ? (int) $row['order'] : 0,
                    ]
                );
            }
        }

        $admin = Role::query()->where('name', 'admin')->first();
        if ($admin !== null) {
            $admin->permissions()->sync(Permission::query()->orderBy('id')->pluck('id')->all());
        }

        $this->seedSidebarMenuLinks();
    }

    private function seedSidebarMenuLinks(): void
    {
        $path = database_path('fixtures/voyager_admin_sidebar_items.json');
        if (! is_readable($path)) {
            return;
        }

        $items = json_decode((string) file_get_contents($path), true);
        if (! is_array($items)) {
            return;
        }

        $menu = Menu::query()->where('name', 'admin')->first();
        if ($menu === null) {
            return;
        }

        foreach ($items as $row) {
            if (empty($row['route'])) {
                continue;
            }

            MenuItem::firstOrCreate(
                ['menu_id' => $menu->id, 'route' => $row['route']],
                [
                    'title' => $row['title'] ?? $row['route'],
                    'url' => '',
                    'target' => $row['target'] ?? '_self',
                    'icon_class' => $row['icon_class'] ?? null,
                    'color' => $row['color'] ?? null,
                    'parent_id' => null,
                    'order' => isset($row['order']) ? (int) $row['order'] : 0,
                ]
            );
        }
    }
}
