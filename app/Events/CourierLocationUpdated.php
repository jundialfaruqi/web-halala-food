<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $deliveryId;
    public int $courierId;
    public string $courierName;
    public float $latitude;
    public float $longitude;
    public ?float $heading;
    public ?float $speed;
    public string $updatedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $deliveryId,
        int $courierId,
        string $courierName,
        float $latitude,
        float $longitude,
        ?float $heading = null,
        ?float $speed = null,
        ?string $updatedAt = null
    ) {
        $this->deliveryId = $deliveryId;
        $this->courierId = $courierId;
        $this->courierName = $courierName;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->heading = $heading;
        $this->speed = $speed;
        $this->updatedAt = $updatedAt ?? now()->toIso8601String();
    }

    /**
     * Broadcast on delivery channel and courier channel.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('delivery.'.$this->deliveryId),
            new PrivateChannel('courier.'.$this->courierId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'courier.location.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->deliveryId,
            'courier_id' => $this->courierId,
            'courier_name' => $this->courierName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'heading' => $this->heading,
            'speed' => $this->speed,
            'updated_at' => $this->updatedAt,
        ];
    }
}
