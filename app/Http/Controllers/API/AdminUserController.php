<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use App\Models\User;
use App\Models\StudentRequest;

class AdminUserController extends Controller
{
    // Create new user (student or graduate)
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'             => 'nullable|integer|unique:users,id',
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'personal_email' => 'nullable|email',
            'phone_number'   => 'required|string|max:20',
            'type'           => 'required|in:student,graduate',
            'major'          => 'nullable|string|max:255',
            'password'       => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'id'             => $request->id,
            'name'           => $request->name,
            'email'          => $request->email,
            'personal_email' => $request->personal_email,
            'phone_number'   => $request->phone_number,
            'type'           => $request->type,
            'major'          => $request->major ?? 'Computer Science',
            'password'       => Hash::make($request->password ?? '123456'),
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => $user
        ], 201);
    }

    // Change user type
    public function changeUserType(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $request->validate([
            'type' => 'required|in:student,graduate'
        ]);

        $user->type = $request->type;
        $user->save();

        return response()->json(['message' => 'User type updated successfully.', 'user' => $user]);
    }

    // Import users from Excel file
    public function importUsersFromExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file'));
            return response()->json(['message' => 'Users imported successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Get all student requests
    public function allStudentRequests()
    {
        $requests = StudentRequest::with(['user', 'requestType'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    // Accept a student request
    public function acceptStudentRequest(Request $request, $id)
    {
        $studentRequest = StudentRequest::find($id);

        if (!$studentRequest) {
            return response()->json(['message' => 'Request not found.'], 404);
        }

        $request->validate([
            'delivery_date' => 'required|date'
        ]);

        $studentRequest->update([
            'admin_status' => 'accepted',
            'status'       => 'approved',
            'notes'        => 'Delivery date: ' . $request->delivery_date
        ]);

        return response()->json(['message' => 'Request accepted successfully.']);
    }

    // Reject a student request
    public function rejectStudentRequest(Request $request, $id)
    {
        $studentRequest = StudentRequest::find($id);

        if (!$studentRequest) {
            return response()->json(['message' => 'Request not found.'], 404);
        }

        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $studentRequest->update([
            'admin_status' => 'rejected',
            'status'       => 'rejected',
            'notes'        => $request->reason
        ]);

        return response()->json(['message' => 'Request rejected successfully.']);
    }
}
