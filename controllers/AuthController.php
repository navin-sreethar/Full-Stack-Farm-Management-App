<?php
/**
 * Auth Controller
 */

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->viewOnly('auth/login', [
            'email' => '',
            'error' => Session::getFlash('error'),
        ]);
    }

    public function login(): void
    {
        $email    = $this->input('email');
        $password = $this->input('password');

        if (empty($email) || empty($password)) {
            $this->viewOnly('auth/login', [
                'email' => $email,
                'error' => 'Please fill in all fields.',
            ]);
            return;
        }

        $result = Auth::attempt($email, $password);

        if ($result === 'ok') {
            Logger::info("User logged in: {$email}");
            $this->redirect('/dashboard', 'Welcome back!');
        } elseif ($result === 'pending') {
            Logger::info("Login blocked — account pending approval: {$email}");
            $this->viewOnly('auth/login', [
                'email' => $email,
                'error' => 'Your account is pending admin approval. You will receive an email once a decision has been made.',
            ]);
        } else {
            Logger::warning("Failed login attempt: {$email}");
            $this->viewOnly('auth/login', [
                'email' => $email,
                'error' => 'Invalid email or password.',
            ]);
        }
    }

    public function registerForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->viewOnly('auth/register', ['error' => '', 'success' => '']);
    }

    public function register(): void
    {
        $data = $this->allInput();

        // Sanitize role — only allow 'farmer' or 'org_admin' (never 'admin' from form)
        $role = ($data['role'] ?? '') === 'org_admin' ? 'org_admin' : 'farmer';

        // Validation
        if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['phone'])) {
            $this->viewOnly('auth/register', array_merge($data, [
                'error'   => 'Please fill in all required fields.',
                'success' => '',
            ]));
            return;
        }

        if ($role === 'org_admin' && empty(trim($data['org_name'] ?? ''))) {
            $this->viewOnly('auth/register', array_merge($data, [
                'error'   => 'Organization name is required for organization accounts.',
                'success' => '',
            ]));
            return;
        }

        if (strlen($data['password']) < 6) {
            $this->viewOnly('auth/register', array_merge($data, [
                'error'   => 'Password must be at least 6 characters.',
                'success' => '',
            ]));
            return;
        }

        if ($data['password'] !== $data['password_confirm']) {
            $this->viewOnly('auth/register', array_merge($data, [
                'error'   => 'Passwords do not match.',
                'success' => '',
            ]));
            return;
        }

        // Check duplicate email
        $stmt = App::db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $this->viewOnly('auth/register', array_merge($data, [
                'error'   => 'An account with this email already exists.',
                'success' => '',
            ]));
            return;
        }

        // Build phone
        $phoneCountry = $data['phone_country'] ?? '+1';
        $phoneCountry = str_replace('-CA', '', $phoneCountry);
        $phoneNumber  = $data['phone'] ?? '';
        $fullPhone    = $phoneNumber ? $phoneCountry . ' ' . $phoneNumber : '';

        $orgName = trim($data['org_name'] ?? '');

        // Create user (status = 'pending' by default via Auth::register)
        $userId = Auth::register([
            'email'      => $data['email'],
            'phone'      => $fullPhone,
            'password'   => $data['password'],
            'role'       => $role,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'] ?? '',
            'org_name'   => $orgName ?: null,
        ]);

        // Create farmer profile for both farmer and org_admin accounts
        $stmt = App::db()->prepare(
            'INSERT INTO farmers (user_id, first_name, last_name, phone, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $data['first_name'], $data['last_name'] ?? '', $fullPhone]);

        Logger::info("New {$role} registration pending approval: {$data['email']}");

        // Send "pending approval" email to applicant
        $this->sendPendingEmail($data['email'], $data['first_name'], $role, $orgName);

        // Notify admin
        $this->notifyAdminNewRequest($data['first_name'], $data['last_name'] ?? '', $data['email'], $fullPhone, $role, $orgName);

        $successMsg = $role === 'org_admin'
            ? 'Thank you for registering your organization! Your application is currently <strong>pending approval</strong>. Once approved, you can start adding farmers to your team.'
            : 'Thank you for registering! Your application is currently <strong>pending approval</strong>. You will receive an email confirmation within <strong>24–48 hours</strong>.';

        // Show success page (no auto-login)
        $this->viewOnly('auth/register', [
            'success' => $successMsg,
            'error'   => '',
        ]);
    }

    public function logout(): void
    {
        $user  = Auth::user();
        $email = $user ? ($user['email'] ?? '') : '';
        Logger::info("User logged out: " . $email);
        Auth::logout();
        $this->redirect('/login', 'You have been logged out.');
    }

    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    private function sendPendingEmail(string $toEmail, string $firstName, string $role = 'farmer', string $orgName = ''): void
    {
        try {
            $mailer = new Mailer();

            $subject = "Your FarmManager Account is Pending Approval";

            $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0d1117;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0d1117;padding:40px 20px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#161b22;border-radius:16px;overflow:hidden;border:1px solid #30363d;">
        <!-- Header -->
        <tr><td style="background:linear-gradient(135deg,#2d6a4f,#40916c);padding:40px 40px 30px;text-align:center;">
          <div style="font-size:48px;margin-bottom:10px;">🌿</div>
          <h1 style="color:#fff;margin:0;font-size:26px;font-weight:700;">FarmManager</h1>
          <p style="color:rgba(255,255,255,0.8);margin:8px 0 0;font-size:14px;">by FarmManager</p>
        </td></tr>
        <!-- Body -->
        <tr><td style="padding:40px;">
          <h2 style="color:#e6edf3;font-size:20px;margin:0 0 16px;">Hi {$firstName},</h2>
          <p style="color:#8b949e;font-size:15px;line-height:1.7;margin:0 0 20px;">
            Thank you for registering with <strong style="color:#e6edf3;">FarmManager</strong>! 🎉
          </p>
          <p style="color:#8b949e;font-size:15px;line-height:1.7;margin:0 0 24px;">
            Your account application has been received and is currently <strong style="color:#f0a500;">pending review</strong> by our admin team.
            You can expect a decision within <strong style="color:#e6edf3;">24 to 48 hours</strong>.
          </p>
          <!-- Status box -->
          <div style="background:#1c2128;border:1px solid #f0a500;border-radius:12px;padding:20px 24px;margin:0 0 28px;text-align:center;">
            <span style="font-size:28px;">⏳</span>
            <p style="color:#f0a500;font-size:16px;font-weight:600;margin:8px 0 4px;">Account Status: Pending Approval</p>
            <p style="color:#8b949e;font-size:13px;margin:0;">Decision expected within 24–48 hours</p>
          </div>
          <p style="color:#8b949e;font-size:14px;line-height:1.7;margin:0 0 8px;">
            Once approved, you'll receive another email with your login link. If your application is denied, you will also be notified with a reason.
          </p>
          <p style="color:#8b949e;font-size:14px;line-height:1.7;margin:0;">
            Questions? Reply to this email and we'll be happy to help.
          </p>
        </td></tr>
        <!-- Footer -->
        <tr><td style="background:#0d1117;padding:20px 40px;text-align:center;border-top:1px solid #30363d;">
          <p style="color:#484f58;font-size:12px;margin:0;">© 2025 FarmManager — FarmManager</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

            $plain = "Hi {$firstName},\n\nThank you for registering with FarmManager!\n\nYour account is currently PENDING APPROVAL by our admin team. You can expect a decision within 24–48 hours.\n\nOnce a decision is made, you'll receive another email.\n\n— FarmManager";

            $mailer->send($toEmail, $subject, $html, $plain);
        } catch (\Exception $e) {
            Logger::error("Failed to send pending email to {$toEmail}: " . $e->getMessage());
        }
    }

    private function notifyAdminNewRequest(string $firstName, string $lastName, string $email, string $phone, string $role = 'farmer', string $orgName = ''): void
    {
        try {
            $cfg = require __DIR__ . '/../config/mail.php';
            $adminEmail = $cfg['admin_email'] ?? $cfg['from_email'] ?? '';
            if (!$adminEmail) return;

            $mailer     = new Mailer();
            $approveUrl = rtrim(App::baseUrl() ?: '', '/') . (App::baseUrl() ? '/index.php' : '') . '/admin/approvals';
            $typeLabel  = $role === 'org_admin' ? '🏢 Organization' : '🌿 Individual Farmer';
            $orgRow     = $role === 'org_admin' && $orgName
                ? "<tr><td style='padding:8px 0;color:#8b949e;font-size:13px;width:120px;'>Org Name</td><td style='color:#58a6ff;font-size:14px;font-weight:600;'>{$orgName}</td></tr>"
                : '';
            $subject = "🆕 New Account Request — {$firstName} {$lastName}";

            $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0d1117;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0d1117;padding:40px 20px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#161b22;border-radius:16px;overflow:hidden;border:1px solid #30363d;">
        <tr><td style="background:linear-gradient(135deg,#1a3a5c,#1a6ea8);padding:36px 40px 28px;text-align:center;">
          <div style="font-size:42px;">🔔</div>
          <h1 style="color:#fff;margin:10px 0 0;font-size:22px;">New Account Request</h1>
          <span style="display:inline-block;margin-top:8px;padding:4px 14px;border-radius:20px;background:rgba(255,255,255,0.15);color:#fff;font-size:13px;">{$typeLabel}</span>
        </td></tr>
        <tr><td style="padding:36px 40px;">
          <p style="color:#8b949e;font-size:15px;margin:0 0 24px;">A new user has submitted a registration request:</p>
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#1c2128;border-radius:10px;padding:20px;border:1px solid #30363d;">
            <tr><td style="padding:8px 0;color:#8b949e;font-size:13px;width:120px;">Name</td><td style="color:#e6edf3;font-size:14px;font-weight:600;">{$firstName} {$lastName}</td></tr>
            <tr><td style="padding:8px 0;color:#8b949e;font-size:13px;">Email</td><td style="color:#58a6ff;font-size:14px;">{$email}</td></tr>
            <tr><td style="padding:8px 0;color:#8b949e;font-size:13px;">Phone</td><td style="color:#e6edf3;font-size:14px;">{$phone}</td></tr>
            {$orgRow}
          </table>
          <div style="text-align:center;margin-top:32px;">
            <a href="{$approveUrl}" style="display:inline-block;background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-size:15px;font-weight:600;">Review Account Request →</a>
          </div>
        </td></tr>
        <tr><td style="background:#0d1117;padding:16px 40px;text-align:center;border-top:1px solid #30363d;">
          <p style="color:#484f58;font-size:12px;margin:0;">FarmManager Admin Notification</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
            $plain = "New {$typeLabel} Request\n\nName: {$firstName} {$lastName}\nEmail: {$email}\nPhone: {$phone}" . ($orgName ? "\nOrg: {$orgName}" : '') . "\n\nReview at: {$approveUrl}";
            $mailer->send($adminEmail, $subject, $html, $plain);
        } catch (\Exception $e) {
            Logger::error("Failed to send admin notification: " . $e->getMessage());
        }
    }
}
