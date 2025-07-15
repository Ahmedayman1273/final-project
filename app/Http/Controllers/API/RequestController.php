<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as RequestModel;

class RequestController extends Controller
{
    // Get all requests (admin only)
    public function index(HttpRequest $request)
    {
        if ($request->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requests = RequestModel::latest()->get();

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    // Create new request (admin only)
    public function store(HttpRequest $request)
    {
        if ($request->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $newRequest = RequestModel::create([
            'name'        => $request->name,
            'price'       => $request->price,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Request created successfully',
            'request' => $newRequest
        ], 201);
    }

    // Update existing request (admin only)
    public function update(HttpRequest $request, $id)
    {
        if ($request->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requestModel = RequestModel::find($id);

        if (!$requestModel) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $requestModel->update([
            'name'        => $request->name,
            'price'       => $request->price,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Request updated successfully',
            'request' => $requestModel
        ]);
    }

    // Delete request (admin only)
    public function destroy(HttpRequest $request, $id)
    {
        if ($request->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requestModel = RequestModel::find($id);

        if (!$requestModel) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        $requestModel->delete();

        return response()->json(['message' => 'Request deleted successfully']);
    }
}
