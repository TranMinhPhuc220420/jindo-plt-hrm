<?php

namespace App\Jobs;

use App\Events\ReportExportReady;
use App\Models\ReportExport;
use App\Services\Report\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $exportId)
    {
        $this->onQueue('reports');
    }

    public function handle(ReportService $reports): void
    {
        $export = ReportExport::query()->with('user.roles.permissions')->find($this->exportId);

        if ($export === null || $export->user === null) {
            return;
        }

        try {
            $rows = $reports->generate($export->report, $export->filters ?? [], $export->user);
            $csv = $this->toCsv($rows);

            $path = 'exports/report-'.$export->id.'.csv';
            Storage::disk('local')->put($path, $csv);

            $export->status = 'ready';
            $export->path = $path;
            $export->error_message = null;
            $export->save();

            ReportExportReady::dispatch($export);
        } catch (\Throwable $e) {
            $export->status = 'failed';
            $export->error_message = $e->getMessage();
            $export->save();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function toCsv(array $rows): string
    {
        if ($rows === []) {
            return "\n";
        }

        $headers = array_keys($rows[0]);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open temporary stream for CSV export.');
        }
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $ordered = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? null;
                $ordered[] = is_array($value) ? json_encode($value) : $value;
            }
            fputcsv($handle, $ordered);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
