<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentCsvImportService
{
    /**
     * Shortest initial password accepted for an imported student; rows with a shorter or missing password are skipped.
     */
    public const int MIN_PASSWORD_LENGTH = 8;

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
                $password = trim($row[3] ?? '');

                if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)
                    || Str::length($password) < self::MIN_PASSWORD_LENGTH) {
                    $skipped++;

                    continue;
                }

                $exists = User::withTrashed()
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
            report($e);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengimpor data. Tidak ada siswa yang disimpan; periksa isi berkas lalu coba lagi.',
                'imported' => 0,
                'skipped' => 0,
            ];
        }

        $message = "Berhasil mengimpor {$imported} siswa.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati karena duplikasi, data tidak lengkap, atau kata sandi kurang dari ".self::MIN_PASSWORD_LENGTH.' karakter.';
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

            if ($handle === false) {
                throw new RuntimeException('Tidak dapat membuka aliran keluaran untuk templat CSV.');
            }

            fputcsv($handle, ['nama', 'email', 'username', 'password']);
            fputcsv($handle, ['Ahmad Fauzi', 'fauzi@falcon.sch.id', 'siswa01', 'Fauzi#2026']);
            fputcsv($handle, ['Siti Nurhaliza', 'siti@falcon.sch.id', 'siswa02', 'Siti#2026']);
            fclose($handle);
        }, 200, $headers);
    }
}
