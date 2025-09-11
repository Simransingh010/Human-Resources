<?php

// app/Console/Commands/ProcessNotificationQueue.php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Models\NotificationQueue;
use App\Notifications\GenericNotification;
use App\Helpers\TenantMailer;

class ProcessNotificationQueue extends Command
{
    protected $signature = 'notifications:process {--limit=100}';
    protected $description = 'Dispatch staged notifications';

    public function handle()
    {
        NotificationQueue::where('status','pending')
            ->limit($this->option('limit'))
            ->get()
            ->each(function($item){
                $item->update(['status'=>'processing']);
                try {
                    TenantMailer::configure($item->firm_id);

                    $notifiable = $item->notifiable;
                    $payload    = json_decode($item->data,true);

                    Notification::send($notifiable,new GenericNotification($payload));

                    $item->update([
                        'status'=>'sent',
                        'processed_at'=>now(),
                    ]);
                } catch (\Throwable $e) {
                    \Log::error($e);
                    $item->update(['status'=>'failed']);
                }
            });
        $this->info('Batch done.');
    }
}

