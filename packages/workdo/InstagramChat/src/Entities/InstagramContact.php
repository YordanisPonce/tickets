<?php

namespace Workdo\InstagramChat\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstagramContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'name',
        'profile_image',
        'user_name',
        'sender_id',
        'created_by'
    ];
}
