<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionCatalog::all() as $key => $meta) {
            Permission::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $meta['name'],
                    'group' => $meta['group'],
                    'description' => $meta['description'],
                ],
            );
        }
    }
}
