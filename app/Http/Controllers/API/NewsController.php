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
        return response()->json(News::orderBy('created_at', 'desc')->get());
    }

    // Create news item (admin only)
    public function store(Request $request)
    {
        if (auth()->user()->type !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('news', 'public');
        }

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
        if (auth()->user()->type !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
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
    if (auth()->user()->type !== 'admin') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    if ($news->image) {
        Storage::disk('public')->delete($news->image);
    }

    $news->delete();

    // Return updated list after delete
    $allNews = News::latest()->get(['id', 'title', 'content', 'image', 'created_at']);

    return response()->json([
        'message' => 'News deleted',
        'news' => $allNews
    ]);
}

}
