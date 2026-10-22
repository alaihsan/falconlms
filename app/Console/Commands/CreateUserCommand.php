<?php

namespace App\Console\Commands;

use App\Actions\Users\IssueTemporaryPassword;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'falcon:create-user
        {username : Login name, for students usually their NIS}
        {name : Full name}
        {--role=student : admin, teacher, student or guardian}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an account with a temporary password';

    public function handle(IssueTemporaryPassword $issueTemporaryPassword): int
    {
        /** @var string $username */
        $username = $this->argument('username');

        $validator = Validator::make([
            'username' => Str::lower(trim($username)),
            'name' => $this->argument('name'),
            'role' => $this->option('role'),
        ], [
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();

        $user = (new User)->forceFill([
            'username' => $data['username'],
            'name' => $data['name'],
            'role' => Role::from($data['role']),
        ]);

        // Saves the new account together with its temporary password.
        $password = $issueTemporaryPassword->handle($user);

        $this->components->info('Account created. The user must choose a new password at first login.');
        $this->table(['Username', 'Temporary password'], [[$user->username, $password]]);

        return self::SUCCESS;
    }
}
