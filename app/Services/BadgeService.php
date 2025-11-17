<?php

namespace App\Services;

use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BadgeService
{
    /**
     * Generate a PDF badge for a registration
     */
    public function generateBadge(Registration $registration): string
    {
        try {
            $event = $registration->event;
            $template = $event->settings['badge_template'] ?? null;

            // Generate QR code if barcode is enabled for this event
            $qrCode = null;
            if ($event->settings['badge_barcode_enabled'] ?? false) {
                $qrCode = $this->generateQrCode($registration);
            }

            // Determine which template to use
            if ($template && !empty($template['fields'])) {
                $pdf = Pdf::loadView('badges.custom-template', [
                    'registration' => $registration,
                    'event' => $event,
                    'template' => $template,
                    'qrCode' => $qrCode,
                ]);

                // Use custom dimensions
                $width = ($template['width'] ?? 400) * 0.75; // Convert px to points (1px = 0.75pt)
                $height = ($template['height'] ?? 300) * 0.75;
                $pdf->setPaper([0, 0, $width, $height], 'landscape');
            } else {
                // Fall back to default template
                $pdf = Pdf::loadView('badges.template', [
                    'registration' => $registration,
                    'event' => $event,
                    'qrCode' => $qrCode,
                ]);

                // Set paper size (standard badge size: 4" x 3")
                $pdf->setPaper([0, 0, 288, 216], 'landscape'); // 4" x 3" in points
            }

            // Generate filename
            $filename = $this->generateFilename($registration);

            // Store the PDF
            $path = "badges/{$event->slug}/{$filename}";
            Storage::put($path, $pdf->output());

            // Update registration
            $registration->markBadgeAsGenerated($path);

            Log::info('Badge generated', [
                'registration_id' => $registration->id,
                'path' => $path,
                'template' => $template ? 'custom' : 'default',
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error('Failed to generate badge', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate filename for badge
     */
    private function generateFilename(Registration $registration): string
    {
        $name = str_replace(' ', '-', strtolower($registration->full_name));
        $timestamp = now()->format('YmdHis');

        return "badge-{$name}-{$timestamp}.pdf";
    }

    /**
     * Get badge download URL
     */
    public function getBadgeUrl(Registration $registration): ?string
    {
        if (!$registration->badge_generated || !$registration->badge_file_path) {
            return null;
        }

        return Storage::url($registration->badge_file_path);
    }

    /**
     * Download badge as response
     */
    public function downloadBadge(Registration $registration): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!$registration->badge_generated || !$registration->badge_file_path) {
            throw new \Exception('Badge not generated yet');
        }

        $filename = basename($registration->badge_file_path);

        return Storage::download($registration->badge_file_path, $filename);
    }

    /**
     * Regenerate a badge (e.g., after registration update)
     */
    public function regenerateBadge(Registration $registration): string
    {
        // Delete old badge if exists
        if ($registration->badge_file_path && Storage::exists($registration->badge_file_path)) {
            Storage::delete($registration->badge_file_path);
        }

        // Generate new badge
        return $this->generateBadge($registration);
    }

    /**
     * Generate badges for multiple registrations in batch
     */
    public function generateBadges(array $registrations): array
    {
        $results = [];

        foreach ($registrations as $registration) {
            try {
                $results[$registration->id] = $this->generateBadge($registration);
            } catch (\Exception $e) {
                $results[$registration->id] = null;
                Log::error('Batch badge generation failed', [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate QR code for a registration
     * Contains registration ID and event information
     */
    public function generateQrCode(Registration $registration): string
    {
        $event = $registration->event;

        // Create QR code data with registration and event info
        $qrData = json_encode([
            'registration_id' => $registration->id,
            'event_id' => $event->id,
            'event_name' => $event->name,
            'event_slug' => $event->slug,
            'attendee_name' => $registration->full_name,
            'attendee_email' => $registration->email,
            'ticket_code' => $this->generateTicketCode($registration),
            'verified_at' => null, // To be updated when scanned
        ]);

        // Generate QR code as base64 PNG image
        $qrCode = QrCode::format('png')
            ->size(200)
            ->margin(1)
            ->errorCorrection('H') // High error correction
            ->generate($qrData);

        // Convert to base64 data URI for embedding in PDF
        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Generate a unique ticket code for verification
     */
    private function generateTicketCode(Registration $registration): string
    {
        // Format: EVENT-REG-TIMESTAMP
        // Example: WEBINAR-12345-20250117
        $eventPrefix = strtoupper(substr($registration->event->slug, 0, 8));
        $regId = str_pad($registration->id, 5, '0', STR_PAD_LEFT);
        $date = $registration->created_at->format('Ymd');

        return "{$eventPrefix}-{$regId}-{$date}";
    }

    /**
     * Verify a ticket code from QR scan
     */
    public function verifyTicketCode(string $qrData): ?array
    {
        try {
            $data = json_decode($qrData, true);

            if (!$data || !isset($data['registration_id'])) {
                return null;
            }

            $registration = Registration::with('event')->find($data['registration_id']);

            if (!$registration) {
                return null;
            }

            return [
                'valid' => true,
                'registration' => $registration,
                'event' => $registration->event,
                'ticket_code' => $data['ticket_code'] ?? null,
                'already_verified' => !is_null($data['verified_at']),
            ];
        } catch (\Exception $e) {
            Log::error('QR code verification failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
