<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Membuat akun admin';

    public function handle()
    {
        $email = $this->ask('Email admin');
        $password = $this->secret('Password admin');

        if (User::where('email', $email)->exists()) {
            $this->error('Email sudah terdaftar.');

            return Command::FAILURE;
        }

        User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);        

        $this->info('Akun admin berhasil dibuat.');

        return Command::SUCCESS;
    }
}
