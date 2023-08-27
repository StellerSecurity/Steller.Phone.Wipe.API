<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;

class PhoneWipeUsers extends Model
{

    use HasUuids;

    protected $table = "phone_wipe_users";

    protected $fillable = ['username', 'password', 'auth_token', 'status'];

}
