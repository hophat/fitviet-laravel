<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Trainer;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['trainer', 'member']);

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('end_time', '<=', $request->end_date);
        }

        // Filter by trainer
        if ($request->has('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        // Filter by member
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'start_time');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Get data based on view type
        if ($request->get('view') === 'calendar') {
            $schedules = $query->get();
        } else {
            $perPage = $request->get('per_page', 10);
            $schedules = $query->paginate($perPage);
        }

        return response()->json($schedules);
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:pt,class,gym',
            'trainer_id' => 'nullable|exists:trainers,id',
            'member_id' => 'nullable|exists:members,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'location' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Check trainer availability
            if ($validated['trainer_id']) {
                $conflictingSchedule = Schedule::where('trainer_id', $validated['trainer_id'])
                    ->where('status', 'scheduled')
                    ->where(function ($query) use ($validated) {
                        $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                              ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                              ->orWhere(function ($q) use ($validated) {
                                  $q->where('start_time', '<=', $validated['start_time'])
                                    ->where('end_time', '>=', $validated['end_time']);
                              });
                    })
                    ->exists();

                if ($conflictingSchedule) {
                    return response()->json([
                        'message' => 'Trainer is not available at this time'
                    ], 400);
                }
            }

            // Check location availability for classes
            if ($validated['type'] === 'class') {
                $conflictingLocation = Schedule::where('location', $validated['location'])
                    ->where('status', 'scheduled')
                    ->where(function ($query) use ($validated) {
                        $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                              ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                              ->orWhere(function ($q) use ($validated) {
                                  $q->where('start_time', '<=', $validated['start_time'])
                                    ->where('end_time', '>=', $validated['end_time']);
                              });
                    })
                    ->exists();

                if ($conflictingLocation) {
                    return response()->json([
                        'message' => 'Location is not available at this time'
                    ], 400);
                }
            }

            $schedule = Schedule::create($validated);

            DB::commit();
            return response()->json([
                'message' => 'Schedule created successfully',
                'data' => $schedule->load(['trainer', 'member'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error creating schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified schedule.
     */
    public function show($id)
    {
        $schedule = Schedule::with(['trainer', 'member'])->findOrFail($id);
        
        return response()->json([
            'data' => $schedule
        ]);
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        // Cannot update completed or cancelled schedules
        if ($schedule->status !== 'scheduled') {
            return response()->json([
                'message' => 'Cannot update completed or cancelled schedules'
            ], 400);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:pt,class,gym',
            'trainer_id' => 'nullable|exists:trainers,id',
            'member_id' => 'nullable|exists:members,id',
            'start_time' => 'sometimes|required|date|after:now',
            'end_time' => 'sometimes|required|date|after:start_time',
            'location' => 'sometimes|required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Check trainer availability if trainer is changed or time is changed
            if (isset($validated['trainer_id']) || isset($validated['start_time']) || isset($validated['end_time'])) {
                $trainerId = $validated['trainer_id'] ?? $schedule->trainer_id;
                $startTime = $validated['start_time'] ?? $schedule->start_time;
                $endTime = $validated['end_time'] ?? $schedule->end_time;

                if ($trainerId) {
                    $conflictingSchedule = Schedule::where('trainer_id', $trainerId)
                        ->where('id', '!=', $id)
                        ->where('status', 'scheduled')
                        ->where(function ($query) use ($startTime, $endTime) {
                            $query->whereBetween('start_time', [$startTime, $endTime])
                                  ->orWhereBetween('end_time', [$startTime, $endTime])
                                  ->orWhere(function ($q) use ($startTime, $endTime) {
                                      $q->where('start_time', '<=', $startTime)
                                        ->where('end_time', '>=', $endTime);
                                  });
                        })
                        ->exists();

                    if ($conflictingSchedule) {
                        return response()->json([
                            'message' => 'Trainer is not available at this time'
                        ], 400);
                    }
                }
            }

            $schedule->update($validated);

            DB::commit();
            return response()->json([
                'message' => 'Schedule updated successfully',
                'data' => $schedule->load(['trainer', 'member'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error updating schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);

        // Cannot delete completed schedules
        if ($schedule->status === 'completed') {
            return response()->json([
                'message' => 'Cannot delete completed schedules'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $schedule->delete();

            DB::commit();
            return response()->json([
                'message' => 'Schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error deleting schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update schedule status.
     */
    public function updateStatus(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Additional logic based on status change
            if ($validated['status'] === 'completed' && $schedule->status === 'scheduled') {
                // Mark as completed - could trigger additional actions like payment processing
                $validated['completed_at'] = now();
            } elseif ($validated['status'] === 'cancelled' && $schedule->status === 'scheduled') {
                // Mark as cancelled - could trigger notifications
                $validated['cancelled_at'] = now();
                $validated['cancelled_reason'] = $validated['notes'] ?? 'No reason provided';
            }

            $schedule->update($validated);

            DB::commit();
            return response()->json([
                'message' => 'Schedule status updated successfully',
                'data' => $schedule->load(['trainer', 'member'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error updating schedule status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available time slots for a trainer.
     */
    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'trainer_id' => 'required|exists:trainers,id',
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'required|integer|min:30|max:180', // duration in minutes
        ]);

        $trainer = Trainer::findOrFail($validated['trainer_id']);
        $date = Carbon::parse($validated['date']);
        $duration = $validated['duration'];

        // Get trainer's working hours for the day
        $dayOfWeek = strtolower($date->format('l'));
        $workingHours = $trainer->working_hours[$dayOfWeek] ?? null;

        if (!$workingHours || !$workingHours['is_working']) {
            return response()->json([
                'data' => []
            ]);
        }

        // Get all scheduled sessions for the trainer on this date
        $existingSchedules = Schedule::where('trainer_id', $trainer->id)
            ->whereDate('start_time', $date)
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->get();

        // Generate available time slots
        $slots = [];
        $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['start_time']);
        $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours['end_time']);
        $slotDuration = $duration;

        $currentSlot = $workStart->copy();
        while ($currentSlot->copy()->addMinutes($slotDuration)->lte($workEnd)) {
            $slotEnd = $currentSlot->copy()->addMinutes($slotDuration);
            
            // Check if this slot conflicts with existing schedules
            $isAvailable = true;
            foreach ($existingSchedules as $schedule) {
                $scheduleStart = Carbon::parse($schedule->start_time);
                $scheduleEnd = Carbon::parse($schedule->end_time);
                
                if (
                    ($currentSlot->gte($scheduleStart) && $currentSlot->lt($scheduleEnd)) ||
                    ($slotEnd->gt($scheduleStart) && $slotEnd->lte($scheduleEnd)) ||
                    ($currentSlot->lte($scheduleStart) && $slotEnd->gte($scheduleEnd))
                ) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $slots[] = [
                    'start_time' => $currentSlot->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'datetime_start' => $currentSlot->toIso8601String(),
                    'datetime_end' => $slotEnd->toIso8601String(),
                ];
            }

            $currentSlot->addMinutes(30); // Move to next slot (30 min intervals)
        }

        return response()->json([
            'data' => $slots
        ]);
    }

    /**
     * Get schedule statistics.
     */
    public function statistics(Request $request)
    {
        $query = Schedule::query();

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('end_time', '<=', $request->end_date);
        }

        $stats = [
            'total_schedules' => $query->count(),
            'scheduled' => $query->clone()->where('status', 'scheduled')->count(),
            'completed' => $query->clone()->where('status', 'completed')->count(),
            'cancelled' => $query->clone()->where('status', 'cancelled')->count(),
            'by_type' => [
                'pt' => $query->clone()->where('type', 'pt')->count(),
                'class' => $query->clone()->where('type', 'class')->count(),
                'gym' => $query->clone()->where('type', 'gym')->count(),
            ],
            'revenue' => $query->clone()->where('status', 'completed')->sum('price'),
            'upcoming_today' => Schedule::whereDate('start_time', today())
                ->where('status', 'scheduled')
                ->count(),
            'this_week' => Schedule::whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}