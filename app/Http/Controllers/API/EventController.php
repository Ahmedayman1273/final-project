<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    // Get all events
    public function index()
    {
        return response()->json(
            Event::orderBy('start_time', 'desc')->get()
        );
    }

    // Create a new event (admin only)
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if token is missing or invalid
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        // Check if user is not admin
        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Admins only.'
            ], 403);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'start_time'  => 'required|date',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('events', 'public')
            : null;

        $event = Event::create([
            'title'       => $request->title,
            'description' => $request->description,
            'start_time'  => $request->start_time,
            'image'       => $imagePath,
        ]);

        return response()->json($event, 201);
    }

    // Update event (admin only)
    public function update(Request $request, Event $event)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Admins only.'
            ], 403);
        }

        $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'start_time'  => 'sometimes|required|date',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }

            $event->image = $request->file('image')->store('events', 'public');
        }

        $event->update($request->only(['title', 'description', 'start_time']));

        return response()->json($event);
    }

    // Delete event (admin only)
    public function destroy(Event $event)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Admins only.'
            ], 403);
        }

        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json([
            'message' => 'Event deleted',
            'events'  => Event::latest()->get([
                'id', 'title', 'description', 'image', 'start_time', 'created_at'
            ])
        ]);
    }

    // Show single event by ID
    public function show($id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. Token is missing or invalid.'
        ], 401);
    }

    $event = Event::find($id);

    if (!$event) {
        return response()->json([
            'status' => 'error',
            'message' => 'Record not found.'
        ], 404);
    }

    return response()->json($event);
}

}
