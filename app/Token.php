<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    const EXPIRATION_TIME = 15;

    protected $fillable = [
      'code',
      'user_id',
      'used'
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($attributes['code'])){
            $attributes['code'] = $this->generateCode();
        }

        parent::__construct($attributes);
    }

    public function generateCode($codeLength = 4)
    {
        /**
         *Static value used for test
         *To generate random code, uncomment the code below and comment out the static assignment.
         */
        /*$min = pow(10, $codeLength);
        $max = $min * 10 - 1;
        $code = mt_rand($min, $max);*/

        $code = 1234;

        return $code;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user ()
    {
        return $this->belongsTo(User::class);
    }

    /**
     *True if the token is not used nor expired
     *
     * @return bool
     */
    public function isValid()
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    /**
     * Is the current token used
     *
     * @return bool
     */
    public function isUsed()
    {
        return $this->used;
    }

    public function isExpired()
    {
        return $this->created_at->diffInMinutes(Carbon::now()) > static::EXPIRATION_TIME;
    }


}
