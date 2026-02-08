<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrito;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAbandonedCartReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-abandoned-cart-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to users with abandoned carts older than 24 hours';

    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting abandoned cart check...');

        // Find carts updated more than 24 hours ago but less than 48 hours (to avoid spamming everyday)
        // And that have items
        $abandonedCarts = Carrito::where('updated_at', '<', Carbon::now()->subHours(24))
            ->where('updated_at', '>', Carbon::now()->subHours(48)) // Only send once in this window
            ->whereHas('items')
            ->with(['usuario', 'items.variante.producto.imagenes'])
            ->get();

        $count = 0;

        foreach ($abandonedCarts as $cart) {
            if ($cart->usuario && $cart->usuario->email) {
                try {
                    $this->emailService->sendAbandonedCartReminder($cart->usuario, $cart);
                    $this->info("Email sent to user: {$cart->usuario->id}");
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Failed to send abandoned cart email to user {$cart->usuario->id}: " . $e->getMessage());
                    $this->error("Failed asking user {$cart->usuario->id}");
                }
            }
        }

        $this->info("Process complete. Sent {$count} reminders.");
    }
}
