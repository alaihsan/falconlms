<?php

namespace App\Console\Commands;

use App\Actions\Users\IssueTemporaryPassword;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'falcon:reset-password
        {username : Login name of the account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give an account a new temporary password';

    public function handle(IssueTemporaryPassword $issueTemporaryPassword): int
    {
        /** @var string $username */
        $username = $this->argument('username');

        $user = User::where('username', Str::lower(trim($username)))->first();

        if (! $user) {
            $this->components->error('No account uses that username.');

            return self::FAILURE;
        }

        $password = $issueTemporaryPassword->handle($user);

        $this->components->info('Password reset. The user must choose a new password at next login.');
        $this->table(['Username', 'Temporary password'], [[$user->username, $password]]);

        return self::SUCCESS;
    }
}
