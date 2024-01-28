<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Matche;
use App\Models\ProfileView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function get_profile($id)
    {
        // Record the profile view
        ProfileView::create([
            'viewer_id' => Auth::id(),
            'profile_owner_id' => $id,
        ]);

        // Retrieve the profile_owner's data
        $profileOwner = User::find($id);

        $profileInfo = $profileOwner->profile_info;
        $educationInfo = $profileOwner->education_details;
        $familyInfo = $profileOwner->family_details;
        $occupationInfo = $profileOwner->occupation_details;

        // Decoding each JSON separately
        $decodedProfileInfo = json_decode($profileInfo, true);
        $decodedEducationInfo = json_decode($educationInfo, true);
        $decodedFamilyInfo = json_decode($familyInfo, true);
        $decodedOccupationInfo = json_decode($occupationInfo, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile viewed successfully.',
            'data' => [
                'id' => $profileOwner->id,
                'username' => $profileOwner->username,
                'email' => $profileOwner->email,
                'profile_picture' => $profileOwner->profile_picture,
                'images' => $profileOwner->images,
                'profile_info' => $decodedProfileInfo,
                'education_details' => $decodedEducationInfo,
                'family_details' => $decodedFamilyInfo,
                'occupation_details' => $decodedOccupationInfo
            ]
        ], 200);
    }

    public function profile_viewer_list()
    {
        // Get the profile views where the profile_owner_id is the current user
        $profileViews = ProfileView::where('profile_owner_id', Auth::id())
                                    ->with('viewer')
                                    ->get();

        $profileViews->each(function ($profileView)
        {
            if (is_string($profileView->viewer->profile_info)) {
                $profileView->viewer->profile_info = json_decode($profileView->viewer->profile_info, true);
            }

            if (is_string($profileView->viewer->education_details)) {
                $profileView->viewer->education_details = json_decode($profileView->viewer->education_details, true);
            }

            if (is_string($profileView->viewer->family_details)) {
                $profileView->viewer->family_details = json_decode($profileView->viewer->family_details, true);
            }

            if (is_string($profileView->viewer->occupation_details)) {
                $profileView->viewer->occupation_details = json_decode($profileView->viewer->occupation_details, true);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Profile views fetched successfully.',
            'data' => $profileViews
        ], 200);
    }

    public function get_profile_info()
    {
        $user = User::where('id',Auth::user()->id)->first();

        $profileInfo = $user->profile_info;
        $educationInfo = $user->education_details;
        $familyInfo = $user->family_details;
        $occupationInfo = $user->occupation_details;

        // Decoding each JSON separately
        $decodedProfileInfo = json_decode($profileInfo, true);
        $decodedEducationInfo = json_decode($educationInfo, true);
        $decodedFamilyInfo = json_decode($familyInfo, true);
        $decodedOccupationInfo = json_decode($occupationInfo, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Info Fetched Successfully',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'profile_picture' => $user->profile_picture,
                'images' => $user->images,
                'profile_info' => $decodedProfileInfo,
                'education_details' => $decodedEducationInfo,
                'family_details' => $decodedFamilyInfo,
                'occupation_details' => $decodedOccupationInfo
            ]
        ], 200);
    }

    public function profile_info(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();

        // Extract only the filled fields from the request
        $profileInfo = array_filter($request->only([
            'profile_summary', 'name', 'age', 'height', 'weight', 'language',
            'dob', 'division', 'district', 'city', 'marital_status', 'diet',
            'gender', 'religion', 'drinking', 'smoking', 'hobbies'
        ]));

        $educationDetails = array_filter($request->only(['education_level']));

        $occupationDetails = array_filter($request->only([
            'profession', 'designation', 'company_name', 'income'
        ]));

        $familyDetails = array_filter($request->only([
            'family_person', 'father_name', 'mother_name', 'brother_name', 'sister_name'
        ]));

        // Merge only the non-empty arrays
        $user->profile_info = json_encode(array_merge(json_decode($user->profile_info, true) ?? [], $profileInfo));
        $user->education_details = json_encode(array_merge(json_decode($user->education_details, true) ?? [], $educationDetails));
        $user->occupation_details = json_encode(array_merge(json_decode($user->occupation_details, true) ?? [], $occupationDetails));
        $user->family_details = json_encode(array_merge(json_decode($user->family_details, true) ?? [], $familyDetails));
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

        // Fetch the IDs of profiles already requested or added
        $requestedProfileIds = Matche::where('sender_id', $currentUser->id)->pluck('receiver_id')->toArray();
        $addedProfileIds = Matche::where('receiver_id', $currentUser->id)->pluck('sender_id')->toArray();
        $excludedProfileIds = array_merge($requestedProfileIds, $addedProfileIds);

        // Accumulate suggested profiles
        $suggestedProfiles = [];

        foreach ($otherUsers as $otherUser)
        {
            // Skip if the profile is already requested or added
            if (in_array($otherUser->id, $excludedProfileIds)) {
                continue;
            }

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

    public function profile_filter(Request $request)
    {
        $currentUser = Auth::user();

        $ageMin = $request->input('age_min');
        $ageMax = $request->input('age_max');
        $heightMin = $request->input('height_min');
        $heightMax = $request->input('height_max');
        $educationLevel = $request->input('education_level');
        $maritalStatus = $request->input('marital_status');
        $profession = $request->input('profession');
        $district = $request->input('district');
        $division = $request->input('division');
        $city = $request->input('city');

        $querys = User::where('id', '!=', $currentUser->id)->get();

        // Filtering based on $querys
        $filteredQuerys = $querys->filter(function ($query) use ($educationLevel, $maritalStatus, $profession, $district, $division, $city, $ageMin, $ageMax, $heightMin, $heightMax) {
            $othersProfileInfo = json_decode($query->profile_info, true) ?? [];
            $othersEducationInfo = json_decode($query->education_details, true) ?? [];
            $othersOccupationInfo = json_decode($query->occupation_details, true) ?? [];

            // Additional filters based on your criteria
            return (
                (!$educationLevel || (isset($othersEducationInfo['education_level']) && $othersEducationInfo['education_level'] === $educationLevel)) &&
                (!$maritalStatus || (isset($othersProfileInfo['marital_status']) && $othersProfileInfo['marital_status'] === $maritalStatus)) &&
                (!$profession || (isset($othersOccupationInfo['profession']) && $othersOccupationInfo['profession'] === $profession)) &&
                (!$district || (isset($othersProfileInfo['district']) && $othersProfileInfo['district'] === $district)) &&
                (!$division || (isset($othersProfileInfo['division']) && $othersProfileInfo['division'] === $division)) &&
                (!$city || (isset($othersProfileInfo['city']) && $othersProfileInfo['city'] === $city)) &&
                (!$ageMin || (isset($othersProfileInfo['age']) && $othersProfileInfo['age'] >= $ageMin)) &&
                (!$ageMax || (isset($othersProfileInfo['age']) && $othersProfileInfo['age'] <= $ageMax)) &&
                (!$heightMin || (isset($othersProfileInfo['height']) && $othersProfileInfo['height'] >= $heightMin)) &&
                (!$heightMax || (isset($othersProfileInfo['height']) && $othersProfileInfo['height'] <= $heightMax))
            );
        });

        // Build the final result array
        $suggestedQuerys = $filteredQuerys->map(function ($query) {
            $othersProfileInfo = json_decode($query->profile_info, true) ?? [];
            $othersEducationDetails = json_decode($query->education_details, true) ?? [];
            $othersOccupationDetails = json_decode($query->occupation_details, true) ?? [];

            return [
                'id' => $query->id,
                'username' => $query->username,
                'email' => $query->email,
                'profile_picture' => $query->profile_picture,
                'images' => $query->images,
                'profile_info' => $othersProfileInfo,
                'education_details' => $othersEducationDetails,
                'occupation_details' => $othersOccupationDetails,
                'created_at' => $query->created_at,
                'updated_at' => $query->updated_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Filtered profiles fetched successfully',
            'data' => $suggestedQuerys->values(),
        ], 200);
    }

    public function profile_search(Request $request)
    {
        $currentUser = Auth::user();

        // Fetch all other users
        $otherUsers = User::where('id', '!=', $currentUser->id)->get();

        // Accumulate suggested profiles
        $searchedProfiles = [];

        $searchTerm = $request->input('name');

        foreach ($otherUsers as $otherUser)
        {
            $othersProfileInfo = json_decode($otherUser->profile_info, true);
            $othersContactDetails = json_decode($otherUser->contact_details, true);
            $othersEducationDetails = json_decode($otherUser->education_details, true);
            $othersFamilyDetails = json_decode($otherUser->family_details, true);
            $othersOccupationDetails = json_decode($otherUser->occupation_details, true);
            $othersName = $othersProfileInfo['name'];

            // Check if the name contains the search term
            if (stripos($othersName, $searchTerm) !== false) {
                $searchedProfiles[] = [
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
            'message' => 'Searched profiles fetched successfully',
            'data' => $searchedProfiles,
        ], 200);
    }
}
