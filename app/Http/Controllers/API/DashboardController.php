<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\StudentRequest;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. Admins only.'
            ], 403);
        }

        $latestEvents = Event::latest()
            ->take(2)
            ->get(['id', 'title', 'description', 'image', 'start_time']);

        $total    = StudentRequest::count();
        $pending  = StudentRequest::where('status', 'pending')->count();
        $accepted = StudentRequest::where('status', 'accepted')->count();
        $rejected = StudentRequest::where('status', 'rejected')->count();

        $total = $total > 0 ? $total : 1;

        $stats = [
            'pending_percentage'  => round(($pending / $total) * 100),
            'accepted_percentage' => round(($accepted / $total) * 100),
            'rejected_percentage' => round(($rejected / $total) * 100),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'events' => $latestEvents,
                'stats'  => $stats,
            ]
        ]);
    }
}
