<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCourierPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $courierId;

    public string $title;

    public string $body;

    public array $data;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $data
     */
    public function __construct(int $courierId, string $title, string $body, array $data = [])
    {
        $this->courierId = $courierId;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            FcmService::sendToCourier(
                $this->courierId,
                $this->title,
                $this->body,
                $this->data
            );
        } catch (\Throwable $e) {
            Log::error("SendCourierPushNotificationJob Error untuk Kurir ID {$this->courierId}: {$e->getMessage()}");
        }
    }
}
