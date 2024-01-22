<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Matche;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MatchController extends Controller
{

    public function getMatchRequests()
    {
        $userId = Auth::id();

        // Retrieve match requests where the current user is the receiver
        $matchRequests = Matche::where('receiver_id', $userId)
                                ->where('match_status', 'Pending')
                                ->with('sender') // Load the sender relationship
                                ->get();

        // Decode the profile_info field for each sender
        $matchRequests->each(function ($matchRequest) {
            if (is_string($matchRequest->sender->profile_info)) {
                $matchRequest->sender->profile_info = json_decode($matchRequest->sender->profile_info, true);
            }
        });

        // Decode the education_detais field for each sender
        $matchRequests->each(function ($matchRequest) {
            if (is_string($matchRequest->sender->education_details)) {
                $matchRequest->sender->education_details = json_decode($matchRequest->sender->education_details, true);
            }
        });

        // Decode the occupation_details field for each sender
        $matchRequests->each(function ($matchRequest) {
            if (is_string($matchRequest->sender->occupation_details)) {
                $matchRequest->sender->occupation_details = json_decode($matchRequest->sender->occupation_details, true);
            }
        });

        // Decode the family_details field for each sender
        $matchRequests->each(function ($matchRequest) {
            if (is_string($matchRequest->sender->family_details)) {
                $matchRequest->sender->family_details = json_decode($matchRequest->sender->family_details, true);
            }
        });

        if ($matchRequests->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No match requests found.',
                'data' => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Match requests fetched successfully.',
            'data' => $matchRequests,
        ], 200);
    }

    public function sendMatchRequest($receiverId)
    {
        $match = Matche::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $receiverId,
            'matched_date' => now(),
            'match_status' => 'Pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Match request sent successfully.',
            'data' => $match,
        ], 200);
    }

    public function respondToMatchRequest(Request $request, $matchId)
    {
        $match = Matche::find($matchId);

        if (!$match) {
            return response()->json([
                'status' => 'error',
                'message' => 'Match not found.',
            ], 404);
        }

        $matchStatus = $request->input('match_status');

        if ($matchStatus === 'Accepted')
        {
            $match->update(['match_status' => $matchStatus]);

            // Create a notification for the sender
            $sender = Auth::user(); // Assuming the authenticated user is the sender
            $content = 'Your friend request has been accepted by ' . $sender->username;

            Notification::create([
                'user_id' => $match->sender_id,
                'content' => $content,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Match request accepted.',
                'data' => $match,
            ], 200);

        } elseif ($matchStatus === 'Rejected')
        {
            $match->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Match request rejected.',
            ], 200);

        } else {

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid match_status value.',
            ], 400);
        }
    }

    public function getMatchLists()
    {
        $userId = Auth::id();

        // Retrieve match requests where the current user is the receiver
        $matchLists = Matche::where('receiver_id', $userId)
                                ->where('match_status', 'Accepted')
                                ->with(['sender' => function ($query) {
                                    // Load only the 'profile_info' column from the sender relationship
                                    $query->select('id', 'profile_info');
                                }])
                                ->get();

        // Decode the profile_info field for each sender
        $matchLists->each(function ($matchList) {
            if (is_string($matchList->sender->profile_info)) {
                $matchList->sender->profile_info = json_decode($matchList->sender->profile_info, true);
            }
        });

        if ($matchLists->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No matched lists found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Accepted match lists fetched.',
            'data' => $matchLists,
        ], 200);
    }

    public function getNotifications()
    {
        $notifications = Notification::where('user_id', Auth::user()->id)->get();

        if ($notifications)
        {
            Notification::where('user_id', Auth::user()->id)
                        ->where('read', 0)
                        ->update(['read' => 1]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications fetched successfully.',
            'data' => $notifications,
        ], 200);
    }
}
