<?php
namespace App\Http\Controllers\V1;

use App\Models\PhoneWipeUsers;
use App\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WipeUserController
{

    /**
     * Login auth.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function auth(Request $request): \Illuminate\Http\JsonResponse
    {

        $wiperUser = PhoneWipeUsers::where(
            [
                'username' => Hash::make($request->input('username')),
                'password' => Hash::make($request->input('password'))
            ]
        )->get();

        if($wiperUser === null) {
            return response()->json([], 400);
        }

        return response()->json($wiperUser, 200);
    }

    /**
     * Will create a wipe user into DB.
     * The auth_token should only be stored into the phone-device.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request): \Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function findbytoken(Request $request): \Illuminate\Http\JsonResponse
    {
        $wiperUser = PhoneWipeUsers::where('auth_token', $request->input('auth_token'))->first();
        return response()->json($wiperUser, 200);
    }

    public function patch(Request $request): \Illuminate\Http\JsonResponse
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
