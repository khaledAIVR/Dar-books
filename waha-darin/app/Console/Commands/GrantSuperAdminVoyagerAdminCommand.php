<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use TCG\Voyager\Models\Role;

class GrantSuperAdminVoyagerAdminCommand extends Command
{
    protected $signature = 'superadmin:grant-voyager-admin
        {email? : Voyager targets this user; omit to use SUPER_ADMIN_EMAIL / SUPER_ADMIN_ID config}';

    protected $description = 'Set the Voyager admin role on the configured super-admin user so /admin (browse_admin) works.';

    public function handle(): int
    {
        $emailArg = $this->argument('email');
        $user = is_string($emailArg) && $emailArg !== ''
            ? User::query()->whereRaw('LOWER(email) = ?', [strtolower($emailArg)])->first()
            : $this->resolveSuperAdminUser();

        if ($user === null) {
            $this->error(
                $emailArg
                    ? "No user with email {$emailArg}."
                    : 'No super admin user found. Pass an email argument or set SUPER_ADMIN_EMAIL / SUPER_ADMIN_ID.'
            );

            return 1;
        }

        $role = Role::query()->where('name', 'admin')->orderBy('id')->first();
        if ($role === null) {
            $this->error('No Voyager role named "admin". Run voyager/database seeds.');

            return 1;
        }

        $user->role()->associate($role);
        $user->save();

        $this->info("Granted Voyager \"admin\" role to {$user->email} (user id {$user->id}). /admin login should persist.");

        return 0;
    }

    private function resolveSuperAdminUser(): ?User
    {
        $email = trim((string) config('superadmin.email', ''));
        if ($email !== '') {
            $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($user !== null) {
                return $user;
            }
        }

        $id = (int) config('superadmin.id', 1);

        return User::query()->find($id);
    }
}
