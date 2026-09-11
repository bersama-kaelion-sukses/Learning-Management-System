<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-passwords';
    protected $description = 'Reset password user menjadi ID mereka sendiri jika sudah 3 bulan sejak password_changed_at';

    public function handle()
    {
        $users = User::whereNotNull('password_changed_at')
            ->where('password_changed_at', '<=', Carbon::now()->subMonths(3))
            ->get();

        if ($users->isEmpty()) {
            $this->info('Tidak ada user yang perlu di-reset.');
            return Command::SUCCESS;
        }

        foreach ($users as $user) {
            // 🔹 Reset password menjadi ID user
            $user->password = Hash::make($user->emp_id);
            $user->password_changed_at = Carbon::now(); // update waktu reset
            $user->save();
        }

        return Command::SUCCESS;
    }
}
