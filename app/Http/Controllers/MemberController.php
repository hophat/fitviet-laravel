<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Order;
use App\Models\Membership;
use App\Models\MemberCheckin;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    /**
     * Lấy danh sách hội viên
     */
    public function index(Request $request)
    {
        $query = Member::with('membership');
        
        // Tìm kiếm theo tên, email, số điện thoại
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Lọc theo trạng thái
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Lọc theo gói thành viên
        if ($request->has('membership_id')) {
            $query->where('membership_id', $request->membership_id);
        }
        
        // Sắp xếp
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query->orderBy($sort, $order);
        
        // Phân trang
        $perPage = $request->input('per_page', 15);
        $members = $query->paginate($perPage);
        
        return response()->json($members);
    }
    
    /**
     * Lấy chi tiết hội viên
     */
    public function show($id)
    {
        $member = Member::with(['membership'])->findOrFail($id);
        return response()->json($member);
    }
    
    /**
     * Thêm hội viên mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members',
            'phone' => 'required|string|max:20|unique:members',
            'address' => 'nullable|string',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'membership_id' => 'nullable|exists:memberships,id',
            'membership_start_date' => 'nullable|date',
            'membership_end_date' => 'nullable|date|after_or_equal:membership_start_date',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);
        
        $member = Member::create($request->all());
        
        return response()->json($member, 201);
    }
    
    /**
     * Cập nhật thông tin hội viên
     */
    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:members,email,' . $id,
            'phone' => 'sometimes|required|string|max:20|unique:members,phone,' . $id,
            'address' => 'nullable|string',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'membership_id' => 'nullable|exists:memberships,id',
            'membership_start_date' => 'nullable|date',
            'membership_end_date' => 'nullable|date|after_or_equal:membership_start_date',
            'status' => 'sometimes|required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);
        
        $member->update($request->all());
        
        return response()->json($member);
    }
    
    /**
     * Xóa hội viên
     */
    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Lấy danh sách đơn hàng của hội viên
     */
    public function orders($id)
    {
        $member = Member::findOrFail($id);
        $orders = Order::with('items.product')
            ->where('member_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json($orders);
    }
    
    /**
     * Lấy lịch sử thanh toán của hội viên
     */
    public function payments($id)
    {
        $member = Member::findOrFail($id);
        
        // Lấy đơn hàng theo dạng thanh toán
        $payments = Order::where('member_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'date' => $order->created_at->format('d/m/Y'),
                    'time' => $order->created_at->format('H:i'),
                    'description' => 'Đơn hàng #' . $order->id,
                    'type' => $this->getOrderType($order),
                    'amount' => $order->total,
                    'amountType' => 'debit',
                    'method' => $order->payment_method,
                    'status' => $order->payment_status,
                    'notes' => $order->notes,
                    'order_id' => $order->id
                ];
            });
        
        // Nếu có hoàn tiền
        $refunds = Order::where('member_id', $id)
            ->where('payment_status', 'refunded')
            ->whereNotNull('refunded_at')
            ->orderBy('refunded_at', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'id' => 'R-' . $order->id,
                    'date' => $order->refunded_at->format('d/m/Y'),
                    'time' => $order->refunded_at->format('H:i'),
                    'description' => 'Hoàn tiền đơn hàng #' . $order->id,
                    'type' => $this->getOrderType($order),
                    'amount' => $order->refund_amount,
                    'amountType' => 'credit',
                    'method' => $order->payment_method,
                    'status' => 'refunded',
                    'notes' => $order->refund_reason,
                    'order_id' => $order->id
                ];
            });
        
        $result = $payments->concat($refunds)->sortByDesc(function ($item) {
            return $item['date'] . ' ' . $item['time'];
        })->values();
        
        return response()->json($result);
    }
    
    /**
     * Lấy thông tin gói thành viên
     */
    public function membership($id)
    {
        $member = Member::with('membership')->findOrFail($id);
        
        $result = [
            'member_id' => $member->id,
            'member_name' => $member->name,
            'membership' => $member->membership,
            'start_date' => $member->membership_start_date,
            'end_date' => $member->membership_end_date,
            'is_active' => $member->status === 'active',
            'days_left' => $member->membership_end_date ? now()->diffInDays($member->membership_end_date, false) : null
        ];
        
        return response()->json($result);
    }
    
    /**
     * Xác định loại đơn hàng (sản phẩm, dịch vụ, v.v.)
     */
    private function getOrderType($order)
    {
        $hasMembership = false;
        $hasProduct = false;
        $hasPT = false;
        
        foreach ($order->items as $item) {
            $product = $item->product;
            
            if ($product) {
                if ($product->category_id === 2) { // Thực phẩm bổ sung
                    $hasProduct = true;
                } elseif ($product->category_id === 4) { // Dịch vụ
                    if (stripos($product->name, 'PT') !== false) {
                        $hasPT = true;
                    } elseif (stripos($product->name, 'Gói') !== false) {
                        $hasMembership = true;
                    }
                }
            }
        }
        
        if ($hasMembership) return 'membership';
        if ($hasPT) return 'pt';
        if ($hasProduct) return 'product';
        
        return 'other';
    }
    
    /**
     * Check-in cho hội viên
     */
    public function checkin($id, Request $request)
    {
        $member = Member::findOrFail($id);
        
        // Kiểm tra trạng thái hội viên
        if ($member->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Hội viên không ở trạng thái hoạt động.',
            ], 400);
        }
        
        // Kiểm tra nếu hội viên có gói thành viên đang hoạt động
        $activeMembership = $member->memberships()
            ->where('end_date', '>=', now())
            ->where('member_memberships.status', 'active')
            ->first();
            
        if (!$activeMembership) {
            return response()->json([
                'success' => false,
                'message' => 'Hội viên không có gói thành viên đang hoạt động.',
            ], 400);
        }
        
        // Validate dữ liệu đầu vào
        $request->validate([
            'check_in_time' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        // Lưu thông tin check-in (giả định có bảng member_checkins)
        $checkin = DB::table('member_checkins')->insert([
            'member_id' => $member->id,
            'checkin_time' => $request->check_in_time,
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Check-in thành công.',
            'data' => [
                'member_id' => $member->id,
                'member_name' => $member->name,
                'checkin_time' => $request->check_in_time,
                'notes' => $request->notes,
            ]
        ]);
    }

    /**
     * Get check-in history for a member
     */
    public function checkInHistory($id, Request $request)
    {
        $member = Member::findOrFail($id);
        
        $query = MemberCheckin::where('member_id', $id);
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('checkin_time', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('checkin_time', '<=', $request->end_date);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'checkin_time');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $checkIns = $query->paginate($perPage);
        
        return response()->json([
            'data' => $checkIns,
            'summary' => [
                'total_checkins' => $member->checkins()->count(),
                'this_month' => $member->checkins()->thisMonth()->count(),
                'this_week' => $member->checkins()->thisWeek()->count(),
                'today' => $member->checkins()->today()->count(),
            ]
        ]);
    }

    /**
     * Get member statistics
     */
    public function statistics($id)
    {
        $member = Member::findOrFail($id);
        
        // Check-in statistics
        $totalCheckins = $member->checkins()->count();
        $thisMonthCheckins = $member->checkins()->thisMonth()->count();
        $thisWeekCheckins = $member->checkins()->thisWeek()->count();
        $todayCheckins = $member->checkins()->today()->count();
        
        // Average check-ins per week (last 3 months)
        $threeMonthsAgo = now()->subMonths(3);
        $checkinsLast3Months = $member->checkins()
            ->where('checkin_time', '>=', $threeMonthsAgo)
            ->count();
        $weeksIn3Months = 12;
        $avgCheckinsPerWeek = round($checkinsLast3Months / $weeksIn3Months, 1);
        
        // Order statistics
        $totalOrders = $member->orders()->count();
        $totalSpent = $member->orders()
            ->where('payment_status', 'paid')
            ->sum('total');
        $thisMonthSpent = $member->orders()
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
        
        // Session statistics
        $totalSessions = $member->schedules()->count();
        $completedSessions = $member->schedules()
            ->where('status', 'completed')
            ->count();
        $upcomingSessions = $member->schedules()
            ->where('status', 'scheduled')
            ->where('start_time', '>', now())
            ->count();
        
        // PT sessions
        $totalPTSessions = $member->schedules()
            ->where('type', 'pt')
            ->count();
        $completedPTSessions = $member->schedules()
            ->where('type', 'pt')
            ->where('status', 'completed')
            ->count();
        
        // Class attendance
        $totalClasses = $member->schedules()
            ->where('type', 'class')
            ->count();
        $completedClasses = $member->schedules()
            ->where('type', 'class')
            ->where('status', 'completed')
            ->count();
        
        // Membership info
        $activeMembership = $member->memberships()
            ->where('end_date', '>=', now())
            ->where('member_memberships.status', 'active')
            ->first();
        
        $membershipDaysRemaining = 0;
        if ($activeMembership) {
            $membershipDaysRemaining = now()->diffInDays($activeMembership->pivot->end_date);
        }
        
        return response()->json([
            'data' => [
                'check_ins' => [
                    'total' => $totalCheckins,
                    'this_month' => $thisMonthCheckins,
                    'this_week' => $thisWeekCheckins,
                    'today' => $todayCheckins,
                    'avg_per_week' => $avgCheckinsPerWeek,
                ],
                'orders' => [
                    'total_orders' => $totalOrders,
                    'total_spent' => $totalSpent,
                    'this_month_spent' => $thisMonthSpent,
                ],
                'sessions' => [
                    'total' => $totalSessions,
                    'completed' => $completedSessions,
                    'upcoming' => $upcomingSessions,
                    'pt_sessions' => [
                        'total' => $totalPTSessions,
                        'completed' => $completedPTSessions,
                    ],
                    'classes' => [
                        'total' => $totalClasses,
                        'completed' => $completedClasses,
                    ],
                ],
                'membership' => [
                    'active' => $activeMembership ? true : false,
                    'days_remaining' => $membershipDaysRemaining,
                    'membership_name' => $activeMembership ? $activeMembership->name : null,
                ],
                'member_since' => $member->created_at->format('Y-m-d'),
                'member_days' => $member->created_at->diffInDays(now()),
            ]
        ]);
    }

    /**
     * Get member's sessions/schedules
     */
    public function sessions($id, Request $request)
    {
        $member = Member::findOrFail($id);
        
        $query = Schedule::where('member_id', $id)
            ->with(['trainer']);
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('end_time', '<=', $request->end_date);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'start_time');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Get data based on view type
        if ($request->get('view') === 'calendar') {
            $sessions = $query->get()->map(function ($session) {
                return [
                    'id' => $session->id,
                    'title' => $session->title,
                    'start' => $session->start_time,
                    'end' => $session->end_time,
                    'type' => $session->type,
                    'status' => $session->status,
                    'trainer' => $session->trainer ? $session->trainer->name : null,
                    'location' => $session->location,
                    'notes' => $session->notes,
                ];
            });
        } else {
            $perPage = $request->get('per_page', 10);
            $sessions = $query->paginate($perPage);
        }
        
        return response()->json([
            'data' => $sessions
        ]);
    }
} 