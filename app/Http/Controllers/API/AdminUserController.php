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
use App\Models\Request as RequestModel;

class AdminUserController extends Controller
{
     // Create new user (student or graduate)
    public function createUser(Request $request)
    {
        $exists = [];

        if ($request->id && User::find($request->id)) {
            $exists[] = 'ID already exists';
        }

        if (User::where('email', $request->email)->exists()) {
            $exists[] = 'Email already exists';
        }

        if ($request->phone_number && User::where('phone_number', $request->phone_number)->exists()) {
            $exists[] = 'Phone number already exists';
        }

        if ($request->personal_email && User::where('personal_email', $request->personal_email)->exists()) {
            $exists[] = 'Personal email already exists';
        }

        if (!empty($exists)) {
            return response()->json([
                'message' => 'Duplicate data found.',
                'errors' => $exists
            ], 409);
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


   // Import users from Excel file
    public function importUsersFromExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $rows = Excel::toCollection(new UsersImport, $request->file('file'))[0];

            $skippedIds = [];
            $importedCount = 0;

            foreach ($rows as $row) {
                if (
                    ($row['id'] && User::find($row['id'])) ||
                    User::where('email', $row['email'])->exists() ||
                    User::where('phone_number', $row['phone_number'])->exists() ||
                    User::where('personal_email', $row['personal_email'])->exists()
                ) {
                    $skippedIds[] = $row['id'];
                    continue;
                }

                User::create([
                    'id'             => $row['id'],
                    'name'           => $row['name'],
                    'email'          => $row['email'],
                    'personal_email' => $row['personal_email'],
                    'phone_number'   => $row['phone_number'],
                    'type'           => $row['type'],
                    'major'          => $row['major'] ?? 'Computer Science',
                    'password'       => Hash::make($row['password'] ?? '123456'),
                ]);

                $importedCount++;
            }

            return response()->json([
                'message'        => 'Import completed.',
                'imported_count' => $importedCount,
                'skipped_count'  => count($skippedIds),
                'skipped_ids'    => $skippedIds
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed', 'error' => $e->getMessage()], 500);
        }
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



    // Helper to format student request output
    private function formatRequests($query)
    {
        return $query->with(['user', 'requestType'])
            ->latest()
            ->get()
            ->map(function ($req) {
                return [
                    'request_id'      => $req->id,
                    'type_id'         => $req->request_id,
                    'type_name'       => $req->requestType->name ?? null,
                    'count'           => $req->count,
                    'total_price'     => $req->total_price,
                    'status'          => $req->status,
                    'admin_status'    => $req->admin_status,
                    'student_name_en' => $req->student_name_en,
                    'student_name_ar' => $req->student_name_ar,
                    'department'      => $req->department,
                    'receipt_image'   => $req->receipt_image,
                ];
            });
    }

    // Get all student requests
    public function allStudentRequests()
    {
        $requests = $this->formatRequests(StudentRequest::query());

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    // Get pending student requests
    public function getPendingRequests()
    {
        $requests = $this->formatRequests(StudentRequest::where('admin_status', 'pending'));

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    // Get accepted student requests
    public function getAcceptedRequests()
    {
        $requests = $this->formatRequests(StudentRequest::where('admin_status', 'accepted'));

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    // Get rejected student requests
    public function getRejectedRequests()
    {
        $requests = $this->formatRequests(StudentRequest::where('admin_status', 'rejected'));

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

    // Get all request types
    public function getAllRequestTypes()
    {
        $types = RequestModel::latest()->get();

        return response()->json([
            'status' => 'success',
            'types' => $types
        ]);
    }

    // Create new request type
    public function createRequestType(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $requestType = RequestModel::create([
            'name'        => $request->name,
            'price'       => $request->price,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Request type created successfully.',
            'request_type' => $requestType
        ], 201);
    }

    // Update request type
    public function updateRequestType(Request $request, $id)
    {
        $requestType = RequestModel::find($id);

        if (!$requestType) {
            return response()->json(['message' => 'Request type not found.'], 404);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $requestType->update([
            'name'        => $request->name,
            'price'       => $request->price,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Request type updated successfully.',
            'request_type' => $requestType
        ]);
    }

    // Delete request type
    public function deleteRequestType($id)
    {
        $requestType = RequestModel::find($id);

        if (!$requestType) {
            return response()->json(['message' => 'Request type not found.'], 404);
        }

        $requestType->delete();

        return response()->json(['message' => 'Request type deleted successfully.']);
    }
}
