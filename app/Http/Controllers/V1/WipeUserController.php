<?php
namespace App\Http\Controllers\V1;

use App\Models\PhoneWipeUsers;
use App\Status;
use Carbon\Carbon;
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

        $username = $request->input('username');

        if($username === null) {
            return response()->json([], 400);
        }

        $wiperUser = PhoneWipeUsers::where(['username' => $username])->first();

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

        if($request->input('auth_token') == null ||$request->input('username') == null || $request->input('secret_key') == null
            || $request->input('password') == null || $request->input('subscription_id') == null) {
            return response()->json([], 200);
        }

        $wipeUser = new PhoneWipeUsers(
            [
                'username' => $request->input('username'),
                'password' => Hash::make($request->input('password')),
                'auth_token' => $request->input('auth_token'),
                'secret_key' => Hash::make($secret_key),
                'key_helper' => time(),
                'status' => Status::ACTIVE->value,
                'subscription_id' => $request->input('subscription_id')
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

        if($request->input('auth_token') === null) {
            return response()->json([], 400);
        }

        $wiperUser = PhoneWipeUsers::where('auth_token', $request->input('auth_token'))->first();

        // should be queued.
        if($wiperUser !== null) {
            //$wiperUser->last_call = Carbon::now();
            if($wiperUser->subscription_id === null && $request->input('subscription_id')!== null) {
                $wiperUser->subscription_id = $request->input('subscription_id');
            }
            $wiperUser->save();
        }

        return response()->json($wiperUser, 200);
    }

    public function findbysubscriptionid(Request $request): JsonResponse
    {

        $subscription_id = $request->input('subscription_id');

        if($subscription_id === null) {
            return response()->json();
        }

        $wipe = PhoneWipeUsers::where('subscription_id', $request->input('subscription_id'))->first();

        return response()->json($wipe);

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

        $id = $request->input('id');

        if($id === null) {
            return response()->json([], 400);
        }

        $wiperUser = PhoneWipeUsers::where('id', $id)->first();

        if($wiperUser === null) {
            return response()->json([], 400);
        }

        $wiperUser->fill($request->only('status'));

        if($request->input('username') !== null) {
            $wiperUser->username = $request->input('username');
        }

        if($request->input('password') !== null) {
            $wiperUser->password = Hash::make($request->input('password'));
        }

        if($request->input('wiped_by') !== null && $request->input('wiped_by') != -1) {
            $wiperUser->wiped_by = $request->input('wiped_by');
        }

        $wiperUser->save();
        return response()->json($wiperUser, 200);

    }

}
