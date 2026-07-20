<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'owner_type' => 'company',
            'owner_id' => null,
            'category' => 'other',
            'title' => $this->faker->sentence(3),
            'file_path' => 'documents/'.$this->faker->uuid().'.pdf',
            'original_name' => 'file.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1000, 500000),
            'uploaded_by' => null,
        ];
    }
}
