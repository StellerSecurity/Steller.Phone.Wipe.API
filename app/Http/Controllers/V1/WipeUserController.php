<?php
namespace App\Http\Controllers\V1;

use App\Models\PhoneWipeUsers;
use App\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WipeUserController
{

    /**
     * Login auth.
     * @param Request $request
     * @return JsonResponse
     */
    public function auth(Request $request): JsonResponse
    {

        $wiperUser = PhoneWipeUsers::where(
            [
                'username' => $request->input('username')
            ]
        )->first();

        if($wiperUser === null) {
            return response()->json([], 400);
        }

        // wrong login.
        if(!Hash::check($request->input('password'), $wiperUser->password)) {
            return response()->json([], 400);
        }

        // needsRehash, update in DB.
        if (Hash::needsRehash($wiperUser->password)) {
            $wiperUser->password = Hash::make($request->input('password'));
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

        $wipeUser = new PhoneWipeUsers(
            [
                'username' => Hash::make($request->input('username')),
                'password' => Hash::make($request->input('password')),
                'auth_token' => $request->input('auth_token'),
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

    public function patch(Request $request): JsonResponse
    {
        $wiperUser = PhoneWipeUsers::where('auth_token', $request->input('auth_token'))->first();
        if($wiperUser === null) {
            return response()->json([], 400);
        }
        $wiperUser->fill($request->only('status'));
        $wiperUser->save();
        return response()->json($wiperUser, 200);

    }

}
