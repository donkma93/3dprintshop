<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineVisitor extends Model
{
    protected $fillable = ['visitor_token', 'last_seen_at'];

    protected $casts = ['last_seen_at' => 'datetime'];
}
