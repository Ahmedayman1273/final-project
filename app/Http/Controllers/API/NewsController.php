<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    // Get all news items
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        return response()->json(
            News::orderBy('created_at', 'desc')->get()
        );
    }

    // Show single news item by ID
    public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        return response()->json($news);
    }

    // Create news item (admin only)
    public function store(Request $request)
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
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('news', 'public')
            : null;

        $news = News::create([
            'title'   => $request->title,
            'content' => $request->content,
            'image'   => $imagePath,
        ]);

        return response()->json($news, 201);
    }

    // Update news item (admin only)
    public function update(Request $request, News $news)
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
            'title'   => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($news->image) {
                Storage::disk('public')->delete($news->image);
            }

            $news->image = $request->file('image')->store('news', 'public');
        }

        $news->update($request->only(['title', 'content']));

        return response()->json($news);
    }

    // Delete news item (admin only)
    public function destroy(News $news)
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

        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }

        $news->delete();

        return response()->json([
            'message' => 'News deleted',
            'news'    => News::latest()->get(['id', 'title', 'content', 'image', 'created_at'])
        ]);
    }
}
