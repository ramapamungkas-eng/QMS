<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ReportReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyReportReady implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    protected string $filePath;

    protected string $fileName;

    public function __construct(User $user, string $filePath, string $fileName)
    {
        $this->user = $user;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function handle(): void
    {
        $this->user->notify(new ReportReady($this->filePath, $this->fileName));
    }
}
