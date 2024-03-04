<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Librarian;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VerificationUserController extends Controller
{
    public function store(Request $request)
    {
        $userAuth = auth()->guard('api')->user();

        $isUserAdmin = false; // Default value false, jika pengguna tidak memiliki peran admin

        if ($userAuth) { // Memeriksa apakah pengguna masuk
            $userRoles = $userAuth->roles->pluck('name'); // Mendapatkan daftar peran pengguna
            $isUserAdmin = $userRoles->contains('admin'); // Memeriksa apakah pengguna memiliki peran 'admin'
        }

        $roles = $request->input('roles', []);

        // // Check if pengurus_kelas role is selected but siswa role is not selected, then automatically add siswa role
        // if (in_array('pengurus_kelas', $roles) && !in_array('siswa', $roles)) {
        //     $roles[] = 'siswa';
        // }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required|confirmed',
            'roles' => 'required',
            'nisn' => (in_array('anggota', $roles)) ? 'required|unique:members,nisn' : 'nullable',
        ], [
            'name.required' => 'Nama wajib diisi.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.', 
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'roles.required' => 'Peran wajib diisi.',
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.unique' => 'NISN sudah terdaftar.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Periksa apakah request roles memuat peran admin
        if (in_array('admin', $roles)) {
            // Periksa apakah pengguna yang sedang login adalah admin
            if (!$userAuth && !$isUserAdmin) {
                // Jika pengguna tidak login sebagai admin, kembalikan error
                return response()->json(['error' => 'Anda tidak boleh mendaftar sebagai admin.'], 422);
            }
        }

        if (!$userAuth && !$isUserAdmin) {
            // // Jika pengguna tidak login atau tidak memiliki role admin, lakukan validasi NISN atau nip
            // if (in_array('anggota', $roles)) {
            //     $member = Member::where('nisn', $request->nisn)->first();
            //     if (!$member) {
            //         return response()->json(['error' => 'NISN tidak terdaftar. Anda tidak diizinkan mendaftar akun.'], 422);
            //     }
            //     if ($member->member_id) {
            //         return response()->json(['error' => 'Akun dengan NISN yang dimasukkan sudah ada sebelumnya.'], 422);
            //     }
            // } elseif (in_array('pustakawan', $roles)) {
            //     $librarian = Librarian::where('nip', $request->nip)->first();
            //     if (!$librarian) {
            //         return response()->json(['error' => 'NIP tidak terdaftar. Anda tidak diizinkan mendaftar akun.'], 422);
            //     }
            //     if ($librarian->librarian_id) {
            //         return response()->json(['error' => 'Akun dengan NIP yang dimasukkan sudah ada sebelumnya.'], 422);
            //     }
            // } 

            // Simpan NISN yang dimasukkan oleh pengguna
            if (in_array('anggota', $roles) && $request->nisn) {
                Member::create(['nisn' => $request->nisn]);
            }

            // // Simpan NIP yang dimasukkan oleh pengguna
            // if (in_array('pustakawan', $roles) && $request->nip) {
            //     Librarian::create(['nip' => $request->nip]);
            // }
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
        }

        // Buat user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'class' => $request->class,
            'departemen' => $request->departemen,
            'image' => $imageName,
        ]);

        // Assign roles to user
        $user->assignRole($request->roles);

        if (!$userAuth && !$isUserAdmin) {
            // Update NISN and nip with user_id based on roles
            if (in_array('anggota', $roles)) {
                $memberUpdate = Member::where('nisn', $request->nisn)->first();
                if ($memberUpdate) {
                    $memberUpdate->update(['anggota_id' => $user->id]);
                } else {
                    // Handle if member identifier not found
                    return response()->json(['error' => 'Data NISN tidak ditemukan.'], 422);
                }
            } 
            // elseif (in_array('pustakawan', $roles)) {
            //     $librarianUpdate = Librarian::where('nip', $request->nip)->first();
            //     if ($librarianUpdate) {
            //         $librarianUpdate->update(['pustakawan_id' => $user->id]);
            //     } else {
            //         // Handle if librarian identifier not found
            //         return response()->json(['error' => 'Data NIP tidak ditemukan.'], 422);
            //     }
            // }
            // } else {
            //     // Admin sedang membuat akun, tambahkan NISN atau nip baru jika tidak ditemukan di database
            //     if (in_array('member', $roles) || in_array('pengurus_kelas', $roles)) {
            //         $studentIdentifierAdmin = StudentIdentifier::where('nisn', $request->nisn)->first();
            //         if (!$studentIdentifierAdmin) {
            //             // NISN tidak ditemukan, tambahkan NISN baru ke database
            //             $studentIdentifierAdmin = StudentIdentifier::create([
            //                 'nisn' => $request->nisn,
            //                 'student_id' => $user->id,
            //             ]);
            //         } else {
            //             $studentIdentifierAdmin->update(['student_id' => $user->id]);
            //         }
            //     } elseif (in_array('guru', $roles)) {
            //         $teacherIdentifierAdmin = TeacherIdentifier::where('nip', $request->nip)->first();
            //         if (!$teacherIdentifierAdmin) {
            //             // nip tidak ditemukan, tambahkan nip baru ke database
            //             $teacherIdentifierAdmin = TeacherIdentifier::create([
            //                 'nip' => $request->nip,
            //                 'teacher_id' => $user->id,
            //             ]);
            //         } else {
            //             $teacherIdentifierAdmin->update(['teacher_id' => $user->id]);
            //         }
            //     }
        }

        // // Automatically create tasks for the student if any
        // if ($request->roles === ['siswa', 'pengurus_kelas'] && $request->class_id) {
        //     $this->createTasksForStudent($user->id, $request->class_id);
        // }

        if ($user) {
            return new UserResource(true, 'Data User Berhasil Disimpan!', $user);
        } else {
            return new UserResource(false, 'Data User Gagal Disimpan!', null);
        }
    }
}
