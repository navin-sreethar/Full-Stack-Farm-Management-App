<?php
/**
 * Authentication Service
 */

class Auth
{
    /**
     * Attempt login. Returns 'ok', 'pending', or 'invalid'.
     */
    public static function attempt(string $email, string $password): string
    {
        $stmt = App::db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return 'invalid';
        }

        if ($user['status'] === 'pending') {
            return 'pending';
        }

        if ($user['status'] !== 'active') {
            return 'invalid'; // suspended / inactive
        }

        // Store user in session
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['first_name'] ?? $user['email']);

        // Regenerate session
        session_regenerate_id(true);

        // Log login
        self::logLogin($user['id'], 'success');

        return 'ok';
    }

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => Session::get('user_id'),
            'role' => Session::get('user_role'),
            'email' => Session::get('user_email'),
            'name' => Session::get('user_name'),
        ];
    }

    public static function role(): string
    {
        return Session::get('user_role', '');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isFarmer(): bool
    {
        return self::role() === 'farmer';
    }

    public static function isOrgAdmin(): bool
    {
        return self::role() === 'org_admin';
    }

    /** Returns true if user can access farm management features (farmer OR org_admin) */
    public static function isFarmerOrOrg(): bool
    {
        return in_array(self::role(), ['farmer', 'org_admin']);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function hashPassword(string $password): string
    {
        $cost = App::config('bcrypt_cost', 12);
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    public static function register(array $data): int
    {
        $stmt = App::db()->prepare(
            'INSERT INTO users (email, phone, password_hash, role, first_name, last_name, org_name, org_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW())'
        );
        $stmt->execute([
            $data['email'],
            $data['phone']     ?? '',
            self::hashPassword($data['password']),
            $data['role']      ?? 'farmer',
            $data['first_name'] ?? '',
            $data['last_name']  ?? '',
            $data['org_name']   ?? null,
            $data['org_id']     ?? null,
        ]);
        return (int) App::db()->lastInsertId();
    }

    private static function logLogin(int $userId, string $status): void
    {
        try {
            $stmt = App::db()->prepare(
                'INSERT INTO login_logs (user_id, ip_address, user_agent, status, created_at) VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $status,
            ]);
        } catch (Exception $e) {
            Logger::error('Login log failed: ' . $e->getMessage());
        }
    }
}
