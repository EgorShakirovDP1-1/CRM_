<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CustomerTransferService
{
    private const COLUMNS = ['display_name', 'type', 'status', 'preferred_language', 'risk_level'];

    public function __construct(private readonly AuditService $audit) {}

    public function import(UploadedFile $file): int
    {
        $rows = $this->readRows($file);

        return DB::transaction(function () use ($rows): int {
            foreach ($rows as $row) {
                Customer::query()->create($row);
            }

            $this->audit->record('customers.imported', Customer::class, ['count' => count($rows)]);

            return count($rows);
        }, 3);
    }

    public function exportCsv(): string
    {
        $directory = storage_path('app/private/exports');
        File::ensureDirectoryExists($directory);
        $path = tempnam($directory, 'customers-');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary CSV export.');
        }
        $stream = fopen($path, 'wb');
        if ($stream === false) {
            File::delete($path);
            throw new RuntimeException('Unable to open the CSV output stream.');
        }

        fputcsv($stream, self::COLUMNS, ',', '"', '');
        foreach (Customer::query()->orderBy('display_name')->cursor() as $customer) {
            fputcsv($stream, [
                $customer->display_name,
                $customer->type,
                $customer->status,
                $customer->preferred_language,
                $customer->risk_level,
            ], ',', '"', '');
        }
        fclose($stream);

        $this->audit->record('customers.exported', Customer::class);

        return $path;
    }

    /** @return list<array<string, mixed>> */
    private function readRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded CSV file cannot be read.']);
        }
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded CSV file cannot be opened.']);
        }

        try {
            $sample = fgets($stream);
            $delimiter = is_string($sample) && substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
            rewind($stream);
            $rawHeader = fgetcsv($stream, null, $delimiter, '"', '');
            if ($rawHeader === false) {
                throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
            }
            $header = array_map(
                static fn (mixed $value): string => mb_strtolower(trim(ltrim(is_string($value) ? $value : '', "\xEF\xBB\xBF"))),
                $rawHeader,
            );
            if (! in_array('display_name', $header, true)) {
                throw ValidationException::withMessages(['file' => 'CSV header must contain display_name.']);
            }

            $rows = [];
            $errors = [];
            $line = 1;
            while (($rawRow = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
                $line++;
                if (count($rows) >= 5000) {
                    throw ValidationException::withMessages(['file' => 'One import is limited to 5,000 customers.']);
                }
                if (count(array_filter($rawRow, static fn (mixed $value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $row = ['type' => 'person', 'status' => 'active', 'preferred_language' => 'ru'];
                foreach (self::COLUMNS as $column) {
                    $index = array_search($column, $header, true);
                    if ($index !== false && isset($rawRow[$index]) && trim((string) $rawRow[$index]) !== '') {
                        $row[$column] = trim((string) $rawRow[$index]);
                    }
                }
                $validator = Validator::make($row, [
                    'display_name' => ['required', 'string', 'max:255'],
                    'type' => ['required', Rule::in(['person', 'company', 'sole_trader'])],
                    'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
                    'preferred_language' => ['required', Rule::in(['ru', 'en'])],
                    'risk_level' => ['nullable', Rule::in(['low', 'medium', 'high'])],
                ]);
                if ($validator->fails()) {
                    $errors['file.'.$line] = $validator->errors()->all();

                    continue;
                }
                $rows[] = $validator->validated();
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'The CSV file contains no customer rows.']);
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }
}
