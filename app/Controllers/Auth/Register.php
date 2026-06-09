<?php
namespace App\Controllers\Auth;

use App\Libraries\Sendmail;
use Ramsey\Uuid\Uuid;

class Register extends BaseController
{
    public function index()
    {
        // Test if user is already logged in and redirect
        if ($this->session->get('user_uuid')) {
            return redirect()->to(site_url());
        }
        $data['title'] = 'Register';
        $data['css'] = ['auth/password-requirements'];
        $data['js'] = ['auth/register'];
        $data['templateMaxWidth'] = '330px';
        $data['registrationDisabled'] = ! config('App')->allowNewUserRegistration;
        return view('auth/register', $data);
    }

    /**
     * Process user registration
     *
     * Handles the registration process by validating input, creating a new user
     * account, and optionally assigning the user to the administrators group.
     *
     * This method:
     * - Validates CSRF token for security
     * - Validates all required fields (email, username, password, password confirmation)
     * - Checks for duplicate email or username
     * - Hashes the password using PASSWORD_DEFAULT
     * - If this is the first registered user, marks them as validated and adds
     *   them to the administrators group
     * - Returns a redirect to the login page on success
     *
     * @return ResponseInterface JSON response with redirect URL on success, or error message on failure
     *
     * Expected JSON input structure:
     * - email: string (required)
     * - username: string (required)
     * - realname: string (optional)
     * - password: string (required)
     * - password_confirm: string (required)
     * - csrf_token: string (required)
     *
     * HTTP Status Codes:
     * - 200: Registration successful with redirect URL
     * - 400: Validation error (missing fields, duplicate email/username, password mismatch)
     * - 403: Invalid CSRF token
     */
    public function verify()
    {
        $data['title'] = 'Verify Your Email';
        $data['templateMaxWidth'] = '330px';
        $data['noreplyEmail'] = config('Email')->fromEmail;
        return view('auth/register-verify', $data);
    }

    public function process()
    {
        // Block registration when disabled via config
        if (! config('App')->allowNewUserRegistration) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'New user registration is currently disabled']);
        }

        // Capture JSON data
        $data = $this->request->getJSON(true);
        $email            = $data['email'] ?? null;
        $username         = $data['username'] ?? null;
        $realname         = $data['realname'] ?? '';
        $password         = $data['password'] ?? null;
        $password_confirm = $data['password_confirm'] ?? null;
        $csrf_token       = $data[csrf_token()] ?? null;

        // Validate CSRF token
        if (!hash_equals(csrf_hash(), $csrf_token)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid CSRF token']);
        }

        // Validate required fields
        if (empty($email) || empty($username) || empty($password) || empty($password_confirm)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'All fields are required']);
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid email address']);
        }

        // Validate passwords match
        if ($password !== $password_confirm) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Passwords do not match']);
        }

        // Validate password strength
        if (strlen($password) < 12) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Password must be at least 12 characters long']);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Password must contain at least one uppercase letter']);
        }
        if (!preg_match('/[a-z]/', $password)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Password must contain at least one lowercase letter']);
        }
        if (!preg_match('/[0-9]/', $password)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Password must contain at least one number']);
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Password must contain at least one special character']);
        }

        $userModel = model('UserModel');

        // Check if email is already registered
        if ($userModel->where('email', $email)->where('validated', 1)->first()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'That email address is already registered']);
        }

        // Check if username is already taken
        if ($userModel->where('username', $username)->first()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'That username is already taken']);
        }

        // Determine if this is the first user being registered
        $isFirstUser = $userModel->countAllResults() === 0;

        // Generate a UUID for the new user
        $userUuid = Uuid::uuid4()->toString();

        // Create the new user record
        $userModel->insert([
            'uuid'      => $userUuid,
            'email'     => $email,
            'username'  => $username,
            'realname'  => $realname,
            'password'  => password_hash($password, PASSWORD_DEFAULT),
            'validated' => $isFirstUser ? 1 : 0,
            'banned'    => 0,
        ]);

        $logAction = $isFirstUser ? 'admin account created' : 'account registered (pending verification)';
        logit("New user {$logAction}: {$email} ({$username})", 0);

        // If this is the first user, add them to the administrators group
        if ($isFirstUser) {
            $groupModel = model('GroupModel');
            $groupModel->insert([
                'uuid'      => Uuid::uuid4()->toString(),
                'user_uuid' => $userUuid,
                'group'     => 'administrators',
            ]);
        } else {
            // Load email view and pass data
            $body = view('emails/auth-verify-account', ['user_uuid' => $userUuid]);
            // Send verification email to the user
            $result = (new Sendmail())
                ->setFrom(config('Email')->fromEmail)
                ->setTo($email)
                ->setSubject('Validate your email address')
                ->setBody($body)
                ->setMailtype(Sendmail::MAILTYPE_HTML)
                ->send();
        }

        $message = $isFirstUser
            ? 'Administrator account created successfully. You may now log in.'
            : 'Registration successful. Please check your email to verify your account.';

        return $this->response->setStatusCode(200)->setJSON([
            'redirect'  => $isFirstUser ? '/auth/' : null,
            'validated' => $isFirstUser,
            'message'   => $message,
        ]);
    }
}