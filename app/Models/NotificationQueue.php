<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class NotificationQueue extends Model
{
    protected $table = 'notification_queue';
    protected $fillable = [
        'firm_id','notifiable_type','notifiable_id',
        'channel','data','status'
    ];
    public function notifiable() { return $this->morphTo(); }
}
