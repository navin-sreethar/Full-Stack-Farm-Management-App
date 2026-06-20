<?php
/**
 * CSRF Token Protection
 */

class CSRF
{
    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::token() . '">';
    }

    public static function validate(): void
    {
        $token = $_POST['_csrf_token'] ?? '';
        if (!$token || !hash_equals(Session::get('_csrf_token', ''), $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
        // Regenerate token after use
        Session::set('_csrf_token', bin2hex(random_bytes(32)));
    }
}
