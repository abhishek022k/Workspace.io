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
        'token', 'email'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
      'created_at' , 'updated_at', 'token'
    ];
}
