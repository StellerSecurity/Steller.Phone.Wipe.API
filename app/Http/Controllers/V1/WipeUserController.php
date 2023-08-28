<?php
namespace App\Http\Controllers\V1;

use App\Models\PhoneWipeUsers;
use App\Status;
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

        if($secret_key === null) {
            $secret_key = Str::random(46);
        }

        $wipeUser = new PhoneWipeUsers(
            [
                'username' => $request->input('username'),
                'password' => Hash::make($request->input('password')),
                'auth_token' => $request->input('auth_token'),
                'secret_key' => Hash::make($secret_key),
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

        $wiperUser = PhoneWipeUsers::where('secret_key', Hash::make($secret_key))->first();

        if($wiperUser !== null) {
            // needsRehash, update in DB.
            if (Hash::needsRehash($wiperUser->password)) {
                $wiperUser->secret_key = Hash::make($secret_key);
                $wiperUser->save();
            }
        }

        return response()->json($wiperUser, 200);

    }

    public function patch(Request $request): JsonResponse
    {
        $wiperUser = PhoneWipeUsers::where('id', $request->input('id'))->first();
        if($wiperUser === null) {
            return response()->json([], 400);
        }
        $wiperUser->fill($request->only('status'));
        $wiperUser->save();
        return response()->json($wiperUser, 200);

    }

}
