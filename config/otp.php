<?php

return [
    'test_mode' => (bool) env('OTP_TEST_MODE', true),
    'fixed_code' => (string) env('OTP_FIXED_CODE', '123456'),
    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 5),
];
