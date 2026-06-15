<?php

if (! function_exists('auth_role')) {
    function auth_role(): string
    {
        return session('firebase_user_role', '');
    }
}

if (! function_exists('auth_uid')) {
    function auth_uid(): string
    {
        return session('firebase_uid', '');
    }
}

if (! function_exists('auth_user_id')) {
    function auth_user_id(): int
    {
        return (int) session('firebase_user_id', 0);
    }
}
