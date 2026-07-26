<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UploadEmployeeAvatarRequest;
use App\Models\Employee;
use App\Services\Auth\AuthService;
use App\Services\Employee\EmployeeAvatarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeAvatarController extends Controller
{
    public function __construct(
        private readonly EmployeeAvatarService $avatars,
        private readonly AuthService $auth,
    ) {}

    public function store(UploadEmployeeAvatarRequest $request): JsonResponse
    {
        $employee = $this->ownEmployee($request);
        $this->authorize('updateAvatar', $employee);

        $this->avatars->upload($employee, $request->file('avatar'));

        return ApiResponse::success(
            $this->auth->me($request->user()->fresh(['employee'])),
            'Avatar updated.',
        );
    }

    public function destroy(Request $request): JsonResponse
    {
        $employee = $this->ownEmployee($request);
        $this->authorize('updateAvatar', $employee);

        $this->avatars->delete($employee);

        return ApiResponse::success(
            $this->auth->me($request->user()->fresh(['employee'])),
            'Avatar removed.',
        );
    }

    private function ownEmployee(Request $request): Employee
    {
        $user = $request->user();
        $user->loadMissing('employee');

        $employee = $user->employee;
        if ($employee === null) {
            throw new DomainException(
                message: 'No employee profile is linked to this account.',
                errorCode: 'EMPLOYEE_NOT_LINKED',
                status: 422,
            );
        }

        return $employee;
    }
}
