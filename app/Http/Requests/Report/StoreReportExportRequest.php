<?php

namespace App\Http\Requests\Report;

use App\Services\Report\ReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_export_reports') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report' => ['required', 'string', Rule::in(ReportService::availableReports())],
            'format' => ['sometimes', 'string', Rule::in(['csv'])],
            'filters' => ['sometimes', 'array'],
        ];
    }
}
