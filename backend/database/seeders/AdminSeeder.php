<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $dominio = config('auth.allowed_domain');
        $email = Str::lower(trim((string) config('auth.admin.email')));
        $password = config('auth.admin.password');

        if (! str_ends_with($email, '@'.$dominio)) {
            throw new RuntimeException("ADMIN_EMAIL debe ser un correo institucional @{$dominio}");
        }

        if (blank($password)) {
            if (app()->isProduction()) {
                throw new RuntimeException('Defina ADMIN_PASSWORD para crear el admin en producción.');
            }
            $password = 'Admin12345*';
        }

        $admin = User::firstOrNew(['email' => $email]);
        $admin->fill([
            'name' => config('auth.admin.name'),
            'password' => $password,
            'programa' => 'Ingeniería de Sistemas',
        ]);
        $admin->role = Role::Admin;
        $admin->email_verified_at ??= now();
        $admin->save();
    }
}
