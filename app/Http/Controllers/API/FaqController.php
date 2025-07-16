<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

class FaqController extends Controller
{
    // Create new FAQ (admin only)
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer'   => 'required|string',
        ]);

        $user = $request->user();
        if ($user->type !== 'admin') {
            return response()->json(['message' => 'Only admins can add FAQs.'], 403);
        }

        $faq = Faq::create([
            'question' => $request->question,
            'answer'   => $request->answer,
        ]);

        return response()->json([
            'message' => 'FAQ created successfully.',
            'faq' => $faq
        ]);
    }

    // Get all FAQs (admin)
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $faqs = Faq::latest()->get();
        return response()->json(['faqs' => $faqs]);
    }

    // Update existing FAQ (admin only)
    public function update(Request $request, $id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found.'], 404);
        }

        $user = $request->user();
        if ($user->type !== 'admin') {
            return response()->json(['message' => 'Only admins can update FAQs.'], 403);
        }

        $request->validate([
            'question' => 'sometimes|required|string',
            'answer'   => 'sometimes|required|string',
        ]);

        $faq->update($request->only(['question', 'answer']));

        return response()->json([
            'message' => 'FAQ updated successfully.',
            'faq' => $faq
        ]);
    }

    // Delete FAQ (admin only)
    public function destroy(Request $request, $id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found.'], 404);
        }

        $user = $request->user();
        if ($user->type !== 'admin') {
            return response()->json(['message' => 'Only admins can delete FAQs.'], 403);
        }

        $faq->delete();

        return response()->json(['message' => 'FAQ deleted successfully.']);
    }

    // Get all questions only (for chatbot)
    public function questionsOnly()
    {
        $questions = Faq::select('id', 'question')->latest()->get();
        return response()->json(['questions' => $questions]);
    }

    // Get answer to a specific question
    public function getAnswer($id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'Question not found.'], 404);
        }

        return response()->json(['answer' => $faq->answer]);
    }
}
