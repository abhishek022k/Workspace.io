<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifyUser extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'verification_code'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'verification_code'
    ];

}
