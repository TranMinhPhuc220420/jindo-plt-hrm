<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeEmergencyContactController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    public function index(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('view', $model);

        return ApiResponse::success(
            $this->employees->listEmergencyContacts($model)->values()->all(),
        );
    }

    public function replace(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $validated = $request->validate([
            'contacts' => ['required', 'array'],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.relationship' => ['nullable', 'string', 'max:100'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],
        ]);

        $contacts = $this->employees->replaceEmergencyContacts(
            $model,
            $validated['contacts'],
        );

        return ApiResponse::success(
            $contacts->values()->all(),
            'Emergency contacts updated.',
        );
    }
}
