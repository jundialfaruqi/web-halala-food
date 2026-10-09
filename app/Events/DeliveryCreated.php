<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Delivery $delivery;

    /**
     * Create a new event instance.
     */
    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('deliveries'),
        ];

        if ($this->delivery->courier_id) {
            $channels[] = new PrivateChannel('courier.'.$this->delivery->courier_id);
            $channels[] = new PrivateChannel('user.'.$this->delivery->courier_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'delivery.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->delivery->id,
            'delivery_number' => $this->delivery->delivery_number,
            'store_id' => $this->delivery->store_id,
            'store_name' => $this->delivery->store?->name,
            'courier_id' => $this->delivery->courier_id,
            'courier_name' => $this->delivery->courier?->name,
            'delivery_date' => $this->delivery->delivery_date?->toDateString(),
            'status' => $this->delivery->status,
            'status_label' => $this->delivery->status_label,
            'total_items' => (int) $this->delivery->total_items,
            'total_amount' => (float) $this->delivery->total_amount,
            'created_at' => $this->delivery->created_at?->toIso8601String(),
            'message' => "Surat jalan baru {$this->delivery->delivery_number} untuk toko {$this->delivery->store?->name} telah ditugaskan.",
        ];
    }
}
