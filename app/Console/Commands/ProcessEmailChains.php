<?php

namespace App\Console\Commands;

use App\Models\EmailChain;
use App\Models\EmailLog;
use App\Models\Registration;
use App\Services\BrevoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessEmailChains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process-chains {--dry-run : Run without actually sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process email chains and send scheduled emails to registrations';

    protected BrevoService $brevoService;

    public function __construct(BrevoService $brevoService)
    {
        parent::__construct();
        $this->brevoService = $brevoService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no emails will be sent');
        }

        $this->info('📧 Processing email chains...');

        // Get all active email chains
        $emailChains = EmailChain::with(['emailTemplate', 'event'])
            ->where('is_active', true)
            ->get();

        if ($emailChains->isEmpty()) {
            $this->warn('⚠️  No active email chains found');
            return Command::SUCCESS;
        }

        $this->info("Found {$emailChains->count()} active email chains");

        $totalSent = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($emailChains as $chain) {
            $this->line("Processing chain: {$chain->emailTemplate->name} for event: {$chain->event->name}");

            // Get all paid registrations for this event
            $registrations = Registration::where('event_id', $chain->event_id)
                ->where('payment_status', 'paid')
                ->get();

            foreach ($registrations as $registration) {
                // Check if this email should be sent for this registration
                if (!$chain->shouldSendForRegistration($registration)) {
                    $this->line("  ⏭️  Skipping {$registration->email} - conditions not met");
                    $totalSkipped++;
                    continue;
                }

                // Calculate when this email should be sent
                $sendTime = $chain->calculateSendTime($registration->created_at);

                // Check if it's time to send
                if ($sendTime->isFuture()) {
                    $this->line("  ⏰ Not yet time for {$registration->email} - scheduled for {$sendTime->format('Y-m-d H:i:s')}");
                    $totalSkipped++;
                    continue;
                }

                // Check if this email has already been sent
                $alreadySent = EmailLog::where('registration_id', $registration->id)
                    ->where('email_chain_id', $chain->id)
                    ->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])
                    ->exists();

                if ($alreadySent) {
                    $totalSkipped++;
                    continue;
                }

                // Send the email
                if ($dryRun) {
                    $this->info("  📨 [DRY RUN] Would send to {$registration->email}");
                    $totalSent++;
                } else {
                    try {
                        $emailLog = $this->brevoService->sendEmailFromTemplate(
                            $chain->emailTemplate,
                            $registration,
                            $chain->id
                        );

                        if ($emailLog && $emailLog->status === 'sent') {
                            $this->info("  ✅ Sent to {$registration->email}");
                            $totalSent++;
                        } else {
                            $this->error("  ❌ Failed to send to {$registration->email}");
                            $totalErrors++;
                        }
                    } catch (\Exception $e) {
                        $this->error("  ❌ Error sending to {$registration->email}: {$e->getMessage()}");
                        Log::error('Email chain processing error', [
                            'registration_id' => $registration->id,
                            'email_chain_id' => $chain->id,
                            'error' => $e->getMessage(),
                        ]);
                        $totalErrors++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Sent', $totalSent],
                ['⏭️  Skipped', $totalSkipped],
                ['❌ Errors', $totalErrors],
            ]
        );

        if ($dryRun) {
            $this->warn('🔍 This was a DRY RUN - no emails were actually sent');
        }

        return Command::SUCCESS;
    }
}
