<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates accounts from a CSV exported from Excel or Dapodik.
 *
 * The header row names the columns: username (or nis), name (or nama) and
 * optionally role (or peran). Both comma and semicolon separated files
 * work, because Excel uses semicolons with Indonesian regional settings.
 */
class ImportUsers
{
    public const int MAX_ROWS = 2000;

    private const array ROLE_ALIASES = [
        'siswa' => Role::Student,
        'student' => Role::Student,
        'guru' => Role::Teacher,
        'teacher' => Role::Teacher,
        'admin' => Role::Admin,
        'administrator' => Role::Admin,
        'orang tua' => Role::Guardian,
        'guardian' => Role::Guardian,
    ];

    public function __construct(private IssueTemporaryPassword $issueTemporaryPassword) {}

    /**
     * @return array{created: list<array{username: string, name: string, role: string, password: string}>, errors: list<array{line: int, message: string}>}
     */
    public function handle(string $contents, Role $defaultRole): array
    {
        $contents = (string) preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        $result = ['created' => [], 'errors' => []];

        $headerLine = array_shift($lines);

        if ($headerLine === null || trim($headerLine) === '') {
            $result['errors'][] = ['line' => 1, 'message' => (string) __('The file is empty.')];

            return $result;
        }

        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $header = array_map(fn ($column) => Str::lower(trim((string) $column)), str_getcsv($headerLine, $delimiter, '"', ''));
        $columns = [
            'username' => $this->columnIndex($header, ['username', 'nis', 'nisn', 'nip']),
            'name' => $this->columnIndex($header, ['name', 'nama']),
            'role' => $this->columnIndex($header, ['role', 'peran']),
        ];

        if ($columns['username'] === null || $columns['name'] === null) {
            $result['errors'][] = ['line' => 1, 'message' => (string) __('The first row must name the columns "username" and "nama".')];

            return $result;
        }

        if (count($lines) > self::MAX_ROWS) {
            $result['errors'][] = ['line' => 1, 'message' => (string) __('A file can hold at most :max accounts.', ['max' => self::MAX_ROWS])];

            return $result;
        }

        $seen = [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 2;

            if (trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line, $delimiter, '"', '');
            $username = Str::lower(trim((string) ($row[$columns['username']] ?? '')));
            $name = trim((string) ($row[$columns['name']] ?? ''));
            $roleText = $columns['role'] !== null ? Str::lower(trim((string) ($row[$columns['role']] ?? ''))) : '';
            $role = $roleText === '' ? $defaultRole : (self::ROLE_ALIASES[$roleText] ?? null);

            $error = match (true) {
                $username === '' || $name === '' => __('Username and name are required.'),
                ! preg_match('/^[a-z0-9._-]{1,50}$/', $username) => __('Usernames may only contain letters, digits, dots, dashes and underscores.'),
                mb_strlen($name) > 255 => __('The name is too long.'),
                $role === null => __('Unknown role ":role".', ['role' => $roleText]),
                isset($seen[$username]) => __('The username :username appears twice in the file.', ['username' => $username]),
                User::query()->where('username', $username)->exists() => __('The username :username is already taken.', ['username' => $username]),
                default => null,
            };

            if ($error !== null) {
                $result['errors'][] = ['line' => $lineNumber, 'message' => is_string($error) ? $error : implode(' ', $error)];

                continue;
            }

            $seen[$username] = true;

            /** @var Role $role */
            $user = (new User)->forceFill(['username' => $username, 'name' => $name, 'role' => $role]);
            $password = $this->issueTemporaryPassword->handle($user);

            $result['created'][] = ['username' => $username, 'name' => $name, 'role' => $role->label(), 'password' => $password];
        }

        return $result;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $names
     */
    private function columnIndex(array $header, array $names): ?int
    {
        foreach ($names as $name) {
            $index = array_search($name, $header, true);

            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }
}
