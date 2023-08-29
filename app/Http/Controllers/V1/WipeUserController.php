<?php
namespace App\Http\Controllers\V1;

use App\Models\PhoneWipeUsers;
use App\Status;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WipeUserController
{

    /**
     * Login auth.
     * @param Request $request
     * @return JsonResponse
     */
    public function auth(Request $request): JsonResponse
    {

        $wiperUser = PhoneWipeUsers::where(['username' => $request->input('username')])->first();

        if($wiperUser === null) {
            return response()->json([], 400);
        }

        $password = $request->input('password');

        // wrong login.
        if(!Hash::check($password, $wiperUser->password)) {
            return response()->json([], 400);
        }

        // needsRehash, update in DB.
        if (Hash::needsRehash($wiperUser->password)) {
            $wiperUser->password = Hash::make($password);
            $wiperUser->save();
        }

        return response()->json($wiperUser, 200);
    }

    /**
     * Will create a wipe user into DB.
     * The auth_token should only be stored into the phone-device.
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {

        $secret_key = $request->input('secret_key');

        $wipeUser = new PhoneWipeUsers(
            [
                'username' => $request->input('username'),
                'password' => Hash::make($request->input('password')),
                'auth_token' => $request->input('auth_token'),
                'secret_key' => Hash::make($secret_key),
                'key_helper' => "",
                'status' => Status::ACTIVE
            ]
        );
        $wipeUser->save();

        return response()->json($wipeUser, 200);
    }

    /**
     * Finds
     * @param Request $request
     * @return JsonResponse
     */
    public function findbytoken(Request $request): JsonResponse
    {
        $wiperUser = PhoneWipeUsers::where('auth_token', $request->input('auth_token'))->first();
        return response()->json($wiperUser, 200);
    }

    public function findbysecretkey(Request $request) : JsonResponse {
        $secret_key = $request->input('secret_key');
        $wiperUsers = PhoneWipeUsers::all();
        $response = [];
        foreach($wiperUsers as $wiperUser) {
        if(Hash::check($secret_key, $wiperUser->secret_key)) {
                // needsRehash, update in DB.
                if (Hash::needsRehash($wiperUser->password)) {
                    $wiperUser->secret_key = Hash::make($secret_key);
                    $wiperUser->save();
                }
            }
            $response = $wiperUser;
            break;
        }

        return response()->json($response, 200);
    }

    public function patch(Request $request): JsonResponse
    {

        $wiperUser = PhoneWipeUsers::where('id', $request->input('id'))->first();

        if($wiperUser === null) {
            return response()->json([], 400);
        }

        $wiperUser->fill($request->only('status'));

        if($request->input('username') !== null) {
            $wiperUser->username = $request->input('username');
        }

        if($request->input('password') !== null) {
            $wiperUser->password = $request->input('password');
        }

        $wiperUser->save();
        return response()->json($wiperUser, 200);

    }

}
