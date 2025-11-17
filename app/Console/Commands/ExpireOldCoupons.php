<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireOldCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupons:expire-old {--dry-run : Run without actually expiring coupons}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire coupons from previous years';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no coupons will be expired');
        }

        $this->info('🎫 Checking for expired coupons...');

        $currentYear = now()->year;

        // Find all active coupons from previous years
        $expiredCoupons = Coupon::where('is_active', true)
            ->where('year', '<', $currentYear)
            ->get();

        if ($expiredCoupons->isEmpty()) {
            $this->info('✅ No coupons to expire');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredCoupons->count()} coupon(s) to expire:");
        $this->newLine();

        $expiredCount = 0;

        // Group by year for reporting
        $byYear = $expiredCoupons->groupBy('year');

        foreach ($byYear as $year => $coupons) {
            $this->line("  Year {$year}: {$coupons->count()} coupon(s)");

            foreach ($coupons as $coupon) {
                if ($dryRun) {
                    $this->line("    [DRY RUN] Would expire: {$coupon->code} ({$coupon->company_name})");
                } else {
                    $coupon->update(['is_active' => false]);
                    $this->line("    ✓ Expired: {$coupon->code} ({$coupon->company_name})");

                    Log::info('Coupon auto-expired', [
                        'coupon_id' => $coupon->id,
                        'code' => $coupon->code,
                        'year' => $coupon->year,
                        'company' => $coupon->company_name,
                    ]);
                }

                $expiredCount++;
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("🔍 DRY RUN: Would have expired {$expiredCount} coupon(s)");
        } else {
            $this->info("✅ Successfully expired {$expiredCount} coupon(s)");

            // Log summary
            Log::info('Coupon auto-expiration completed', [
                'total_expired' => $expiredCount,
                'current_year' => $currentYear,
            ]);
        }

        return Command::SUCCESS;
    }
}
