<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\StudentRequest;
use App\Models\Request as RequestModel;


class StudentRequestController extends Controller
{
    // Get all requests for the authenticated user
   // StudentRequestController.php

public function index(Request $request)
{
    $user = $request->user();

    $requests = StudentRequest::where('user_id', $user->id)
        ->with('requestType') // علشان يرجع نوع الطلب
        ->latest()
        ->get();

    return response()->json([
        'status' => 'success',
        'requests' => $requests
    ]);
}


  public function store(Request $request)
{
    $user = $request->user();

    // Deny requests from web interface
    if (strtolower($request->header('X-From', 'web')) === 'web') {
        return response()->json([
            'status' => 'error',
            'message' => 'Submitting requests from web is not allowed.'
        ], 403);
    }

    // Prevent admins from submitting requests
    if ($user->type === 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Admins are not allowed to submit requests.'
        ], 403);
    }

    // Validate input
    $request->validate([
        'request_id'       => 'required|exists:requests,id',
        'count'            => 'required|integer|min:1|max:5',
        'student_id'       => 'required|string|max:50',
        'student_name_ar'  => 'required|string|max:255',
        'student_name_en'  => 'required|string|max:255',
        'department'       => 'required|string|max:255',
        'receipt_image'    => 'required|image|max:2048',
    ]);

    $requestType = RequestModel::find($request->request_id);

    // Prevent students from requesting Graduation Certificate
    if ($user->type === 'student' && stripos($requestType->name, 'graduation certificate') !== false) {
        return response()->json([
            'status' => 'error',
            'message' => 'Students are not allowed to request a graduation certificate.'
        ], 403);
    }

    // Prevent graduates from requesting Enrollment
    if ($user->type === 'graduate' && stripos($requestType->name, 'enrollment') !== false) {
        return response()->json([
            'status' => 'error',
            'message' => 'Graduates are not allowed to request an enrollment certificate.'
        ], 403);
    }

    // Fix count to 1 if user is graduate
    $count = ($user->type === 'graduate') ? 1 : $request->count;

    // Prevent duplicate pending requests
    $existing = StudentRequest::where('user_id', $user->id)
        ->where('request_id', $request->request_id)
        ->where('status', 'pending')
        ->first();

    if ($existing) {
        return response()->json([
            'message' => 'You already submitted this request and it is still pending.'
        ], 409);
    }

    // Store receipt image
    $imagePath = $request->file('receipt_image')->store('receipts', 'public');

    // Calculate total price
    $totalPrice = $requestType->price * $count;

    // Create request
    $studentRequest = StudentRequest::create([
        'user_id'         => $user->id,
        'request_id'      => $request->request_id,
        'count'           => $count,
        'total_price'     => $totalPrice,
        'receipt_image'   => $imagePath,
        'student_id'      => $request->student_id,
        'student_name_ar' => $request->student_name_ar,
        'student_name_en' => $request->student_name_en,
        'department'      => $request->department,
        'status'          => 'pending',
    ]);

    $studentRequest->load('requestType');

    return response()->json([
        'message' => 'Request submitted successfully',
        'request' => [
            'id'             => $studentRequest->id,
            'student_id'     => $studentRequest->student_id,
            'count'          => $studentRequest->count,
            'total_price'    => $studentRequest->total_price,
            'admin_status'   => $studentRequest->admin_status ?? 'pending',
            'request_type'   => [
                'id'    => $studentRequest->requestType->id,
                'name'  => $studentRequest->requestType->name,
                'price' => $studentRequest->requestType->price,
            ]
        ]
    ], 201);
}



    // Delete a pending request (only if admin_status is still 'pending')
public function destroy(Request $request, $id)

{
    $user = $request->user();

    $studentRequest = StudentRequest::find($id);

    // Check if request exists and belongs to current user
    if (!$studentRequest || $studentRequest->user_id !== $user->id) {
        return response()->json([
            'status' => 'error',
            'message' => 'Not found or unauthorized'
        ], 404);
    }

    // Allow delete only if admin_status is still pending
    if ($studentRequest->admin_status !== 'pending') {
        return response()->json([
            'status' => 'error',
            'message' => 'Cannot delete approved or rejected requests'
        ], 403);
    }

    // Delete receipt image if exists
    if ($studentRequest->receipt_image) {
        Storage::disk('public')->delete($studentRequest->receipt_image);
    }

    $studentRequest->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Request deleted successfully'
    ]);
}
}
