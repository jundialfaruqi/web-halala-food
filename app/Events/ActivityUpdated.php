<?php

namespace App\Events;

use App\Models\Activity;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Activity $activity;

    /**
     * Create a new event instance.
     */
    public function __construct(Activity $activity)
    {
        $this->activity = $activity->loadMissing(['user', 'courier']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('activities'),
        ];

        if ($this->activity->courier_id) {
            $channels[] = new PrivateChannel('courier.' . $this->activity->courier_id);
        }

        if ($this->activity->user_id && $this->activity->user_id !== $this->activity->courier_id) {
            $user = $this->activity->user;
            if (! $user || ! $user->hasAnyRole(['dev', 'manager'])) {
                $channels[] = new PrivateChannel('user.' . $this->activity->user_id);
            }
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'activity.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->activity->id,
            'type' => $this->activity->type,
            'title' => $this->activity->title,
            'description' => $this->activity->description,
            'user_id' => $this->activity->user_id,
            'user_name' => $this->activity->user?->name ?? 'User',
            'user_role' => $this->activity->user?->roles?->first()?->name ?? 'Staf',
            'user_initials' => $this->activity->user_initials,
            'user_avatar' => $this->activity->user?->avatar_url,
            'courier_id' => $this->activity->courier_id,
            'courier_name' => $this->activity->courier?->name,
            'subject_type' => $this->activity->subject_type,
            'subject_id' => $this->activity->subject_id,
            'data' => $this->activity->data,
            'is_deleted' => (bool) ($this->activity->data['is_deleted'] ?? false),
            'deletion_message' => $this->activity->data['deletion_message'] ?? null,
            'created_at' => $this->activity->created_at?->toISOString(),
            'time_formatted' => $this->activity->time_formatted,
            'date_formatted' => $this->activity->date_formatted,
            'date_group_key' => $this->activity->date_group_key,
        ];
    }
}
