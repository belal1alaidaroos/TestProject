<?php

namespace App\Jobs;

use App\Models\WorkerReservation;
use App\Models\Worker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReleaseExpiredReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting ReleaseExpiredReservations job');

        try {
            // Get expired reservations
            $expiredReservations = WorkerReservation::where('status', 'Active')
                ->where('expires_at', '<', now())
                ->with(['worker', 'customer'])
                ->get();

            $releasedCount = 0;

            foreach ($expiredReservations as $reservation) {
                try {
                    // Update reservation status
                    $reservation->update([
                        'status' => 'Expired',
                        'modified_by' => 'system',
                    ]);

                    // Release the worker
                    $reservation->worker->update([
                        'status' => 'Ready',
                        'modified_by' => 'system',
                    ]);

                    $releasedCount++;

                    Log::info('Released expired reservation', [
                        'reservation_id' => $reservation->id,
                        'worker_id' => $reservation->worker_id,
                        'customer_id' => $reservation->customer_id,
                    ]);

                    // TODO: Send notification to customer about expired reservation
                    // $this->sendExpiredReservationNotification($reservation);

                } catch (\Exception $e) {
                    Log::error('Failed to release expired reservation', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('ReleaseExpiredReservations job completed', [
                'total_expired' => $expiredReservations->count(),
                'released_count' => $releasedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('ReleaseExpiredReservations job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send notification to customer about expired reservation
     */
    private function sendExpiredReservationNotification(WorkerReservation $reservation): void
    {
        // TODO: Implement notification logic
        // This could be SMS, email, or push notification
        // For now, just log the event
        Log::info('Expired reservation notification should be sent', [
            'reservation_id' => $reservation->id,
            'customer_phone' => $reservation->customer->phone,
        ]);
    }
}