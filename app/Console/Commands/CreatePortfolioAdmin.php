<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreatePortfolioAdmin extends Command
{
    protected $signature = 'portfolio:admin';

    protected $description = 'Create a portfolio administrator interactively, without default credentials';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');
        if (User::where('email', $email)->exists()) {
            $this->error('An account with this email already exists. No access changes were made.');

            return self::FAILURE;
        }
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->is_admin = true;
        $user->save();
        $this->info('Administrator created. Sign in at /admin.');

        return self::SUCCESS;
    }
}
