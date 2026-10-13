<?php

namespace App\Services;

use App\Events\ActivityCreated;
use App\Events\ActivityUpdated;
use App\Models\Activity;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityService
{
    /**
     * Catat aktifitas pembuatan surat jalan / pengantaran baru.
     */
    public static function logDeliveryCreated(Delivery $delivery, ?User $actor = null): Activity
    {
        $actor = $actor ?? Auth::user();
        $storeName = $delivery->store?->name ?? 'Toko Mitra';
        $courierName = $delivery->courier?->name ?? 'Belum ditugaskan';

        $activity = Activity::create([
            'type' => 'delivery_created',
            'title' => 'Surat Pengiriman Dibuat',
            'description' => "Surat jalan {$delivery->delivery_number} dibuat untuk {$storeName}.",
            'user_id' => $actor?->id ?? $delivery->created_by ?? 1,
            'courier_id' => $delivery->courier_id,
            'subject_type' => Delivery::class,
            'subject_id' => $delivery->id,
            'data' => [
                'delivery_id' => $delivery->id,
                'delivery_number' => $delivery->delivery_number,
                'store_id' => $delivery->store_id,
                'store_name' => $storeName,
                'courier_id' => $delivery->courier_id,
                'courier_name' => $courierName,
                'total_items' => $delivery->total_items ?? 0,
                'delivery_date' => $delivery->delivery_date?->toDateString(),
                'status' => $delivery->status,
            ],
        ]);

        self::broadcastActivity($activity);

        return $activity;
    }

    /**
     * Catat aktifitas surat pengantaran diberangkatkan (status dikirim).
     */
    public static function logDeliveryDispatched(Delivery $delivery, ?User $actor = null): Activity
    {
        $actor = $actor ?? Auth::user();
        $storeName = $delivery->store?->name ?? 'Toko Mitra';
        $courierName = $delivery->courier?->name ?? 'Kurir';

        $activity = Activity::create([
            'type' => 'delivery_dispatched',
            'title' => 'Pengantaran Diberangkatkan',
            'description' => "Surat jalan {$delivery->delivery_number} sedang dalam perjalanan ke {$storeName}.",
            'user_id' => $actor?->id ?? $delivery->courier_id ?? 1,
            'courier_id' => $delivery->courier_id,
            'subject_type' => Delivery::class,
            'subject_id' => $delivery->id,
            'data' => [
                'delivery_id' => $delivery->id,
                'delivery_number' => $delivery->delivery_number,
                'store_id' => $delivery->store_id,
                'store_name' => $storeName,
                'courier_id' => $delivery->courier_id,
                'courier_name' => $courierName,
                'dispatched_at' => $delivery->dispatched_at?->toIso8601String() ?? now()->toIso8601String(),
                'status' => $delivery->status,
            ],
        ]);

        self::broadcastActivity($activity);

        return $activity;
    }

    /**
     * Catat aktifitas pengantaran selesai diserahterimakan.
     */
    public static function logDeliveryCompleted(Delivery $delivery, ?User $actor = null): Activity
    {
        $actor = $actor ?? Auth::user();
        $storeName = $delivery->store?->name ?? 'Toko Mitra';
        $courierName = $delivery->courier?->name ?? 'Kurir';
        $recipient = $delivery->recipient_name ? " kepada {$delivery->recipient_name}" : '';

        $activity = Activity::create([
            'type' => 'delivery_completed',
            'title' => 'Pengantaran Selesai',
            'description' => "Surat jalan {$delivery->delivery_number} berhasil diserahterimakan{$recipient} di {$storeName}.",
            'user_id' => $actor?->id ?? $delivery->courier_id ?? 1,
            'courier_id' => $delivery->courier_id,
            'subject_type' => Delivery::class,
            'subject_id' => $delivery->id,
            'data' => [
                'delivery_id' => $delivery->id,
                'delivery_number' => $delivery->delivery_number,
                'store_id' => $delivery->store_id,
                'store_name' => $storeName,
                'courier_id' => $delivery->courier_id,
                'courier_name' => $courierName,
                'recipient_name' => $delivery->recipient_name,
                'delivered_at' => $delivery->delivered_at?->toIso8601String() ?? now()->toIso8601String(),
                'status' => $delivery->status,
            ],
        ]);

        self::broadcastActivity($activity);

        return $activity;
    }

    /**
     * Catat aktifitas penerbitan faktur tagihan baru.
     */
    public static function logInvoiceCreated(Invoice $invoice, ?User $actor = null): Activity
    {
        $actor = $actor ?? Auth::user();
        $storeName = $invoice->store?->name ?? 'Toko Mitra';
        $courierName = $invoice->courier?->name;

        $activity = Activity::create([
            'type' => 'invoice_created',
            'title' => 'Surat Faktur Diterbitkan',
            'description' => "Faktur tagihan {$invoice->invoice_number} senilai Rp ".number_format($invoice->total_amount, 0, ',', '.')." diterbitkan untuk {$storeName}.",
            'user_id' => $actor?->id ?? $invoice->created_by ?? 1,
            'courier_id' => $invoice->courier_id,
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'store_id' => $invoice->store_id,
                'store_name' => $storeName,
                'courier_id' => $invoice->courier_id,
                'courier_name' => $courierName,
                'total_amount' => (float) $invoice->total_amount,
                'remaining_balance' => (float) $invoice->remaining_balance,
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'status' => $invoice->status,
            ],
        ]);

        self::broadcastActivity($activity);

        return $activity;
    }

    /**
     * Catat aktifitas pelunasan faktur tagihan.
     */
    public static function logInvoicePaid(Invoice $invoice, ?InvoicePayment $payment = null, ?User $actor = null): Activity
    {
        $actor = $actor ?? Auth::user();
        $storeName = $invoice->store?->name ?? 'Toko Mitra';
        $paymentAmount = $payment ? (float) $payment->amount : (float) $invoice->paid_amount;
        $paymentMethod = $payment?->payment_method ?? 'Tunai';

        $activity = Activity::create([
            'type' => 'invoice_paid',
            'title' => 'Faktur Telah Lunas',
            'description' => "Tagihan faktur {$invoice->invoice_number} di {$storeName} senilai Rp ".number_format($invoice->total_amount, 0, ',', '.').' telah lunas.',
            'user_id' => $actor?->id ?? $payment?->user_id ?? $invoice->created_by ?? 1,
            'courier_id' => $invoice->courier_id,
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'store_id' => $invoice->store_id,
                'store_name' => $storeName,
                'courier_id' => $invoice->courier_id,
                'courier_name' => $invoice->courier?->name,
                'total_amount' => (float) $invoice->total_amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'payment_amount' => $paymentAmount,
                'payment_method' => $paymentMethod,
                'status' => $invoice->status,
            ],
        ]);

        self::broadcastActivity($activity);

        return $activity;
    }

    /**
     * Tandai log aktifitas yang berkaitan dengan faktur yang dihapus.
     */
    public static function markInvoiceDeleted(Invoice $invoice, ?User $actor = null): int
    {
        $actor = $actor ?? auth('api')->user() ?? Auth::user();
        $actorName = $actor?->name ?? 'Pengguna';

        $activities = Activity::where(function ($query) use ($invoice) {
            $query->where('subject_type', Invoice::class)
                ->where('subject_id', $invoice->id);
        })->orWhere('data->invoice_id', $invoice->id)->get();

        $count = 0;
        foreach ($activities as $act) {
            $data = $act->data ?? [];
            $data['is_deleted'] = true;
            $data['deleted_by'] = $actorName;
            $data['deleted_at'] = now()->toIso8601String();
            $data['deletion_message'] = "Faktur {$invoice->invoice_number} telah dihapus oleh {$actorName}";

            $act->data = $data;
            $act->save();
            self::broadcastActivityUpdated($act);
            $count++;
        }

        return $count;
    }

    /**
     * Tandai log aktifitas yang berkaitan dengan surat jalan yang dihapus.
     */
    public static function markDeliveryDeleted(Delivery $delivery, ?User $actor = null): int
    {
        $actor = $actor ?? auth('api')->user() ?? Auth::user();
        $actorName = $actor?->name ?? 'Pengguna';

        $activities = Activity::where(function ($query) use ($delivery) {
            $query->where('subject_type', Delivery::class)
                ->where('subject_id', $delivery->id);
        })->orWhere('data->delivery_id', $delivery->id)->get();

        $count = 0;
        foreach ($activities as $act) {
            $data = $act->data ?? [];
            $data['is_deleted'] = true;
            $data['deleted_by'] = $actorName;
            $data['deleted_at'] = now()->toIso8601String();
            $data['deletion_message'] = "Surat jalan {$delivery->delivery_number} telah dihapus oleh {$actorName}";

            $act->data = $data;
            $act->save();
            self::broadcastActivityUpdated($act);
            $count++;
        }

        return $count;
    }

    /**
     * Broadcast realtime event via Reverb/Pusher saat aktifitas baru dibuat.
     */
    private static function broadcastActivity(Activity $activity): void
    {
        try {
            broadcast(new ActivityCreated($activity));
        } catch (\Throwable $e) {
            Log::warning('[ActivityService] Failed to broadcast activity: '.$e->getMessage());
        }
    }

    /**
     * Broadcast realtime event via Reverb/Pusher saat status aktifitas diperbarui (misal dihapus).
     */
    private static function broadcastActivityUpdated(Activity $activity): void
    {
        try {
            broadcast(new ActivityUpdated($activity));
        } catch (\Throwable $e) {
            Log::warning('[ActivityService] Failed to broadcast activity update: '.$e->getMessage());
        }
    }

    /**
     * Backfill data aktifitas masa lalu dari data delivery & invoice yang sudah ada di database.
     */
    public static function backfill(): int
    {
        $count = 0;

        // 1. Deliveries
        $deliveries = Delivery::with(['store', 'courier'])->get();
        foreach ($deliveries as $deliv) {
            // Check delivery_created
            $exists = Activity::where('type', 'delivery_created')
                ->where('subject_type', Delivery::class)
                ->where('subject_id', $deliv->id)
                ->exists();

            if (! $exists) {
                $act = Activity::create([
                    'type' => 'delivery_created',
                    'title' => 'Surat Pengiriman Dibuat',
                    'description' => "Surat jalan {$deliv->delivery_number} dibuat untuk ".($deliv->store?->name ?? 'Toko Mitra').'.',
                    'user_id' => $deliv->created_by ?: ($deliv->courier_id ?: 1),
                    'courier_id' => $deliv->courier_id,
                    'subject_type' => Delivery::class,
                    'subject_id' => $deliv->id,
                    'data' => [
                        'delivery_id' => $deliv->id,
                        'delivery_number' => $deliv->delivery_number,
                        'store_id' => $deliv->store_id,
                        'store_name' => $deliv->store?->name ?? 'Toko Mitra',
                        'courier_id' => $deliv->courier_id,
                        'courier_name' => $deliv->courier?->name,
                        'total_items' => $deliv->total_items ?? 0,
                        'delivery_date' => $deliv->delivery_date?->toDateString(),
                        'status' => $deliv->status,
                    ],
                    'created_at' => $deliv->created_at,
                    'updated_at' => $deliv->created_at,
                ]);
                $count++;
            }

            // If dispatched
            if (in_array($deliv->status, ['dikirim', 'selesai'])) {
                $dispatchedExists = Activity::where('type', 'delivery_dispatched')
                    ->where('subject_type', Delivery::class)
                    ->where('subject_id', $deliv->id)
                    ->exists();

                if (! $dispatchedExists) {
                    $dispatchedTime = $deliv->dispatched_at ?: $deliv->created_at->addMinutes(15);
                    Activity::create([
                        'type' => 'delivery_dispatched',
                        'title' => 'Pengantaran Diberangkatkan',
                        'description' => "Surat jalan {$deliv->delivery_number} sedang dalam perjalanan ke ".($deliv->store?->name ?? 'Toko Mitra').'.',
                        'user_id' => $deliv->courier_id ?: ($deliv->created_by ?: 1),
                        'courier_id' => $deliv->courier_id,
                        'subject_type' => Delivery::class,
                        'subject_id' => $deliv->id,
                        'data' => [
                            'delivery_id' => $deliv->id,
                            'delivery_number' => $deliv->delivery_number,
                            'store_id' => $deliv->store_id,
                            'store_name' => $deliv->store?->name ?? 'Toko Mitra',
                            'courier_id' => $deliv->courier_id,
                            'courier_name' => $deliv->courier?->name,
                            'dispatched_at' => $dispatchedTime->toIso8601String(),
                            'status' => $deliv->status,
                        ],
                        'created_at' => $dispatchedTime,
                        'updated_at' => $dispatchedTime,
                    ]);
                    $count++;
                }
            }

            // If completed
            if ($deliv->status === 'selesai') {
                $completedExists = Activity::where('type', 'delivery_completed')
                    ->where('subject_type', Delivery::class)
                    ->where('subject_id', $deliv->id)
                    ->exists();

                if (! $completedExists) {
                    $deliveredTime = $deliv->delivered_at ?: ($deliv->dispatched_at ? $deliv->dispatched_at->addHour() : $deliv->created_at->addHours(2));
                    Activity::create([
                        'type' => 'delivery_completed',
                        'title' => 'Pengantaran Selesai',
                        'description' => "Surat jalan {$deliv->delivery_number} berhasil diserahterimakan di ".($deliv->store?->name ?? 'Toko Mitra').'.',
                        'user_id' => $deliv->courier_id ?: ($deliv->created_by ?: 1),
                        'courier_id' => $deliv->courier_id,
                        'subject_type' => Delivery::class,
                        'subject_id' => $deliv->id,
                        'data' => [
                            'delivery_id' => $deliv->id,
                            'delivery_number' => $deliv->delivery_number,
                            'store_id' => $deliv->store_id,
                            'store_name' => $deliv->store?->name ?? 'Toko Mitra',
                            'courier_id' => $deliv->courier_id,
                            'courier_name' => $deliv->courier?->name,
                            'recipient_name' => $deliv->recipient_name,
                            'delivered_at' => $deliveredTime->toIso8601String(),
                            'status' => $deliv->status,
                        ],
                        'created_at' => $deliveredTime,
                        'updated_at' => $deliveredTime,
                    ]);
                    $count++;
                }
            }
        }

        // 2. Invoices
        $invoices = Invoice::with(['store', 'courier', 'payments'])->get();
        foreach ($invoices as $inv) {
            $invExists = Activity::where('type', 'invoice_created')
                ->where('subject_type', Invoice::class)
                ->where('subject_id', $inv->id)
                ->exists();

            if (! $invExists) {
                Activity::create([
                    'type' => 'invoice_created',
                    'title' => 'Surat Faktur Diterbitkan',
                    'description' => "Faktur tagihan {$inv->invoice_number} senilai Rp ".number_format($inv->total_amount, 0, ',', '.').' diterbitkan untuk '.($inv->store?->name ?? 'Toko Mitra').'.',
                    'user_id' => $inv->created_by ?: 1,
                    'courier_id' => $inv->courier_id,
                    'subject_type' => Invoice::class,
                    'subject_id' => $inv->id,
                    'data' => [
                        'invoice_id' => $inv->id,
                        'invoice_number' => $inv->invoice_number,
                        'store_id' => $inv->store_id,
                        'store_name' => $inv->store?->name ?? 'Toko Mitra',
                        'courier_id' => $inv->courier_id,
                        'courier_name' => $inv->courier?->name,
                        'total_amount' => (float) $inv->total_amount,
                        'remaining_balance' => (float) $inv->remaining_balance,
                        'invoice_date' => $inv->invoice_date?->toDateString(),
                        'due_date' => $inv->due_date?->toDateString(),
                        'status' => $inv->status,
                    ],
                    'created_at' => $inv->created_at,
                    'updated_at' => $inv->created_at,
                ]);
                $count++;
            }

            // If invoice is paid
            if ($inv->status === 'lunas') {
                $paidExists = Activity::where('type', 'invoice_paid')
                    ->where('subject_type', Invoice::class)
                    ->where('subject_id', $inv->id)
                    ->exists();

                if (! $paidExists) {
                    $latestPayment = $inv->payments->sortByDesc('id')->first();
                    $paidTime = $latestPayment?->created_at ?? $inv->updated_at;

                    Activity::create([
                        'type' => 'invoice_paid',
                        'title' => 'Faktur Telah Lunas',
                        'description' => "Tagihan faktur {$inv->invoice_number} di ".($inv->store?->name ?? 'Toko Mitra').' senilai Rp '.number_format($inv->total_amount, 0, ',', '.').' telah lunas.',
                        'user_id' => $latestPayment?->user_id ?? $inv->created_by ?? 1,
                        'courier_id' => $inv->courier_id,
                        'subject_type' => Invoice::class,
                        'subject_id' => $inv->id,
                        'data' => [
                            'invoice_id' => $inv->id,
                            'invoice_number' => $inv->invoice_number,
                            'store_id' => $inv->store_id,
                            'store_name' => $inv->store?->name ?? 'Toko Mitra',
                            'courier_id' => $inv->courier_id,
                            'courier_name' => $inv->courier?->name,
                            'total_amount' => (float) $inv->total_amount,
                            'paid_amount' => (float) $inv->paid_amount,
                            'payment_amount' => (float) ($latestPayment?->amount ?? $inv->paid_amount),
                            'payment_method' => $latestPayment?->payment_method ?? 'Tunai',
                            'status' => $inv->status,
                        ],
                        'created_at' => $paidTime,
                        'updated_at' => $paidTime,
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }
}
