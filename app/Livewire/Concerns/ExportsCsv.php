<?php

namespace App\Livewire\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a CSV built from an already-scoped, already-filtered dataset.
 * Callers are responsible for their own StaffScope/query scoping - this
 * trait only turns rows into a downloadable file, the same streamDownload
 * idiom already used by Units::downloadTemplate() and
 * ImportData::downloadTemplate().
 */
trait ExportsCsv
{
    protected function streamCsv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        $csv = implode(',', array_map([$this, 'csvField'], $header)) . "\r\n";

        foreach ($rows as $row) {
            $csv .= implode(',', array_map([$this, 'csvField'], $row)) . "\r\n";
        }

        return response()->streamDownload(fn () => print($csv), $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function csvField($value): string
    {
        $value = (string) ($value ?? '');

        if (preg_match('/[",\r\n]/', $value)) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
