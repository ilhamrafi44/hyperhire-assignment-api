<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Person, Like};
use Illuminate\Support\Facades\Mail;

class EmailPopularPeople extends Command
{
    protected $signature = 'tinder:email-popular';
    protected $description = 'Email admin when someone has >= 50 likes';

    public function handle(): int
    {
        $popular = Person::withCount('likes')->having('likes_count', '>=', 50)->get();
        if ($popular->isEmpty()) {
            $this->info('No popular people found.');
            return self::SUCCESS;
        }

        $admin = config('mail.admin', env('ADMIN_EMAIL'));
        $lines = $popular->map(fn ($p) => "{$p->name} (ID {$p->id}) - {$p->likes_count} likes")->implode("\n");

        Mail::raw("Popular people:\n\n".$lines, function ($m) use ($admin) {
            $m->to($admin)->subject('Popular People (>=50 likes)');
        });

        $this->info('Email sent to admin.');
        return self::SUCCESS;
    }
}
