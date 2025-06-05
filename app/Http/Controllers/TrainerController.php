<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Models\TrainerReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrainerController extends Controller
{
    /**
     * Display a listing of trainers.
     */
    public function index(Request $request)
    {
        $query = Trainer::with(['schedules', 'reviews']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by specialty
        if ($request->has('specialty')) {
            $query->where('specialty', 'like', '%' . $request->specialty . '%');
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $trainers = $query->paginate($perPage);

        return response()->json($trainers);
    }

    /**
     * Store a newly created trainer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:trainers,email',
            'phone' => 'required|string|max:20',
            'specialty' => 'required|string|max:255',
            'experience_years' => 'required|integer|min:0',
            'bio' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
            'certifications' => 'nullable|array',
            'working_hours' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Handle photo upload
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store('trainers', 'public');
            }

            $trainer = Trainer::create($validated);

            DB::commit();
            return response()->json([
                'message' => 'Trainer created successfully',
                'data' => $trainer->load(['schedules', 'reviews'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error creating trainer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified trainer.
     */
    public function show($id)
    {
        $trainer = Trainer::with(['schedules', 'reviews.member'])->findOrFail($id);
        
        // Calculate average rating
        $avgRating = $trainer->reviews()->avg('rating');
        $trainer->average_rating = round($avgRating, 1);
        
        return response()->json([
            'data' => $trainer
        ]);
    }

    /**
     * Update the specified trainer.
     */
    public function update(Request $request, $id)
    {
        $trainer = Trainer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:trainers,email,' . $id,
            'phone' => 'sometimes|required|string|max:20',
            'specialty' => 'sometimes|required|string|max:255',
            'experience_years' => 'sometimes|required|integer|min:0',
            'bio' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
            'certifications' => 'nullable|array',
            'working_hours' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($trainer->photo) {
                    Storage::disk('public')->delete($trainer->photo);
                }
                $validated['photo'] = $request->file('photo')->store('trainers', 'public');
            }

            $trainer->update($validated);

            DB::commit();
            return response()->json([
                'message' => 'Trainer updated successfully',
                'data' => $trainer->load(['schedules', 'reviews'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error updating trainer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified trainer.
     */
    public function destroy($id)
    {
        $trainer = Trainer::findOrFail($id);

        DB::beginTransaction();
        try {
            // Check if trainer has active schedules
            if ($trainer->schedules()->where('status', 'scheduled')->exists()) {
                return response()->json([
                    'message' => 'Cannot delete trainer with active schedules'
                ], 400);
            }

            // Delete photo if exists
            if ($trainer->photo) {
                Storage::disk('public')->delete($trainer->photo);
            }

            $trainer->delete();

            DB::commit();
            return response()->json([
                'message' => 'Trainer deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error deleting trainer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trainer's schedule.
     */
    public function schedule(Request $request, $id)
    {
        $trainer = Trainer::findOrFail($id);

        $query = $trainer->schedules();

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('start_time', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $schedules = $query->with('member')
                          ->orderBy('start_time', 'asc')
                          ->get();

        return response()->json([
            'data' => $schedules
        ]);
    }

    /**
     * Get trainer's reviews.
     */
    public function reviews($id)
    {
        $trainer = Trainer::findOrFail($id);

        $reviews = $trainer->reviews()
                          ->with('member')
                          ->orderBy('created_at', 'desc')
                          ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Add a review for trainer.
     */
    public function addReview(Request $request, $id)
    {
        $trainer = Trainer::findOrFail($id);

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Check if member already reviewed this trainer
        $existingReview = TrainerReview::where('trainer_id', $id)
                                      ->where('member_id', $validated['member_id'])
                                      ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Member has already reviewed this trainer'
            ], 400);
        }

        $review = $trainer->reviews()->create($validated);

        return response()->json([
            'message' => 'Review added successfully',
            'data' => $review->load('member')
        ], 201);
    }

    /**
     * Get trainer statistics.
     */
    public function statistics($id)
    {
        $trainer = Trainer::findOrFail($id);

        $stats = [
            'total_sessions' => $trainer->schedules()->count(),
            'completed_sessions' => $trainer->schedules()->where('status', 'completed')->count(),
            'upcoming_sessions' => $trainer->schedules()->where('status', 'scheduled')->count(),
            'total_members' => $trainer->schedules()->distinct('member_id')->count('member_id'),
            'average_rating' => round($trainer->reviews()->avg('rating'), 1),
            'total_reviews' => $trainer->reviews()->count(),
            'this_month_sessions' => $trainer->schedules()
                ->whereMonth('start_time', now()->month)
                ->whereYear('start_time', now()->year)
                ->count(),
            'this_month_earnings' => $trainer->schedules()
                ->whereMonth('start_time', now()->month)
                ->whereYear('start_time', now()->year)
                ->where('status', 'completed')
                ->sum('price'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}