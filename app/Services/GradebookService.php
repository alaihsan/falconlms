<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradebookService
{
    /**
     * Get the graded learning items shared to the class, oldest first.
     *
     * @return Collection<int, LearningItem>
     */
    public function gradedItems(SchoolClass $schoolClass): Collection
    {
        return $schoolClass->learningItems()
            ->where('is_graded', true)
            ->orderBy('learning_items.created_at')
            ->orderBy('learning_items.id')
            ->get();
    }

    /**
     * Get the students enrolled in the class, ordered by name.
     *
     * @return Collection<int, User>
     */
    public function students(SchoolClass $schoolClass): Collection
    {
        return $schoolClass->students()->orderBy('name')->orderBy('users.id')->get();
    }

    /**
     * Get every recorded score of the class keyed by "{siswaId}:{learningItemId}".
     *
     * @param  Collection<int, User>  $students
     * @param  Collection<int, LearningItem>  $items
     * @return array<string, int>
     */
    public function scores(Collection $students, Collection $items): array
    {
        return AssignmentSubmission::query()
            ->whereIn('siswa_id', $students->modelKeys())
            ->whereIn('learning_item_id', $items->modelKeys())
            ->whereNotNull('score')
            ->get(['siswa_id', 'learning_item_id', 'score'])
            ->mapWithKeys(fn (AssignmentSubmission $submission): array => [
                "{$submission->siswa_id}:{$submission->learning_item_id}" => (int) $submission->score,
            ])
            ->all();
    }

    /**
     * Save a batch of scores for the class, rejecting the whole batch when any entry is invalid.
     *
     * @param  array<int, array{learning_item_id: int, siswa_id: int, score: int|null}>  $entries
     * @return int Number of cells that changed.
     *
     * @throws ValidationException
     */
    public function saveScores(SchoolClass $schoolClass, array $entries): int
    {
        $items = $this->gradedItems($schoolClass)->keyBy('id');
        $studentIds = $this->students($schoolClass)->modelKeys();

        foreach ($entries as $entry) {
            $problem = $this->entryProblem($entry, $items, $studentIds);

            if ($problem !== null) {
                throw ValidationException::withMessages(['scores' => $problem]);
            }
        }

        return $this->persist($entries);
    }

    /**
     * Stream the class gradebook as a CSV file that Excel can open and that can be re-imported.
     */
    public function export(SchoolClass $schoolClass): StreamedResponse
    {
        $items = $this->gradedItems($schoolClass);
        $students = $this->students($schoolClass);
        $scores = $this->scores($students, $items);
        $filename = 'buku-nilai-'.Str::slug("{$schoolClass->name} {$schoolClass->subject}").'.csv';

        return response()->streamDownload(function () use ($items, $students, $scores): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\u{FEFF}");

            fputcsv($handle, [
                'Username',
                'Nama',
                ...$items->map(fn (LearningItem $item): string => $this->columnHeading($item))->all(),
            ]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->username ?? $student->email,
                    $student->name,
                    ...$items->map(fn (LearningItem $item): string => (string) ($scores["{$student->id}:{$item->id}"] ?? ''))->all(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Import scores from a CSV file in the exported layout. Invalid cells are skipped and reported.
     *
     * @return array{success: bool, message: string}
     */
    public function import(SchoolClass $schoolClass, UploadedFile $file): array
    {
        $rows = $this->readCsv($file);

        if (count($rows) < 2) {
            return ['success' => false, 'message' => 'Berkas CSV kosong atau tidak memiliki baris siswa.'];
        }

        $items = $this->gradedItems($schoolClass)->keyBy('id');
        $students = $this->students($schoolClass);
        $studentIds = $students->modelKeys();

        $columns = [];
        foreach (array_slice($rows[0], 2, null, true) as $index => $heading) {
            if (preg_match('/\[#(\d+)\]\s*$/', (string) $heading, $matches) === 1 && $items->has((int) $matches[1])) {
                $columns[$index] = (int) $matches[1];
            }
        }

        if ($columns === []) {
            return [
                'success' => false,
                'message' => 'Kolom nilai tidak dikenali. Gunakan berkas hasil ekspor buku nilai kelas ini sebagai templat.',
            ];
        }

        $studentsByIdentifier = [];
        foreach ($students as $student) {
            foreach ([$student->username, $student->email] as $identifier) {
                if ($identifier !== null && $identifier !== '') {
                    $studentsByIdentifier[Str::lower($identifier)] = $student->id;
                }
            }
        }

        $entries = [];
        $unknownStudents = 0;
        $invalidCells = 0;

        foreach (array_slice($rows, 1) as $row) {
            $studentId = $studentsByIdentifier[Str::lower(trim((string) ($row[0] ?? '')))] ?? null;

            if ($studentId === null) {
                $unknownStudents++;

                continue;
            }

            foreach ($columns as $index => $itemId) {
                $cell = trim((string) ($row[$index] ?? ''));

                if ($cell === '') {
                    continue;
                }

                $entry = [
                    'learning_item_id' => $itemId,
                    'siswa_id' => $studentId,
                    'score' => ctype_digit($cell) ? (int) $cell : -1,
                ];

                if ($this->entryProblem($entry, $items, $studentIds) !== null) {
                    $invalidCells++;

                    continue;
                }

                $entries[] = $entry;
            }
        }

        $changed = $this->persist($entries);

        $message = "Impor selesai: {$changed} nilai diperbarui.";
        if ($unknownStudents > 0) {
            $message .= " {$unknownStudents} baris dilewati karena siswa tidak terdaftar di kelas ini.";
        }
        if ($invalidCells > 0) {
            $message .= " {$invalidCells} nilai dilewati karena bukan angka bulat atau di luar rentang.";
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * Build the CSV column heading for an item; the trailing "[#id]" identifies it on import.
     */
    public function columnHeading(LearningItem $item): string
    {
        return "{$item->title} (maks {$item->max_score}) [#{$item->id}]";
    }

    /**
     * Explain why a score entry cannot be saved, or return null when it is valid.
     *
     * @param  array{learning_item_id: int, siswa_id: int, score: int|null}  $entry
     * @param  SupportCollection<int, LearningItem>  $items
     * @param  array<int, int>  $studentIds
     */
    private function entryProblem(array $entry, SupportCollection $items, array $studentIds): ?string
    {
        $item = $items->get($entry['learning_item_id']);

        if ($item === null || ! in_array($entry['siswa_id'], $studentIds, true)) {
            return 'Ada nilai untuk siswa atau konten yang tidak termasuk buku nilai kelas ini.';
        }

        if ($entry['score'] !== null && ($entry['score'] < 0 || $entry['score'] > $item->max_score)) {
            return "Nilai untuk \"{$item->title}\" harus antara 0 dan {$item->max_score}.";
        }

        return null;
    }

    /**
     * Write validated score entries. Clearing a score removes rows that hold no student answer.
     *
     * @param  array<int, array{learning_item_id: int, siswa_id: int, score: int|null}>  $entries
     */
    private function persist(array $entries): int
    {
        if ($entries === []) {
            return 0;
        }

        return DB::transaction(function () use ($entries): int {
            $existing = AssignmentSubmission::query()
                ->whereIn('learning_item_id', array_unique(array_column($entries, 'learning_item_id')))
                ->whereIn('siswa_id', array_unique(array_column($entries, 'siswa_id')))
                ->get()
                ->keyBy(fn (AssignmentSubmission $submission): string => "{$submission->siswa_id}:{$submission->learning_item_id}");

            $changed = 0;

            foreach ($entries as $entry) {
                $submission = $existing->get("{$entry['siswa_id']}:{$entry['learning_item_id']}");

                if ($submission?->score === $entry['score']) {
                    continue;
                }

                if ($entry['score'] === null) {
                    if ($submission->submitted_at === null) {
                        $submission->delete();
                    } else {
                        $submission->update(['score' => null, 'graded_at' => null]);
                    }
                } elseif ($submission === null) {
                    AssignmentSubmission::create([
                        'learning_item_id' => $entry['learning_item_id'],
                        'siswa_id' => $entry['siswa_id'],
                        'score' => $entry['score'],
                        'graded_at' => now(),
                    ]);
                } else {
                    $submission->update(['score' => $entry['score'], 'graded_at' => now()]);
                }

                $changed++;
            }

            return $changed;
        });
    }

    /**
     * Read a CSV file into rows, tolerating a UTF-8 BOM and comma, semicolon or tab delimiters.
     *
     * @return array<int, array<int, string|null>>
     */
    private function readCsv(UploadedFile $file): array
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $contents = preg_replace('/^\x{FEFF}/u', '', $contents) ?? $contents;
        $firstLine = strtok($contents, "\r\n") ?: '';

        $delimiter = collect([',', ';', "\t"])
            ->sortByDesc(fn (string $candidate): int => substr_count($firstLine, $candidate))
            ->first();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            if (array_filter($row, static fn (?string $value): bool => $value !== null && trim($value) !== '') !== []) {
                $rows[] = $row;
            }
        }
        fclose($stream);

        return $rows;
    }
}
