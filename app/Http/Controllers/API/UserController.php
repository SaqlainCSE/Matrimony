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
            'profile_summary' => $request->profile_summary,
            'name' => $request->name,
            'age' => $request->age,
            'height' => $request->height,
            'weight' => $request->weight,
            'language' => $request->language,
            'dob' => $request->dob,
            'division' => $request->division,
            'district' => $request->district,
            'city' => $request->city,
            'marital_status' => $request->marital_status,
            'diet' => $request->diet,
            'gender' => $request->gender,
            'religion' => $request->religion,
            'drinking' => $request->drinking,
            'smoking' => $request->smoking,
            'hobbies' => $request->hobbies,
        ];

        $educationDetails = [
            'education_level' => $request->education_level
        ];

        $occupationDetails = [
            'profession' => $request->profession,
            'designation' => $request->designation,
            'company_name' => $request->company_name,
            'income' => $request->income
        ];

        $familyDetails = [
            'family_person' => $request->family_person,
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'brother_name' => $request->brother_name,
            'sister_name' => $request->sister_name
        ];

        $user->profile_info = json_encode($profileInfo);
        $user->education_details = json_encode($educationDetails);
        $user->occupation_details = json_encode($occupationDetails);
        $user->family_details = json_encode($familyDetails);
        $user->update();

        return response()->json([
            'status' => 'success',
            'message' => 'Info Update Successfully',
            'data' => $user
        ], 200);
    }

    public function profile_suggest()
    {
        $currentUser = Auth::user();

        $profileInfo = json_decode($currentUser->profile_info, true);
        $currentReligion = $profileInfo['religion'];
        $currentGender = $profileInfo['gender'];

        // Fetch all other users
        $otherUsers = User::where('id', '!=', $currentUser->id)->get();

        // Accumulate suggested profiles
        $suggestedProfiles = [];

        foreach ($otherUsers as $otherUser)
        {
            $othersProfileInfo = json_decode($otherUser->profile_info, true);
            $othersContactDetails = json_decode($otherUser->contact_details, true);
            $othersEducationDetails = json_decode($otherUser->education_details, true);
            $othersFamilyDetails = json_decode($otherUser->family_details, true);
            $othersOccupationDetails = json_decode($otherUser->occupation_details, true);
            $othersReligion = $othersProfileInfo['religion'];
            $othersGender = $othersProfileInfo['gender'];

            // Check if the other user meets the criteria
            if ($othersGender != $currentGender && $othersReligion == $currentReligion) {
                $suggestedProfiles[] = [
                    'id' => $otherUser->id,
                    'username' => $otherUser->username,
                    'email' => $otherUser->email,
                    'profile_picture' => $otherUser->profile_picture,
                    'images' => $otherUser->images,
                    'profile_info' => $othersProfileInfo,
                    'contact_details' => $othersContactDetails,
                    'education_details' => $othersEducationDetails,
                    'family_details' => $othersFamilyDetails,
                    'occupation_details' => $othersOccupationDetails,
                    'created_at' => $otherUser->created_at,
                    'updated_at' => $otherUser->updated_at,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Suggested Profiles Fetched Successfully',
            'data' => $suggestedProfiles,
        ], 200);
    }
}
