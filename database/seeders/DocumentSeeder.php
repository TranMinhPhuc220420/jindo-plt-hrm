<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        $relativePath = 'documents/'.$company->id.'/employee-handbook.txt';
        $contents = "JINDO Employee Handbook (demo)\n\nThis is a sample company policy document seeded for local development.\n";

        Storage::disk('local')->put($relativePath, $contents);

        Document::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'owner_type' => 'company',
                'title' => 'Employee Handbook',
            ],
            [
                'owner_id' => null,
                'category' => 'policy',
                'file_path' => $relativePath,
                'original_name' => 'employee-handbook.txt',
                'mime_type' => 'text/plain',
                'size' => strlen($contents),
                'uploaded_by' => null,
            ],
        );
    }
}
