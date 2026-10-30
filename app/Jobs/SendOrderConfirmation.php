<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit = true;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->order->loadMissing('customer');

        Log::info('Order confirmation email simulated.', [
            'order_id' => $this->order->id,
            'customer_email' => $this->order->customer->email,
            'grand_total' => $this->order->grand_total,
        ]);
    }
}
