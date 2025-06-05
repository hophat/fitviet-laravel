<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Lấy danh sách sản phẩm
     */
    public function index(Request $request)
    {
        $query = Product::with('category');
        
        // Lọc theo danh mục nếu có
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Tìm kiếm theo tên
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Sắp xếp
        $sort = $request->input('sort', 'name');
        $order = $request->input('order', 'asc');
        $query->orderBy($sort, $order);
        
        // Trả về dữ liệu phân trang
        $products = $query->paginate(20);
        
        return response()->json($products);
    }
    
    /**
     * Lấy thông tin chi tiết sản phẩm
     */
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }
    
    /**
     * Thêm sản phẩm mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:product_categories,id',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:50|unique:products',
            'stock_quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|string'
        ]);
        
        $product = Product::create($request->all());
        
        return response()->json($product, 201);
    }
    
    /**
     * Cập nhật thông tin sản phẩm
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:product_categories,id',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $id,
            'stock_quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|string'
        ]);
        
        $product->update($request->all());
        
        return response()->json($product);
    }
    
    /**
     * Xóa sản phẩm
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Lấy danh sách danh mục sản phẩm
     */
    public function categories()
    {
        $categories = ProductCategory::all();
        return response()->json($categories);
    }
    
    /**
     * Kiểm tra tồn kho
     */
    public function checkStock($id)
    {
        $product = Product::findOrFail($id);
        
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'stock_quantity' => $product->stock_quantity,
            'in_stock' => $product->stock_quantity > 0
        ]);
    }
    
    /**
     * Cập nhật số lượng tồn kho
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer',
            'operation' => 'required|in:add,subtract,set'
        ]);
        
        $product = Product::findOrFail($id);
        
        switch ($request->operation) {
            case 'add':
                $product->stock_quantity += $request->quantity;
                break;
            case 'subtract':
                $product->stock_quantity = max(0, $product->stock_quantity - $request->quantity);
                break;
            case 'set':
                $product->stock_quantity = max(0, $request->quantity);
                break;
        }
        
        $product->save();
        
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'stock_quantity' => $product->stock_quantity
        ]);
    }
} 