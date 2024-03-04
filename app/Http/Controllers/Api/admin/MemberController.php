<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    /**
     * Store a newly created resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /**
         * Validate request
         */
        $validator = Validator::make($request->all(), [
            'name'         => 'required',
            'email'        => 'required|unique:users',
            'password'     => 'required|confirmed',
            'nisn'         => 'required',
            'name'         => 'required',
            'image'        => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Fetch random image from Lorem Picsum
        $response = Http::get('https://picsum.photos/200/300');

        // Check if the request to Lorem Picsum was successful
        if ($response->ok()) {
            $imageContent = $response->body();

            // Generate unique image name
            $imageName = time() . '.jpg';

            // Store image in the filesystem
            Storage::disk('public')->put('users/' . $imageName, $imageContent);

            // Create user with image filename
            $member = User::create([
                'name'     => $request->name,
                'nisn'     => $request->nisn,
                'email'    => $request->email,
                'password' => bcrypt($request->password),
                'image'    => $imageName, // Store only the filename in the database
            ]);

            // Assign role to user
            $member->assignRole($request->roles);

            if ($member) {
                // Return success with Api Resource
                return new UserResource(true, 'Data User Berhasil Disimpan!', $member);
            }

            // Return failed with Api Resource
            return new UserResource(false, 'Data User Gagal Disimpan!', null);
        }
    }
}
