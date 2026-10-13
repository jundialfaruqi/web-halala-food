<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;

    /**
     * Create a new event instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('invoices'),
        ];

        if ($this->invoice->courier_id) {
            $channels[] = new PrivateChannel('courier.'.$this->invoice->courier_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'invoice.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'store_id' => $this->invoice->store_id,
            'store_name' => $this->invoice->store?->name,
            'courier_id' => $this->invoice->courier_id,
            'courier_name' => $this->invoice->courier?->name,
            'total_amount' => (float) $this->invoice->total_amount,
            'paid_amount' => (float) $this->invoice->paid_amount,
            'remaining_balance' => (float) $this->invoice->remaining_balance,
            'status' => $this->invoice->status,
            'status_label' => $this->invoice->status_label,
            'updated_at' => $this->invoice->updated_at?->toIso8601String(),
            'message' => "Faktur {$this->invoice->invoice_number} telah diperbarui (Status: {$this->invoice->status_label}).",
        ];
    }
}
