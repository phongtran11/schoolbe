<?php

namespace App\Http\Controllers;


use App\Http\Requests\LoginRequest;
use App\Models\educations;
use App\Models\profiles;
use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'companies_id' => $request->companies_id,
            'account_type' => $request->account_type,
            'status' => $request->status,

            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    public function login(LoginRequest $request)
    {
//        $validator = Validator::make($request->all(), [
//            'email'    => 'required|string',
//            'password' => 'required|string',
//        ]);

//        if ($validator->fails()) {
//            return response()->json(['error' => $validator->errors()], 400);
//        }



        if(!Auth::attempt(['email' => $request->email, 'password' => $request->password])){
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user  = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }


    public function logout(Request $request) {
            $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout Sucess'], 200);
    }




    public function me(Request $request)
    {
        $id = Auth::user()->getAuthIdentifier();
        // Lấy thông tin hồ sơ cùng với giáo dục và dự án
        $profiles = profiles::with(['educations', 'projects'])->get();



        // Trả về thông tin
        return response()->json(['profiles' => $profiles,
        ]);
    }

}
