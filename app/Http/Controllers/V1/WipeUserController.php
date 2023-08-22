<?php

namespace App\Http\Controllers\V1;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;

class WipeUserController
{

    public function add(Request $request) {


        // Our custom encryption key:
        $key = 'U2x2QdvosFTtk5nL0ejrKqLFP1tUDtSt';

        $encrypter = new Encrypter(
            key: $key,
            cipher: config('app.cipher'),
        );

        $encryptedValue = $encrypter->encrypt('hello');

    }

}
