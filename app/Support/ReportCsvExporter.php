<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportCsvExporter
{
    /** @param array<string, mixed> $result */
    public function download(array $result, string $filename): StreamedResponse
    {
        $columns = $result['definition']['columns'];
        $rows = $result['rows'];

        return response()->streamDownload(function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                throw new \RuntimeException('امکان ساخت خروجی CSV وجود ندارد.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_map(static fn (array $column): string => $column['label'], $columns));

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn (array $column): string => $this->safeCell($row[$column['key']] ?? '', $column['type']),
                    $columns,
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function safeCell(mixed $value, string $type): string
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        if ($type === 'number' || $type === 'money') {
            return $value;
        }

        return in_array($value[0] ?? '', ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }
}
