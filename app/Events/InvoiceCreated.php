<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreated implements ShouldBroadcastNow
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
            $channels[] = new PrivateChannel('user.'.$this->invoice->courier_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'invoice.created';
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
            'due_date' => $this->invoice->due_date?->toDateString(),
            'status' => $this->invoice->status,
            'status_label' => $this->invoice->status_label,
            'created_at' => $this->invoice->created_at?->toIso8601String(),
            'message' => "Faktur baru {$this->invoice->invoice_number} telah diterbitkan untuk toko {$this->invoice->store?->name}.",
        ];
    }
}
