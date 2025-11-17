<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class HubspotService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.hubapi.com/',
            'timeout' => 10,
        ]);
    }

    /**
     * Sync a registration to Hubspot as a contact
     */
    public function syncRegistration(Registration $registration): ?string
    {
        $event = $registration->event;

        if (!config('services.hubspot.api_key')) {
            Log::warning('Hubspot API key not configured', [
                'event_id' => $event->id,
            ]);
            return null;
        }

        try {
            // Check if contact already exists
            $existingContact = $this->findContactByEmail($registration->email);

            if ($existingContact) {
                // Update existing contact
                $hubspotId = $this->updateContact(
                    $existingContact['vid'],
                    $registration
                );
            } else {
                // Create new contact
                $hubspotId = $this->createContact($registration);
            }

            // Add contact to event-specific list if configured
            if ($event->hubspot_list_id) {
                $this->addContactToList($hubspotId, $event->hubspot_list_id);
            }

            // Update registration with Hubspot ID
            $registration->update(['hubspot_id' => $hubspotId]);

            Log::info('Synced registration to Hubspot', [
                'registration_id' => $registration->id,
                'hubspot_id' => $hubspotId,
                'list_id' => $event->hubspot_list_id,
            ]);

            return $hubspotId;
        } catch (\Exception $e) {
            Log::error('Failed to sync registration to Hubspot', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a new contact in Hubspot
     */
    private function createContact(Registration $registration): string
    {
        $response = $this->client->post('contacts/v1/contact/', [
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.hubspot.api_key'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'properties' => $this->buildContactProperties($registration),
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return (string) $data['vid'];
    }

    /**
     * Update an existing contact in Hubspot
     */
    private function updateContact(string $contactId, Registration $registration): string
    {
        $this->client->post("contacts/v1/contact/vid/{$contactId}/profile", [
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.hubspot.api_key'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'properties' => $this->buildContactProperties($registration),
            ],
        ]);

        return $contactId;
    }

    /**
     * Find a contact by email
     */
    private function findContactByEmail(string $email): ?array
    {
        try {
            $response = $this->client->get("contacts/v1/contact/email/{$email}/profile", [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.hubspot.api_key'),
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return null; // Contact not found
            }
            throw $e;
        }
    }

    /**
     * Add a contact to a Hubspot list
     */
    private function addContactToList(string $contactId, string $listId): void
    {
        try {
            $this->client->post("contacts/v1/lists/{$listId}/add", [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.hubspot.api_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'vids' => [(int) $contactId],
                ],
            ]);

            Log::info('Added contact to Hubspot list', [
                'contact_id' => $contactId,
                'list_id' => $listId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add contact to Hubspot list', [
                'contact_id' => $contactId,
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - list addition is not critical
        }
    }

    /**
     * Build contact properties array for Hubspot
     */
    private function buildContactProperties(Registration $registration): array
    {
        $event = $registration->event;

        $properties = [
            [
                'property' => 'email',
                'value' => $registration->email,
            ],
            [
                'property' => 'firstname',
                'value' => $registration->name,
            ],
            [
                'property' => 'lastname',
                'value' => $registration->surname,
            ],
        ];

        if ($registration->company) {
            $properties[] = [
                'property' => 'company',
                'value' => $registration->company,
            ];
        }

        if ($registration->phone) {
            $properties[] = [
                'property' => 'phone',
                'value' => $registration->phone,
            ];
        }

        // Add event-specific custom properties
        $properties[] = [
            'property' => 'event_name',
            'value' => $event->name,
        ];

        $properties[] = [
            'property' => 'registration_status',
            'value' => $registration->payment_status,
        ];

        $properties[] = [
            'property' => 'registration_date',
            'value' => $registration->created_at->timestamp * 1000, // Hubspot uses milliseconds
        ];

        return $properties;
    }

    /**
     * Sync multiple registrations in batch
     */
    public function syncRegistrations(Event $event, array $registrations): array
    {
        $results = [];

        foreach ($registrations as $registration) {
            $results[$registration->id] = $this->syncRegistration($registration);
        }

        return $results;
    }

    /**
     * Get companies from a Hubspot list
     */
    public function getCompaniesFromList(string $listId): array
    {
        try {
            $response = $this->client->get("contacts/v1/lists/{$listId}/contacts/all", [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.hubspot.api_key'),
                ],
                'query' => [
                    'count' => 100, // Max per request
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $companies = [];

            foreach ($data['contacts'] ?? [] as $contact) {
                $properties = $contact['properties'] ?? [];

                $companies[] = [
                    'id' => $contact['vid'] ?? $contact['id'] ?? null,
                    'name' => $properties['company']['value'] ?? $properties['companyname']['value'] ?? 'Unknown Company',
                    'email' => $properties['email']['value'] ?? null,
                    'properties' => $properties,
                ];
            }

            return $companies;
        } catch (\Exception $e) {
            Log::error('Failed to fetch companies from Hubspot list', [
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all companies from Hubspot (paginated)
     */
    public function getAllCompanies(int $limit = 100): array
    {
        try {
            $response = $this->client->get('crm/v3/objects/companies', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.hubspot.api_key'),
                ],
                'query' => [
                    'limit' => $limit,
                    'properties' => 'name,domain,city,country',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return array_map(function ($company) {
                return [
                    'id' => $company['id'],
                    'name' => $company['properties']['name'] ?? 'Unknown',
                    'domain' => $company['properties']['domain'] ?? null,
                    'city' => $company['properties']['city'] ?? null,
                    'country' => $company['properties']['country'] ?? null,
                ];
            }, $data['results'] ?? []);
        } catch (\Exception $e) {
            Log::error('Failed to fetch all companies from Hubspot', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
