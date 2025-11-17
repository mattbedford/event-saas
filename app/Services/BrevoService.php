<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Registration;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class BrevoService
{
    private TransactionalEmailsApi $apiInstance;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            config('services.brevo.api_key')
        );

        $this->apiInstance = new TransactionalEmailsApi(new Client(), $config);
        $this->fromEmail = config('services.brevo.from_email');
        $this->fromName = config('services.brevo.from_name');
    }

    /**
     * Send an email using a template and registration data
     */
    public function sendEmailFromTemplate(
        EmailTemplate $template,
        Registration $registration,
        ?int $emailChainId = null
    ): ?EmailLog {
        try {
            // Prepare template variables
            $variables = $this->prepareTemplateVariables($registration);

            // Render the template
            $rendered = $template->render($variables);

            // Create email log
            $emailLog = EmailLog::create([
                'registration_id' => $registration->id,
                'email_template_id' => $template->id,
                'email_chain_id' => $emailChainId,
                'status' => 'pending',
            ]);

            // Send via Brevo
            $result = $this->sendEmail(
                $registration->email,
                $registration->full_name,
                $rendered['subject'],
                $rendered['html_content'],
                $rendered['text_content']
            );

            if ($result) {
                $emailLog->markAsSent($result['messageId']);
                Log::info('Email sent successfully', [
                    'email_log_id' => $emailLog->id,
                    'brevo_message_id' => $result['messageId'],
                    'recipient' => $registration->email,
                ]);

                return $emailLog;
            }

            $emailLog->markAsFailed('Failed to send email via Brevo');
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'template_id' => $template->id,
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($emailLog)) {
                $emailLog->markAsFailed($e->getMessage());
            }

            return null;
        }
    }

    /**
     * Send an email directly via Brevo
     */
    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        ?string $textContent = null
    ): ?array {
        try {
            $sendSmtpEmail = new SendSmtpEmail([
                'sender' => [
                    'name' => $this->fromName,
                    'email' => $this->fromEmail,
                ],
                'to' => [[
                    'email' => $toEmail,
                    'name' => $toName,
                ]],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
                'textContent' => $textContent,
            ]);

            $result = $this->apiInstance->sendTransacEmail($sendSmtpEmail);

            return [
                'messageId' => $result->getMessageId(),
            ];
        } catch (\Exception $e) {
            Log::error('Brevo API error', [
                'error' => $e->getMessage(),
                'to' => $toEmail,
            ]);

            return null;
        }
    }

    /**
     * Get email statistics from Brevo
     */
    public function getEmailStats(string $brevoMessageId): ?array
    {
        try {
            $client = new Client();
            $response = $client->request('GET', "https://api.brevo.com/v3/smtp/statistics/events?messageId={$brevoMessageId}", [
                'headers' => [
                    'api-key' => config('services.brevo.api_key'),
                    'accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'events' => $data['events'] ?? [],
                'total_count' => $data['count'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Brevo stats', [
                'message_id' => $brevoMessageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update email log from Brevo webhook
     */
    public function processWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $messageId = $payload['message-id'] ?? $payload['id'] ?? null;

        if (!$event || !$messageId) {
            Log::warning('Invalid Brevo webhook payload', ['payload' => $payload]);
            return;
        }

        $emailLog = EmailLog::where('brevo_message_id', $messageId)->first();

        if (!$emailLog) {
            Log::warning('Email log not found for Brevo message', ['message_id' => $messageId]);
            return;
        }

        switch ($event) {
            case 'delivered':
                $emailLog->markAsDelivered();
                break;

            case 'opened':
            case 'unique_opened':
                $emailLog->markAsOpened();
                break;

            case 'click':
            case 'unique_click':
                $emailLog->markAsClicked();
                break;

            case 'hard_bounce':
            case 'soft_bounce':
            case 'blocked':
            case 'invalid_email':
                $emailLog->update(['status' => 'bounced']);
                break;

            case 'error':
            case 'deferred':
                $errorReason = $payload['reason'] ?? $payload['error'] ?? 'Unknown error';
                $emailLog->markAsFailed($errorReason);
                break;
        }

        // Store the full event data
        $emailLog->updateBrevoStats($payload);

        Log::info('Processed Brevo webhook', [
            'event' => $event,
            'message_id' => $messageId,
            'email_log_id' => $emailLog->id,
        ]);
    }

    /**
     * Prepare template variables from registration
     */
    private function prepareTemplateVariables(Registration $registration): array
    {
        $event = $registration->event;

        return [
            'event_name' => $event->name,
            'registrant_name' => $registration->full_name,
            'registrant_first_name' => $registration->name,
            'registrant_last_name' => $registration->surname,
            'registrant_email' => $registration->email,
            'registrant_company' => $registration->company ?? '',
            'event_date' => $event->event_date->format('F j, Y'),
            'event_time' => $event->event_date->format('g:i A'),
            'ticket_price' => number_format($event->ticket_price, 2) . ' CHF',
            'paid_amount' => number_format($registration->paid_amount, 2) . ' CHF',
            'badge_download_link' => $registration->badge_file_path
                ? url('/api/badges/' . $registration->id)
                : '',
        ];
    }
}
