---
description: 
globs: 
alwaysApply: false
---
# Kiến trúc dữ liệu

## Database Entities

### Users
- Người dùng hệ thống (admin, nhân viên)
- Xác thực và phân quyền
- Liên kết với profiles (Admin, Staff)

### Members
- Hội viên tập gym
- Thông tin cá nhân, thông tin liên hệ
- Lịch sử gói tập, lịch tập

### Trainers
- Huấn luyện viên
- Chuyên môn, kinh nghiệm
- Lịch dạy, đánh giá

### Memberships
- Các gói tập
- Thời hạn, giá, quyền lợi

### Schedules
- Lịch tập của hội viên với PT
- Thời gian, địa điểm

### Products
- Sản phẩm bán tại quầy
- Giá, danh mục, tồn kho

### Orders
- Hóa đơn bán hàng
- Chi tiết hóa đơn

### Facilities
- Cơ sở vật chất
- Thiết bị, tình trạng

## Relationships
- Member - Membership: Many-to-Many (qua bảng member_memberships)
- Member - Trainer: Many-to-Many (qua bảng schedules)
- Order - Product: Many-to-Many (qua bảng order_items)
- Member - Order: One-to-Many

## Validation Rules
- [app/Rules/ValidMembershipPeriod.php](mdc:app/Rules/ValidMembershipPeriod.php): Kiểm tra thời hạn gói tập
