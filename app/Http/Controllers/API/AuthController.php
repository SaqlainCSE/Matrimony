<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OTPMail;
use Illuminate\Support\Str;
use Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validate = Validator::make($request->all(),[
            // 'username' => 'required|string|max:250|unique:users,username',
            'email' => 'required|string|max:250|unique:users,email'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'This email already exist!',
                'data' => $validate->errors(),
            ], 403);
        }

        $user = User::create([
            'username' =>  'ID-'.time().random_int(10, 99),
            'email' => $request->email
        ]);

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'User is created successfully.',
            'data' => $data,
        ];

        return response()->json($response, 201);
    }

    public function login(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validate->errors(),
            ], 403);
        }

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid credentials'
                ], 401);
        }

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'User is logged in successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User is logged out successfully'
            ], 200);
    }

    //Login with OTP..................
    public function otpLogin(Request $request)
    {
        // Validate request data (email, etc.)
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Generate and save OTP for the user
        $otp = Str::random(6);
        $user->otp = $otp;
        $user->save();

        // Send OTP via email
        Mail::to($user->email)->send(new OTPMail($otp));

        return response()->json(['message' => 'OTP sent to your email'], 200);

    }

    public function verifyOTP(Request $request)
    {
        // Validate OTP entered by the user
        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 401);
        }

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        // Clear OTP after verification
        $user->otp = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully',
            'data' => $data
        ], 200);
    }


}
