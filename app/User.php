<?php

namespace App;

use App\Events\UserCreated;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'country_code', 'phone', 'use_sms_verify'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => UserCreated::class
    ];

    public function userRegistrationsLog()
    {
        return $this->hasOne(UserRegistrationLog::class, 'user_id');
    }


    public function tokens()
    {
        return $this->hasMany(Token::class);
    }
    public function getPhoneNumber()
    {
        return $this->country_code.$this->phone;
    }

}
