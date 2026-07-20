<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function documentUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'doc');
}

function docHeaders(): array
{
    return array_merge(spaJsonHeaders(), ['Accept' => 'application/json']);
}

test('cannot list documents without permission', function () {
    Company::factory()->create();
    $user = documentUser([]);

    $this->actingAs($user)
        ->withHeaders(docHeaders())
        ->getJson('/api/documents')
        ->assertForbidden();
});

test('uploading a company document is audited', function () {
    Storage::fake('local');
    Company::factory()->create();
    $user = documentUser(['can_view_company_documents', 'can_manage_company_documents']);

    $response = $this->actingAs($user)
        ->withHeaders(docHeaders())
        ->post('/api/documents', [
            'file' => UploadedFile::fake()->create('policy.pdf', 120, 'application/pdf'),
            'owner_type' => 'company',
            'category' => 'policy',
            'title' => 'Company policy',
        ])
        ->assertCreated()
        ->assertJsonPath('data.owner_type', 'company')
        ->assertJsonPath('data.category', 'policy');

    $id = $response->json('data.id');
    $document = Document::query()->find($id);
    Storage::disk('local')->assertExists($document->file_path);

    expect(AuditLog::query()->where('action', 'document.uploaded')->count())->toBe(1);
});

test('cannot upload company document without manage permission', function () {
    Storage::fake('local');
    Company::factory()->create();
    $user = documentUser(['can_view_company_documents']);

    $this->actingAs($user)
        ->withHeaders(docHeaders())
        ->post('/api/documents', [
            'file' => UploadedFile::fake()->create('policy.pdf', 120, 'application/pdf'),
            'owner_type' => 'company',
            'category' => 'policy',
        ])
        ->assertForbidden();
});

test('employee can upload own document and download it', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = documentUser(['can_upload_own_documents']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $id = $this->actingAs($user->fresh('roles.permissions'))
        ->withHeaders(docHeaders())
        ->post('/api/documents', [
            'file' => UploadedFile::fake()->create('certificate.pdf', 80, 'application/pdf'),
            'owner_type' => 'employee',
            'owner_id' => $employee->id,
            'category' => 'certificate',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user->fresh('roles.permissions'))
        ->withHeaders(docHeaders())
        ->get('/api/documents/'.$id.'/download')
        ->assertOk();
});

test('another employee cannot download a private employee document', function () {
    Storage::fake('local');
    $company = Company::factory()->create();

    $owner = documentUser(['can_upload_own_documents']);
    $ownerEmployee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $owner->id,
        'status' => 'active',
    ]);

    $id = $this->actingAs($owner->fresh('roles.permissions'))
        ->withHeaders(docHeaders())
        ->post('/api/documents', [
            'file' => UploadedFile::fake()->create('salary.pdf', 40, 'application/pdf'),
            'owner_type' => 'employee',
            'owner_id' => $ownerEmployee->id,
            'category' => 'contract',
        ])
        ->assertCreated()
        ->json('data.id');

    $intruder = documentUser(['can_upload_own_documents']);
    Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $intruder->id,
        'status' => 'active',
    ]);

    $this->actingAs($intruder->fresh('roles.permissions'))
        ->withHeaders(docHeaders())
        ->get('/api/documents/'.$id.'/download')
        ->assertForbidden();
});

test('deleting a document soft-deletes it', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = documentUser(['can_manage_company_documents']);
    $document = Document::factory()->create([
        'company_id' => $company->id,
        'owner_type' => 'company',
        'owner_id' => null,
    ]);

    $this->actingAs($user)
        ->withHeaders(docHeaders())
        ->deleteJson('/api/documents/'.$document->id)
        ->assertOk();

    expect(Document::query()->find($document->id))->toBeNull();
    expect(Document::withTrashed()->find($document->id))->not->toBeNull();
});
