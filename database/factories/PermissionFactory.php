<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = 'can_'.fake()->unique()->slug(2);

        return [
            'key' => $key,
            'name' => fake()->words(3, true),
            'group' => 'custom',
            'description' => fake()->sentence(),
        ];
    }
}
