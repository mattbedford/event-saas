<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Services\CouponService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupAbandonedRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:cleanup-abandoned {--dry-run : Run without actually making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup abandoned registrations and expired coupon reservations';

    /**
     * Execute the console command.
     */
    public function handle(CouponService $couponService)
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        }

        $this->info('🧹 Starting cleanup process...');
        $this->newLine();

        // Step 1: Expire old coupon reservations
        $this->line('📋 Step 1: Expiring old coupon reservations...');
        $expiredCount = $this->expireOldReservations($couponService, $dryRun);
        $this->info("  ✓ Processed {$expiredCount} expired reservation(s)");
        $this->newLine();

        // Step 2: Mark abandoned draft registrations
        $this->line('📋 Step 2: Marking abandoned draft registrations...');
        $abandonedCount = $this->markAbandonedDrafts($dryRun);
        $this->info("  ✓ Marked {$abandonedCount} registration(s) as abandoned");
        $this->newLine();

        // Step 3: Release reservations for abandoned registrations
        $this->line('📋 Step 3: Releasing reservations for abandoned registrations...');
        $releasedCount = $this->releaseAbandonedReservations($couponService, $dryRun);
        $this->info("  ✓ Released {$releasedCount} reservation(s)");
        $this->newLine();

        // Summary
        $totalActions = $expiredCount + $abandonedCount + $releasedCount;

        if ($dryRun) {
            $this->warn("🔍 DRY RUN: Would have processed {$totalActions} item(s)");
        } else {
            $this->info("✅ Cleanup completed successfully! Processed {$totalActions} item(s)");

            Log::info('Registration cleanup completed', [
                'expired_reservations' => $expiredCount,
                'abandoned_registrations' => $abandonedCount,
                'released_reservations' => $releasedCount,
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Expire old coupon reservations (>30 minutes)
     */
    private function expireOldReservations(CouponService $couponService, bool $dryRun): int
    {
        if ($dryRun) {
            // Just count without expiring
            $count = \App\Models\CouponReservation::where('status', 'reserved')
                ->where('expires_at', '<=', now())
                ->count();

            if ($count > 0) {
                $this->line("  [DRY RUN] Would expire {$count} reservation(s)");
            }

            return $count;
        }

        return $couponService->expireOldReservations();
    }

    /**
     * Mark draft registrations as abandoned after 24 hours
     */
    private function markAbandonedDrafts(bool $dryRun): int
    {
        $abandonDeadline = now()->subHours(24);

        // Find registrations in draft/pending_payment state for more than 24 hours
        $oldDrafts = Registration::whereIn('registration_status', ['draft', 'pending_payment'])
            ->where('created_at', '<=', $abandonDeadline)
            ->get();

        if ($oldDrafts->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($oldDrafts as $registration) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Would abandon registration #{$registration->id} ({$registration->email})");
            } else {
                $registration->update(['registration_status' => 'abandoned']);

                Log::info('Registration auto-abandoned', [
                    'registration_id' => $registration->id,
                    'email' => $registration->email,
                    'age_hours' => now()->diffInHours($registration->created_at),
                ]);

                $this->line("  ✓ Abandoned registration #{$registration->id} ({$registration->email})");
            }

            $count++;
        }

        return $count;
    }

    /**
     * Release reservations for abandoned registrations
     */
    private function releaseAbandonedReservations(CouponService $couponService, bool $dryRun): int
    {
        // Find active reservations for abandoned/payment_failed registrations
        $reservationsToRelease = \App\Models\CouponReservation::where('status', 'reserved')
            ->whereHas('registration', function ($query) {
                $query->whereIn('registration_status', ['abandoned', 'payment_failed']);
            })
            ->get();

        if ($reservationsToRelease->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($reservationsToRelease as $reservation) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Would release reservation for registration #{$reservation->registration_id}");
            } else {
                $couponService->releaseReservation($reservation);

                Log::info('Reservation auto-released for abandoned registration', [
                    'reservation_id' => $reservation->id,
                    'registration_id' => $reservation->registration_id,
                    'coupon_code' => $reservation->coupon->code ?? 'unknown',
                ]);

                $this->line("  ✓ Released reservation for registration #{$reservation->registration_id}");
            }

            $count++;
        }

        return $count;
    }
}
