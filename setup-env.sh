#!/bin/bash

# Thiết lập PHP 8.2 cho dự án
export PATH="/usr/local/opt/php@8.2/bin:$PATH"
export PATH="/usr/local/opt/php@8.2/sbin:$PATH"

# Thông báo phiên bản PHP đang sử dụng
php -v

# Hiển thị thông tin về dự án
echo "===== FITVIET GYM MANAGEMENT SYSTEM ====="
echo "- Laravel: $(php artisan --version)"
echo "- Vue.js: $(grep '"vue"' package.json | head -n 1 | awk -F: '{ print $2 }' | sed 's/[",]//g')"
echo "- PHP: $(php -v | head -n 1)"
echo "- Node: $(node -v)"
echo "- NPM: $(npm -v)"
echo "=========================================="

# Hướng dẫn chạy dự án
echo "Để chạy dự án:"
echo "1. Terminal 1: bash -c 'source setup-env.sh && php artisan serve'"
echo "2. Terminal 2: bash -c 'source setup-env.sh && npm run dev'" 