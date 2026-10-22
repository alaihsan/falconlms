<?php

namespace App\Actions\Users;

use App\Models\User;

class IssueTemporaryPassword
{
    /**
     * Letters and digits that cannot be mistaken for each other when read
     * aloud or copied from a printed slip (no 0/o, 1/l/i).
     */
    private const string LETTERS = 'abcdefghjkmnpqrstuvwxyz';

    private const string DIGITS = '23456789';

    /**
     * Give the user a new random password that must be changed at the next login.
     *
     * Returns the plain password so it can be handed to the user.
     */
    public function handle(User $user): string
    {
        $password = $this->generate();

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
        ])->save();

        return $password;
    }

    /**
     * Generate a password such as "kpqm-4827".
     */
    private function generate(): string
    {
        return $this->randomFrom(self::LETTERS, 4).'-'.$this->randomFrom(self::DIGITS, 4);
    }

    private function randomFrom(string $characters, int $length): string
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $result;
    }
}
