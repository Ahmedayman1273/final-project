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
    public function index(Request $request)
    {
        $user = $request->user();

        $requests = StudentRequest::where('user_id', $user->id)
            ->with('requestType')
            ->latest()
            ->get()
            ->map(function ($req) {
                return [
                    'id'                => $req->id,
                    'type'              => $req->requestType->name ?? null,
                    'status'            => $req->status,
                    'count'             => $req->count,
                    'total_price'       => $req->total_price,
                    'student_name_ar'   => $req->student_name_ar,
                    'student_name_en'   => $req->student_name_en,
                    'department'        => $req->department,
                    'receipt_image'     => asset('storage/' . $req->receipt_image),
                    'submitted_at'      => $req->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    // Store a new request
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
            'count'            => 'required|integer|min:1',
            'student_name_ar'  => 'required|string|max:255',
            'student_name_en'  => 'required|string|max:255',
            'department'       => 'required|string|max:255',
            'receipt_image'    => 'required|image|max:2048',
        ]);

        $requestType = RequestModel::find($request->request_id);

        //Prevent students from requesting graduation certificate
        if ($user->type === 'student' && $requestType->name === 'Graduation Certificate') {
            return response()->json([
                'status' => 'error',
                'message' => 'Students are not allowed to request a graduation certificate.'
            ], 403);
        }

        //  Prevent graduates from requesting enrollment proof
        if ($user->type === 'graduate' && $requestType->name === 'Enrollment Proof') {
            return response()->json([
                'status' => 'error',
                'message' => 'Graduates are not allowed to request an enrollment proof.'
            ], 403);
        }

        //  Prevent duplicate pending request
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
        $totalPrice = $requestType->price * $request->count;

        // Create request
        $studentRequest = StudentRequest::create([
            'user_id'         => $user->id,
            'request_id'      => $request->request_id,
            'count'           => $request->count,
            'total_price'     => $totalPrice,
            'receipt_image'   => $imagePath,
            'student_name_ar' => $request->student_name_ar,
            'student_name_en' => $request->student_name_en,
            'department'      => $request->department,
            'status'          => 'pending',
        ]);

        return response()->json([
            'message' => 'Request submitted successfully',
            'request' => $studentRequest
        ], 201);
    }

    // Delete a pending request
    public function destroy(Request $request, $id)
    {
        $studentRequest = StudentRequest::find($id);

        if (!$studentRequest || $studentRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found or unauthorized'], 404);
        }

        if ($studentRequest->status !== 'pending') {
            return response()->json(['message' => 'Cannot delete approved/rejected requests'], 403);
        }

        if ($studentRequest->receipt_image) {
            Storage::disk('public')->delete($studentRequest->receipt_image);
        }

        $studentRequest->delete();

        return response()->json(['message' => 'Request deleted successfully']);
    }
}
