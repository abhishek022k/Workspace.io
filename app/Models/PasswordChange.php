<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordChange extends Model
{
   /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token', 'user_id','expiry_date'
    ];
    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
      'token'
    ];
}
