<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentCsvImportService
{
    /**
     * Import students from an uploaded CSV file.
     *
     * @return array{success: bool, message: string, imported: int, skipped: int}
     */
    public function import(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            return [
                'success' => false,
                'message' => 'Berkas CSV tidak valid.',
                'imported' => 0,
                'skipped' => 0,
            ];
        }

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [
                'success' => false,
                'message' => 'Gagal membuka berkas CSV.',
                'imported' => 0,
                'skipped' => 0,
            ];
        }

        $imported = 0;
        $skipped = 0;
        $firstRow = true;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty(array_filter($row, static fn (?string $v): bool => $v !== null && trim($v) !== ''))) {
                    continue;
                }

                if ($firstRow) {
                    $firstRow = false;
                    $firstCol = strtolower(trim($row[0] ?? ''));
                    if (in_array($firstCol, ['nama', 'name', 'nama lengkap'], true)) {
                        continue;
                    }
                }

                $name = trim($row[0] ?? '');
                $email = strtolower(trim($row[1] ?? ''));
                $username = isset($row[2]) ? trim($row[2]) : null;
                $password = isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : 'siswa123';

                if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;

                    continue;
                }

                $exists = User::query()
                    ->where('email', $email)
                    ->when($username, fn (Builder $q) => $q->orWhere('username', $username))
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'username' => $username ?: null,
                    'role' => UserRole::Siswa,
                    'password' => Hash::make($password),
                    'must_change_password' => true,
                ]);

                $imported++;
            }

            fclose($handle);
            DB::commit();
        } catch (Throwable $e) {
            fclose($handle);
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengimpor data: '.$e->getMessage(),
                'imported' => 0,
                'skipped' => 0,
            ];
        }

        $message = "Berhasil mengimpor {$imported} siswa.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati karena duplikasi atau data tidak lengkap.";
        }

        return [
            'success' => true,
            'message' => $message,
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Stream the CSV template download response.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_siswa.csv"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['nama', 'email', 'username', 'password']);
            fputcsv($handle, ['Ahmad Fauzi', 'fauzi@falcon.sch.id', 'siswa01', 'siswa123']);
            fputcsv($handle, ['Siti Nurhaliza', 'siti@falcon.sch.id', 'siswa02', 'siswa123']);
            fclose($handle);
        }, 200, $headers);
    }
}
