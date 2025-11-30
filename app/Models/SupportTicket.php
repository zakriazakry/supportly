<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'name',
        'email',
        'issueType',
        'priority',
        'subject',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
