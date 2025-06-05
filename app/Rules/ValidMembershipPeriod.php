<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMembershipPeriod implements ValidationRule
{
    /**
     * Kiểm tra xem thời hạn gói tập có hợp lệ hay không
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Các thời hạn gói tập hợp lệ (tính bằng tháng)
        $validPeriods = [1, 3, 6, 12];

        if (!in_array($value, $validPeriods)) {
            $fail('Thời hạn gói tập phải là 1, 3, 6 hoặc 12 tháng.');
        }
    }
} 