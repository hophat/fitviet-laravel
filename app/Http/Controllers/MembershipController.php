<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Member;
use App\Models\MemberMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MembershipController extends Controller
{
    /**
     * Display a listing of memberships.
     */
    public function index(Request $request)
    {
        $query = Membership::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $memberships = $query->paginate($perPage);

        return response()->json($memberships);
    }

    /**
     * Store a newly created membership plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1', // in days
            'benefits' => 'nullable|array',
            'restrictions' => 'nullable|array',
            'max_freeze_days' => 'nullable|integer|min:0',
            'guest_passes' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $membership = Membership::create($validated);

            DB::commit();
            return response()->json([
                'message' => 'Membership plan created successfully',
                'data' => $membership
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error creating membership plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified membership.
     */
    public function show($id)
    {
        $membership = Membership::findOrFail($id);
        
        // Add statistics
        $membership->active_members = DB::table('member_memberships')
            ->where('membership_id', $id)
            ->where('status', 'active')
            ->count();
        
        $membership->total_revenue = DB::table('member_memberships')
            ->where('membership_id', $id)
            ->sum('price');
        
        return response()->json([
            'data' => $membership
        ]);
    }

    /**
     * Update the specified membership.
     */
    public function update(Request $request, $id)
    {
        $membership = Membership::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration' => 'sometimes|required|integer|min:1',
            'benefits' => 'nullable|array',
            'restrictions' => 'nullable|array',
            'max_freeze_days' => 'nullable|integer|min:0',
            'guest_passes' => 'nullable|integer|min:0',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $membership->update($validated);

            DB::commit();
            return response()->json([
                'message' => 'Membership plan updated successfully',
                'data' => $membership
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error updating membership plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified membership.
     */
    public function destroy($id)
    {
        $membership = Membership::findOrFail($id);

        // Check if there are active members using this membership
        $activeMembers = DB::table('member_memberships')
            ->where('membership_id', $id)
            ->where('status', 'active')
            ->exists();

        if ($activeMembers) {
            return response()->json([
                'message' => 'Cannot delete membership plan with active members'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $membership->delete();

            DB::commit();
            return response()->json([
                'message' => 'Membership plan deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error deleting membership plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign membership to a member.
     */
    public function assignToMember(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'membership_id' => 'required|exists:memberships,id',
            'start_date' => 'required|date',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $member = Member::findOrFail($validated['member_id']);
            $membership = Membership::findOrFail($validated['membership_id']);

            // Check if member has an active membership
            $activeMembership = DB::table('member_memberships')
                ->where('member_id', $member->id)
                ->where('status', 'active')
                ->first();

            if ($activeMembership) {
                return response()->json([
                    'message' => 'Member already has an active membership'
                ], 400);
            }

            // Calculate end date
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = $startDate->copy()->addDays($membership->duration);

            // Calculate final price
            $discount = $validated['discount'] ?? 0;
            $finalPrice = $membership->price * (1 - $discount / 100);

            // Create member membership record
            $memberMembership = DB::table('member_memberships')->insertGetId([
                'member_id' => $member->id,
                'membership_id' => $membership->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $finalPrice,
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update member status
            $member->update(['status' => 'active']);

            DB::commit();
            return response()->json([
                'message' => 'Membership assigned successfully',
                'data' => [
                    'id' => $memberMembership,
                    'member' => $member,
                    'membership' => $membership,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'final_price' => $finalPrice,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error assigning membership',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renew membership for a member.
     */
    public function renewMembership(Request $request, $memberId)
    {
        $validated = $request->validate([
            'membership_id' => 'required|exists:memberships,id',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $member = Member::findOrFail($memberId);
            $membership = Membership::findOrFail($validated['membership_id']);

            // Get current membership
            $currentMembership = DB::table('member_memberships')
                ->where('member_id', $member->id)
                ->orderBy('end_date', 'desc')
                ->first();

            // Calculate start date (either from end of current membership or today)
            if ($currentMembership && Carbon::parse($currentMembership->end_date)->isFuture()) {
                $startDate = Carbon::parse($currentMembership->end_date)->addDay();
            } else {
                $startDate = Carbon::now();
            }

            $endDate = $startDate->copy()->addDays($membership->duration);

            // Calculate final price
            $discount = $validated['discount'] ?? 0;
            $finalPrice = $membership->price * (1 - $discount / 100);

            // Create new membership record
            $memberMembership = DB::table('member_memberships')->insertGetId([
                'member_id' => $member->id,
                'membership_id' => $membership->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $finalPrice,
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update member status
            $member->update(['status' => 'active']);

            DB::commit();
            return response()->json([
                'message' => 'Membership renewed successfully',
                'data' => [
                    'id' => $memberMembership,
                    'member' => $member,
                    'membership' => $membership,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'final_price' => $finalPrice,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error renewing membership',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Freeze membership for a member.
     */
    public function freezeMembership(Request $request, $membershipId)
    {
        $validated = $request->validate([
            'freeze_start_date' => 'required|date|after_or_equal:today',
            'freeze_end_date' => 'required|date|after:freeze_start_date',
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $memberMembership = DB::table('member_memberships')
                ->where('id', $membershipId)
                ->where('status', 'active')
                ->first();

            if (!$memberMembership) {
                return response()->json([
                    'message' => 'Active membership not found'
                ], 404);
            }

            $membership = Membership::find($memberMembership->membership_id);
            $freezeDays = Carbon::parse($validated['freeze_start_date'])
                ->diffInDays(Carbon::parse($validated['freeze_end_date']));

            // Check if freeze days exceed maximum allowed
            if ($membership->max_freeze_days && $freezeDays > $membership->max_freeze_days) {
                return response()->json([
                    'message' => 'Freeze period exceeds maximum allowed days (' . $membership->max_freeze_days . ')'
                ], 400);
            }

            // Update membership with freeze information
            DB::table('member_memberships')
                ->where('id', $membershipId)
                ->update([
                    'freeze_start_date' => $validated['freeze_start_date'],
                    'freeze_end_date' => $validated['freeze_end_date'],
                    'freeze_reason' => $validated['reason'],
                    'status' => 'frozen',
                    'updated_at' => now(),
                ]);

            // Extend end date by freeze duration
            $newEndDate = Carbon::parse($memberMembership->end_date)->addDays($freezeDays);
            DB::table('member_memberships')
                ->where('id', $membershipId)
                ->update(['end_date' => $newEndDate]);

            DB::commit();
            return response()->json([
                'message' => 'Membership frozen successfully',
                'data' => [
                    'freeze_days' => $freezeDays,
                    'new_end_date' => $newEndDate,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error freezing membership',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel membership for a member.
     */
    public function cancelMembership(Request $request, $membershipId)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_method' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $memberMembership = DB::table('member_memberships')
                ->where('id', $membershipId)
                ->whereIn('status', ['active', 'frozen'])
                ->first();

            if (!$memberMembership) {
                return response()->json([
                    'message' => 'Active or frozen membership not found'
                ], 404);
            }

            // Update membership status
            DB::table('member_memberships')
                ->where('id', $membershipId)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $validated['cancellation_reason'],
                    'refund_amount' => $validated['refund_amount'] ?? 0,
                    'refund_method' => $validated['refund_method'] ?? null,
                    'updated_at' => now(),
                ]);

            // Update member status if no other active memberships
            $member = Member::find($memberMembership->member_id);
            $hasOtherActive = DB::table('member_memberships')
                ->where('member_id', $member->id)
                ->where('id', '!=', $membershipId)
                ->whereIn('status', ['active', 'frozen'])
                ->exists();

            if (!$hasOtherActive) {
                $member->update(['status' => 'inactive']);
            }

            DB::commit();
            return response()->json([
                'message' => 'Membership cancelled successfully',
                'data' => [
                    'refund_amount' => $validated['refund_amount'] ?? 0,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error cancelling membership',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get membership statistics.
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_plans' => Membership::count(),
            'active_plans' => Membership::where('status', 'active')->count(),
            'total_active_members' => DB::table('member_memberships')
                ->where('status', 'active')
                ->distinct('member_id')
                ->count('member_id'),
            'revenue_this_month' => DB::table('member_memberships')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('price'),
            'new_memberships_this_month' => DB::table('member_memberships')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'expiring_soon' => DB::table('member_memberships')
                ->where('status', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(7)])
                ->count(),
            'popular_plans' => Membership::select('memberships.*', DB::raw('COUNT(member_memberships.id) as member_count'))
                ->leftJoin('member_memberships', 'memberships.id', '=', 'member_memberships.membership_id')
                ->groupBy('memberships.id')
                ->orderBy('member_count', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}