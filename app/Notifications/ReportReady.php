<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ReportReady extends Notification
{
    use Queueable;

    public string $filePath;

    public string $fileName;

    public function __construct(string $filePath, string $fileName)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Report Ready',
            'message' => "Your report {$this->fileName} is ready for download.",
            'file_path' => $this->filePath,
            'file_name' => $this->fileName,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Report Ready',
            'message' => "Your report {$this->fileName} is ready for download.",
            'file_path' => $this->filePath,
            'file_name' => $this->fileName,
        ]);
    }
}
