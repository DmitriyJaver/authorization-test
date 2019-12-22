<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRegistationLog extends Model
{
    protected $guarded = [];

    public function registeredUser()
    {
        return $this->belongsTo(User::class);
    }
}
