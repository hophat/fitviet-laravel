<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Lấy danh sách đơn hàng
     */
    public function index(Request $request)
    {
        $query = Order::with(['member:id,name,phone,email', 'items.product']);
        
        // Lọc theo trạng thái
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Lọc theo khách hàng
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        
        // Lọc theo ngày tạo
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        // Sắp xếp
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query->orderBy($sort, $order);
        
        // Trả về dữ liệu phân trang
        $orders = $query->paginate(20);
        
        return response()->json($orders);
    }
    
    /**
     * Lấy thông tin chi tiết đơn hàng
     */
    public function show($id)
    {
        $order = Order::with(['member:id,name,phone,email', 'items.product'])->findOrFail($id);
        return response()->json($order);
    }
    
    /**
     * Tạo đơn hàng mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,transfer',
            'payment_status' => 'required|string|in:pending,completed,refunded',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Tạo đơn hàng
            $order = new Order();
            $order->member_id = $request->member_id;
            $order->discount = $request->discount;
            $order->payment_method = $request->payment_method;
            $order->payment_status = $request->payment_status;
            $order->notes = $request->notes;
            $order->status = 'completed';
            
            // Tính tổng tiền đơn hàng
            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            $order->subtotal = $total;
            $order->total = $total - ($request->discount ?? 0);
            $order->save();
            
            // Thêm chi tiết đơn hàng
            foreach ($request->items as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item['product_id'];
                $orderItem->quantity = $item['quantity'];
                $orderItem->price = $item['price'];
                $orderItem->total = $item['price'] * $item['quantity'];
                $orderItem->save();
                
                // Cập nhật số lượng trong kho
                $product = Product::find($item['product_id']);
                if ($product && $product->stock_quantity !== null) {
                    $product->stock_quantity = max(0, $product->stock_quantity - $item['quantity']);
                    $product->save();
                }
            }
            
            DB::commit();
            
            // Trả về đơn hàng đã tạo kèm chi tiết
            $order = Order::with(['member:id,name,phone,email', 'items.product'])->find($order->id);
            return response()->json($order, 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Lỗi khi tạo đơn hàng: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
            'payment_status' => 'sometimes|required|string|in:pending,completed,refunded',
        ]);
        
        $order = Order::findOrFail($id);
        $order->status = $request->status;
        
        if ($request->has('payment_status')) {
            $order->payment_status = $request->payment_status;
        }
        
        $order->save();
        
        return response()->json($order);
    }
    
    /**
     * Hoàn tiền đơn hàng
     */
    public function refund(Request $request, $id)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0',
            'refund_reason' => 'required|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            $order = Order::findOrFail($id);
            
            // Kiểm tra đơn hàng đã hoàn thành
            if ($order->status !== 'completed') {
                return response()->json(['message' => 'Chỉ có thể hoàn tiền đơn hàng đã hoàn thành'], 400);
            }
            
            // Kiểm tra số tiền hoàn
            if ($request->refund_amount > $order->total) {
                return response()->json(['message' => 'Số tiền hoàn không thể lớn hơn tổng giá trị đơn hàng'], 400);
            }
            
            // Cập nhật trạng thái đơn hàng
            $order->payment_status = 'refunded';
            $order->refund_amount = $request->refund_amount;
            $order->refund_reason = $request->refund_reason;
            $order->refunded_at = now();
            $order->save();
            
            // Cập nhật lại kho nếu cần
            if ($request->has('restore_stock') && $request->restore_stock) {
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock_quantity += $item->quantity;
                        $product->save();
                    }
                }
            }
            
            DB::commit();
            
            return response()->json($order);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Lỗi khi hoàn tiền đơn hàng: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Lấy báo cáo doanh thu
     */
    public function report(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'group_by' => 'sometimes|in:day,week,month',
        ]);
        
        $groupBy = $request->input('group_by', 'day');
        
        $dateFormat = 'Y-m-d';
        $groupByFormat = 'Y-m-d';
        
        if ($groupBy == 'week') {
            $groupByFormat = 'Y-W';
        } elseif ($groupBy == 'month') {
            $groupByFormat = 'Y-m';
        }
        
        $orders = Order::selectRaw('
                DATE_FORMAT(created_at, "' . $groupByFormat . '") as date_group,
                COUNT(*) as order_count,
                SUM(total) as total_revenue,
                SUM(subtotal) as subtotal,
                SUM(discount) as discount
            ')
            ->whereDate('created_at', '>=', $request->from_date)
            ->whereDate('created_at', '<=', $request->to_date)
            ->where('status', 'completed')
            ->groupBy('date_group')
            ->orderBy('date_group')
            ->get();
        
        // Tính tổng
        $summary = [
            'total_orders' => $orders->sum('order_count'),
            'total_revenue' => $orders->sum('total_revenue'),
            'total_discount' => $orders->sum('discount'),
            'period' => [
                'from' => $request->from_date,
                'to' => $request->to_date,
            ]
        ];
        
        return response()->json([
            'data' => $orders,
            'summary' => $summary
        ]);
    }
    
    /**
     * Lấy top sản phẩm bán chạy
     */
    public function topProducts(Request $request)
    {
        $request->validate([
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);
        
        $limit = $request->input('limit', 10);
        
        $query = OrderItem::with('product')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('
                products.id,
                products.name,
                products.price,
                products.category_id,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.total) as total_revenue
            ')
            ->where('orders.status', 'completed');
        
        if ($request->has('from_date')) {
            $query->whereDate('orders.created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('orders.created_at', '<=', $request->to_date);
        }
        
        $products = $query->groupBy('products.id')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json($products);
    }
} 