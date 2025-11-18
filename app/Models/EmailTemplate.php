<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'subject',
        'html_content',
        'text_content',
        'available_variables',
        'is_system',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_system' => 'boolean',
    ];

    /**
     * Get all email chains using this template
     */
    public function emailChains(): HasMany
    {
        return $this->hasMany(EmailChain::class);
    }

    /**
     * Get all email logs for this template
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Render the template with given variables
     */
    public function render(array $variables): array
    {
        $subject = $this->replaceVariables($this->subject, $variables);
        $htmlContent = $this->replaceVariables($this->html_content, $variables);
        $textContent = $this->text_content ? $this->replaceVariables($this->text_content, $variables) : null;

        return [
            'subject' => $subject,
            'html_content' => $htmlContent,
            'text_content' => $textContent,
        ];
    }

    /**
     * Replace template variables with actual values
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $content;
    }

    /**
     * Get available variable names for this template
     */
    public function getAvailableVariableNames(): array
    {
        return $this->available_variables ?? [
            'event_name',
            'registrant_name',
            'registrant_email',
            'event_date',
            'ticket_price',
            'badge_download_link',
        ];
    }
}
