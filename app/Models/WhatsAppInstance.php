<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppInstance extends Model
{
    protected $table = 'whats_app_instances';
    protected $fillable = [
        'user_id',
        'instance_name',
        'token',
        'status',
        'qr_code',
    ];
}
