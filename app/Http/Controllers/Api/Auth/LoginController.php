<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    /**
     * index (function yang akan di panggil pertama kali ketika )
     * 
     * @param mixed
     * @return void
     */
    public function index(Request $request)
    {
        //set validasi
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        //response error validasi
        if ($validator->fails()) {
            return response()->json($validator->error(), 422);
        }

        //get 'email' dan 'password' dari input
        $credentials = $request->only('email', 'password');


        if (!$token = auth()->guard('api')->attempt($credentials)) {

            return response()->json([
                'success' => false,
                'message' => 'email or password is incorrect'
            ], 400);
        }

        // Dapatkan informasi peran pengguna
        $user = auth()->guard('api')->user();

        // Ambil peran pengguna
        $roles = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'user' => auth()->guard('api')->user()->only(['name', 'email']),
            'permission' => auth()->guard('api')->user()->getPermissionArray(),
            'roles' => $roles,
            'token' => $token
        ], 200);
    }

    /**
     * logout
     * berfungsi untuk menghapus jwt token
     * @return void
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
        ], 200);
    }
}
