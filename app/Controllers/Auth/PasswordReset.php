<?php

namespace App\Controllers\Auth;

use App\Libraries\Sendmail;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class PasswordReset extends BaseController
{
    /**
     * Display the password reset request page
     *
     * Shows the form where the user enters their email address to request a
     * password reset link. Performs garbage collection on expired reset records.
     *
     * @return string Rendered view
     */
    public function index()
    {
        // Garbage-collect password reset records older than 1 hour
        $model = model('PasswordResetModel');
        $model->where('created_at <', date('Y-m-d H:i:s', strtotime('-1 hour')))->delete();

        $data['js'] = [
            'auth/password-reset',
        ];
        $data['title'] = 'Reset Password';
        $data['templateMaxWidth'] = '330px';
        return view('auth/password-reset', $data);
    }

    /**
     * Process a password reset request
     *
     * Looks up the submitted email in the users table. If found, creates a reset
     * record and emails a one-time link to the user. The response is identical
     * whether or not the email exists, to prevent enumeration of registered accounts.
     *
     * Expected JSON input:
     * - email: string (required)
     * - csrf_token: string (required)
     *
     * HTTP Status Codes:
     * - 200: Request processed (always, even if email not found)
     * - 400: Missing or invalid email
     * - 403: Invalid CSRF token
     */
    public function request()
    {
        $data       = $this->request->getJSON(true);
        $email      = $data['email'] ?? null;
        $csrf_token = $data[csrf_token()] ?? null;

        // Validate CSRF token
        if (!hash_equals(csrf_hash(), $csrf_token)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid CSRF token']);
        }

        // Validate email presence and format
        if (empty($email)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Please enter your email address']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid email address']);
        }

        // Look up the user — only validated, non-banned accounts can reset
        $userModel = model('UserModel');
        $user = $userModel->where('email', $email)
                          ->where('validated', 1)
                          ->where('banned', 0)
                          ->first();

        if ($user) {
            // Invalidate any existing reset records for this user
            $resetModel = model('PasswordResetModel');
            $resetModel->where('user_id', $user['id'])->delete();

            // Create a new reset record
            $resetUuid = Uuid::uuid4()->toString();
            $resetModel->insert([
                'user_id'   => $user['id'],
                'uuid'      => $resetUuid,
                'user_uuid' => $user['uuid'],
                'email'     => $email,
            ]);

            logit("Password reset requested for: {$email}", 1);

            // Send the reset email
            $body = view('emails/auth-password-reset', [
                'reset_uuid' => $resetUuid,
            ]);
            try {
                (new Sendmail())
                    ->setFrom(config('Email')->fromEmail)
                    ->setTo($email)
                    ->setSubject('Reset your password')
                    ->setBody($body)
                    ->setMailtype(Sendmail::MAILTYPE_HTML)
                    ->send();
            } catch (InvalidArgumentException $e) {
                log_message('error', $e->getMessage());
            } catch (RuntimeException $e) {
                log_message('error', $e->getMessage());
            }
        }

        // Always return the same response to prevent email enumeration
        return $this->response->setStatusCode(200)->setJSON(['success' => true]);
    }

    /**
     * Display the password reset confirmation page
     *
     * Validates the UUID from the reset link. If valid, shows the new-password
     * form. If invalid or expired, shows an error message.
     *
     * @param string $uuid The reset token from the email link
     * @return string Rendered view
     */
    public function confirm(string $uuid)
    {
        $data['js'] = [
            'auth/password-reset-confirm',
        ];
        $data['css'] = [
            'auth/password-requirements',
        ];
        $data['title'] = 'Set New Password';
        $data['templateMaxWidth'] = '330px';
        // Basic UUID format validation before hitting the DB
        if (!Uuid::isValid($uuid)) {
            $data['valid']   = false;
            $data['message'] = 'This reset link is invalid.';
            return view('auth/password-reset-confirm', $data);
        }

        $resetModel = model('PasswordResetModel');
        $reset = $resetModel->where('uuid', $uuid)
                            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                            ->first();

        if (!$reset) {
            $data['valid']   = false;
            $data['message'] = 'This reset link is invalid or has expired. Please <a href="/auth/password-reset">request a new one</a>.';
            return view('auth/password-reset-confirm', $data);
        }

        $data['valid'] = true;
        $data['uuid']  = $uuid;
        return view('auth/password-reset-confirm', $data);
    }

    /**
     * Process the new password submission
     *
     * Validates the reset token and password, updates the user's password, and
     * invalidates the reset record so the link cannot be reused.
     *
     * Expected JSON input:
     * - uuid: string (required) — the reset token
     * - password: string (required)
     * - password_confirm: string (required)
     * - csrf_token: string (required)
     *
     * HTTP Status Codes:
     * - 200: Password updated successfully
     * - 400: Validation error or expired/invalid token
     * - 403: Invalid CSRF token
     */
    public function update()
    {
        $data             = $this->request->getJSON(true);
        $uuid             = $data['uuid'] ?? null;
        $password         = $data['password'] ?? null;
        $password_confirm = $data['password_confirm'] ?? null;
        $csrf_token       = $data[csrf_token()] ?? null;

        // Validate CSRF token
        if (!hash_equals(csrf_hash(), $csrf_token)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Invalid CSRF token']);
        }

        // Validate required fields
        if (empty($uuid) || empty($password) || empty($password_confirm)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'All fields are required']);
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

        // Validate UUID format before DB lookup
        if (!Uuid::isValid($uuid)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid reset token']);
        }

        // Verify the reset record is still valid
        $resetModel = model('PasswordResetModel');
        $reset = $resetModel->where('uuid', $uuid)
                            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                            ->first();

        if (!$reset) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'This reset link is invalid or has expired. Please <a href="/password-reset">request a new one</a>.']);
        }

        // Update the user's password
        $userModel = model('UserModel');
        $userModel->update($reset['user_id'], [
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        // Invalidate the reset record so it cannot be reused
        $resetModel->delete($reset['id']);

        logit("Password reset completed for: {$reset['email']}", 0);

        return $this->response->setStatusCode(200)->setJSON(['success' => true]);
    }
}
