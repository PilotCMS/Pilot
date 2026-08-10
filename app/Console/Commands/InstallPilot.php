<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SpaceSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class InstallPilot extends Command
{
    protected $signature = 'pilot:install {--force : Run migrations in production without confirmation}';

    protected $description = 'Install Pilot and create the first administrator account';

    public function handle(): int
    {
        $this->components->info('Installing Pilot');

        if ($this->call('migrate', ['--force' => (bool) $this->option('force')]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $admin = User::role('Admin')->oldest()->first();

        if ($admin === null) {
            if (! $this->input->isInteractive()) {
                $this->components->error('Pilot requires an interactive terminal to create the first administrator account.');

                return self::FAILURE;
            }

            $admin = $this->createAdministrator();
        } else {
            $this->components->warn("Pilot already has an administrator ({$admin->email}); account creation was skipped.");
        }

        DB::transaction(function () use ($admin): void {
            app(SpaceSeeder::class)->run($admin);
        });

        $this->newLine();
        $this->components->info("Pilot is ready. Sign in with {$admin->email}.");

        return self::SUCCESS;
    }

    private function createAdministrator(): User
    {
        $this->components->info('Create your administrator account');

        $name = $this->validatedAnswer(
            'Administrator name',
            'name',
            ['required', 'string', 'max:255'],
        );
        $email = strtolower($this->validatedAnswer(
            'Email address',
            'email',
            ['required', 'email', 'max:255', 'unique:users,email'],
        ));
        $password = $this->validatedPassword();

        return DB::transaction(function () use ($name, $email, $password): User {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $admin->forceFill(['email_verified_at' => now()])->save();
            $admin->assignRole('Admin');

            return $admin;
        });
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function validatedAnswer(string $question, string $field, array $rules): string
    {
        while (true) {
            $answer = trim((string) $this->ask($question));
            $validator = Validator::make([$field => $answer], [$field => $rules]);

            if ($validator->passes()) {
                return $answer;
            }

            $this->components->error($validator->errors()->first($field));
        }
    }

    private function validatedPassword(): string
    {
        while (true) {
            $password = (string) $this->secret('Password');
            $confirmation = (string) $this->secret('Confirm password');
            $validator = Validator::make(
                ['password' => $password, 'password_confirmation' => $confirmation],
                ['password' => ['required', 'string', Password::defaults(), 'confirmed']],
            );

            if ($validator->passes()) {
                return $password;
            }

            $this->components->error($validator->errors()->first('password'));
        }
    }
}
