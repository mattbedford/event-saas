<?php

namespace App\Services;

use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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

            // Determine which template to use
            if ($template && !empty($template['fields'])) {
                $pdf = Pdf::loadView('badges.custom-template', [
                    'registration' => $registration,
                    'event' => $event,
                    'template' => $template,
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
}
