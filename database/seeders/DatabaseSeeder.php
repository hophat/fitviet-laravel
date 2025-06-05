<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạo tài khoản quản trị viên
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@fitviet.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        // Tạo tài khoản chủ phòng tập
        User::create([
            'name' => 'Owner User',
            'email' => 'owner@fitviet.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        // Tạo tài khoản nhân viên
        User::create([
            'name' => 'Staff User',
            'email' => 'staff@fitviet.com',
            'password' => 'password',
            'role' => 'staff',
        ]);

        // Tạo tài khoản hội viên
        User::create([
            'name' => 'Member User',
            'email' => 'member@fitviet.com',
            'password' => 'password',
            'role' => 'member',
        ]);

        // Tạo danh mục sản phẩm
        $categories = [
            ['name' => 'Thực phẩm bổ sung', 'description' => 'Các loại thực phẩm bổ sung dinh dưỡng'],
            ['name' => 'Đồ uống', 'description' => 'Nước uống năng lượng, nước khoáng'],
            ['name' => 'Trang phục', 'description' => 'Quần áo tập luyện'],
            ['name' => 'Phụ kiện', 'description' => 'Găng tay, đai lưng, băng đô'],
            ['name' => 'Thẻ tập', 'description' => 'Các loại thẻ tập gym'],
        ];

        foreach ($categories as $category) {
            ProductCategory::create($category);
        }

        // Tạo sản phẩm mẫu
        $products = [
            [
                'name' => 'Whey Protein 2kg',
                'price' => 1200000,
                'category_id' => 1,
                'description' => 'Bột protein hỗ trợ phát triển cơ bắp',
                'sku' => 'WHEY001',
                'stock_quantity' => 20,
                'image' => 'whey-protein.jpg'
            ],
            [
                'name' => 'BCAA 500g',
                'price' => 450000,
                'category_id' => 1,
                'description' => 'Hỗ trợ phục hồi cơ bắp sau tập luyện',
                'sku' => 'BCAA001',
                'stock_quantity' => 15,
                'image' => 'bcaa.jpg'
            ],
            [
                'name' => 'Nước uống năng lượng',
                'price' => 25000,
                'category_id' => 2,
                'description' => 'Đồ uống tăng lực trước tập luyện',
                'sku' => 'DRINK001',
                'stock_quantity' => 50,
                'image' => 'energy-drink.jpg'
            ],
            [
                'name' => 'Áo tập gym nam',
                'price' => 350000,
                'category_id' => 3,
                'description' => 'Áo thể thao thoáng khí cho nam',
                'sku' => 'APPAREL001',
                'stock_quantity' => 30,
                'image' => 'gym-shirt.jpg'
            ],
            [
                'name' => 'Găng tay tập gym',
                'price' => 150000,
                'category_id' => 4,
                'description' => 'Găng tay bảo vệ lòng bàn tay khi tập',
                'sku' => 'GLOVES001',
                'stock_quantity' => 25,
                'image' => 'gym-gloves.jpg'
            ],
            [
                'name' => 'Thẻ tập 1 tháng',
                'price' => 500000,
                'category_id' => 5,
                'description' => 'Thẻ tập không giới hạn trong 1 tháng',
                'sku' => 'MEMB001',
                'stock_quantity' => 100,
                'image' => 'membership-card.jpg'
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Tạo các loại gói tập
        $memberships = [
            [
                'name' => 'Gói 1 tháng',
                'price' => 500000,
                'duration' => 30,
                'description' => 'Tập không giới hạn thời gian trong 1 tháng',
            ],
            [
                'name' => 'Gói 3 tháng',
                'price' => 1300000,
                'duration' => 90,
                'description' => 'Tập không giới hạn thời gian trong 3 tháng',
            ],
            [
                'name' => 'Gói 6 tháng',
                'price' => 2400000,
                'duration' => 180,
                'description' => 'Tập không giới hạn thời gian trong 6 tháng',
            ],
            [
                'name' => 'Gói 12 tháng',
                'price' => 4300000,
                'duration' => 365,
                'description' => 'Tập không giới hạn thời gian trong 12 tháng',
            ],
        ];

        foreach ($memberships as $membership) {
            Membership::create($membership);
        }

        // Tạo hội viên mẫu
        $members = [
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'phone' => '0901234567',
                'address' => 'Quận 1, TP.HCM',
                'birthday' => '1990-01-01',
                'gender' => 'male',
                'join_date' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'Trần Thị B',
                'email' => 'tranthib@example.com',
                'phone' => '0911234567',
                'address' => 'Quận 2, TP.HCM',
                'birthday' => '1992-05-10',
                'gender' => 'female',
                'join_date' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'Phạm Văn C',
                'email' => 'phamvanc@example.com',
                'phone' => '0921234567',
                'address' => 'Quận 3, TP.HCM',
                'birthday' => '1988-12-20',
                'gender' => 'male',
                'join_date' => now(),
                'status' => 'active',
            ],
        ];

        foreach ($members as $member) {
            Member::create($member);
        }
    }
}
