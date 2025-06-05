---
description: 
globs: 
alwaysApply: false
---
# Quy ước và tiêu chuẩn code

## Backend (Laravel)
- Sử dụng Laravel 11+
- Áp dụng mô hình MVC
- Controllers chỉ xử lý logic control flow, không chứa business logic
- Services chứa business logic
- Repositories xử lý truy xuất dữ liệu
- Models định nghĩa relationships và scopes
- Rules được đặt trong app/Rules
- Policies được đặt trong app/Policies
- Resource Controllers cho API endpoints

## Frontend (Vue 3)
- Sử dụng Composition API và `<script setup>`
- State management với Pinia
- Vue Router với routing và middleware
- SFC (Single File Components)
- Tailwind CSS cho styling
- shadcn-vue cho UI components

## Quy ước đặt tên
- Controllers: PascalCase, hậu tố Controller (VD: MemberController)
- Models: PascalCase, số ít (VD: Member, Trainer)
- Vue Components: PascalCase (VD: MemberList, LoginForm)
- Methods: camelCase (VD: getUserData())
- Variables: camelCase (VD: userData)
- Database tables: snake_case, số nhiều (VD: members, trainers)

## Cấu trúc thư mục Vue
- layouts/: Layout chung cho ứng dụng
- pages/: Các trang chính
- components/: Components tái sử dụng
- stores/: Pinia stores
- router/: Vue Router
- utils/: Utility functions
