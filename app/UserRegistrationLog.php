<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRegistrationLog extends Model
{
    protected $fillable = ['user_id'];

    public function registeredUser()
    {
        return $this->belongsTo(User::class);
    }
}
