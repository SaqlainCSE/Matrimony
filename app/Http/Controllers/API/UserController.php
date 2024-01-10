<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function get_profile_info()
    {
        $user = User::where('id',Auth::user()->id)->first();

        $profileInfo = $user->profile_info;
        $educationInfo = $user->education_details;
        $occupationInfo = $user->occupation_details;

        // Decoding each JSON separately
        $decodedProfileInfo = json_decode($profileInfo, true);
        $decodedEducationInfo = json_decode($educationInfo, true);
        $decodedOccupationInfo = json_decode($occupationInfo, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Info Fetched Successfully',
            'data' => [$decodedProfileInfo,$decodedEducationInfo,$decodedOccupationInfo]
        ], 200);
    }

    public function profile_info(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();

        $profileInfo = [
            'division' => $request->division,
            'district' => $request->district,
            'city' => $request->city,
            'marital_status' => $request->marital_status,
            'diet' => $request->diet,
            'religion' => $request->religion
        ];

        $educationDetails = [
            'education_level' => $request->education_level
        ];

        $occupationDetails = [
            'profession' => $request->profession,
            'designation' => $request->designation,
            'income' => $request->income
        ];

        $user->profile_info = json_encode($profileInfo);
        $user->education_details = json_encode($educationDetails);
        $user->occupation_details = json_encode($occupationDetails);
        $user->update();

        return response()->json([
            'status' => 'success',
            'message' => 'Info Update Successfully',
            'data' => $user
        ], 200);
    }
}
