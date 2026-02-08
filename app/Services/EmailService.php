<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send welcome email to a new user.
     *
     * @param User $user
     * @return void
     */
    public function sendWelcomeEmail(User $user)
    {
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
            Log::info("Welcome email sent to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Error sending welcome email to {$user->email}: " . $e->getMessage());
        }
    }

    /**
     * Send login alert email.
     *
     * @param User $user
     * @param string $ip
     * @param string $userAgent
     * @return void
     */
    public function sendLoginAlert(User $user, $ip, $userAgent)
    {
        try {
            $time = now()->format('Y-m-d H:i:s');
            Mail::to($user->email)->send(new \App\Mail\LoginAlert($user, $ip, $userAgent, $time));
            Log::info("Login alert sent to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Error sending login alert to {$user->email}: " . $e->getMessage());
        }
    }

    /**
     * Send password recovery code.
     *
     * @param User $user
     * @param string $code
     * @return void
     */
    public function sendRecoveryCode(User $user, $code)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\RecoveryCodeEmail($user, $code));
            Log::info("Recovery code sent to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Error sending recovery code to {$user->email}: " . $e->getMessage());
        }
    }

    /**
     * Send password changed confirmation.
     *
     * @param User $user
     * @return void
     */
    public function sendPasswordChangedAlert(User $user)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\PasswordChangedEmail($user));
            Log::info("Password changed alert sent to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Error sending password changed alert to {$user->email}: " . $e->getMessage());
        }
    }

    /**
     * Send order confirmation email.
     *
     * @param User $user
     * @param mixed $pedido
     * @return void
     */
    public function sendOrderConfirmation(User $user, $pedido)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\OrderConfirmationEmail($user, $pedido));
            Log::info("Order confirmation sent to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Error sending order confirmation to {$user->email}: " . $e->getMessage());
        }
    }

    /**
     * Send abandoned cart reminder email.
     *
     * @param User $user
     * @param \App\Models\Carrito $carrito
     * @return void
     */
    public function sendAbandonedCartReminder(User $user, \App\Models\Carrito $carrito)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\AbandonedCartEmail($user, $carrito));
            Log::info("Abandoned cart reminder sent to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Error sending abandoned cart reminder to {$user->email}: " . $e->getMessage());
        }
    }
    /**
     * Send order status update email.
     *
     * @param User $user
     * @param \App\Models\Pedido $pedido
     * @param string $status
     * @return void
     */
    public function sendOrderStatusUpdate(User $user, $pedido, $status)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\OrderStatusEmail($user, $pedido, $status));
            Log::info("Order status update ({$status}) sent to: {$user->email}");
        } catch (\Exception $e) {
            Log::error("Error sending order status update ({$status}) to {$user->email}: " . $e->getMessage());
        }
    }
}
