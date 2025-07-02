<?php

declare(strict_types=1);

namespace Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Inbounder\Events\EmailTemplateCreated;
use Inbounder\Events\EmailTemplateDeleted;
use Inbounder\Events\EmailTemplateUpdated;

/**
 * Email template model for storing and managing HTML email templates.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $subject
 * @property string $html_content
 * @property string|null $text_content
 * @property array|null $variables
 * @property array|null $metadata
 * @property bool $is_active
 * @property string|null $category
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EmailTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'subject',
        'html_content',
        'text_content',
        'variables',
        'metadata',
        'is_active',
        'category',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function distributionLists()
    {
        return $this->hasMany(DistributionList::class, 'email_template_id');
    }

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        // Dispatch events for model lifecycle
        static::created(function (EmailTemplate $emailTemplate) {
            event(new EmailTemplateCreated($emailTemplate));
        });

        static::updated(function (EmailTemplate $emailTemplate) {
            $changes = [];
            foreach ($emailTemplate->getChanges() as $attribute => $newValue) {
                $changes[$attribute] = [
                    'old' => $emailTemplate->getOriginal($attribute),
                    'new' => $newValue,
                ];
            }
            event(new EmailTemplateUpdated($emailTemplate, $changes));
        });

        static::deleted(function (EmailTemplate $emailTemplate) {
            $templateData = [
                'html_content' => $emailTemplate->html_content,
                'text_content' => $emailTemplate->text_content,
                'variables' => $emailTemplate->variables,
                'metadata' => $emailTemplate->metadata,
                'created_at' => $emailTemplate->created_at?->toISOString(),
                'updated_at' => $emailTemplate->updated_at?->toISOString(),
            ];

            event(new EmailTemplateDeleted(
                $emailTemplate->id,
                $emailTemplate->name,
                $emailTemplate->slug,
                $emailTemplate->subject,
                $emailTemplate->category,
                $emailTemplate->is_active,
                $templateData
            ));
        });
    }

    /**
     * Scope to get only active templates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Render the HTML content with provided variables.
     */
    public function renderHtml(array $variables = []): string
    {
        return $this->renderContent($this->html_content, $variables);
    }

    /**
     * Render the text content with provided variables.
     */
    public function renderText(array $variables = []): ?string
    {
        if (! $this->text_content) {
            return null;
        }

        return $this->renderContent($this->text_content, $variables);
    }

    /**
     * Render the subject with provided variables.
     */
    public function renderSubject(array $variables = []): string
    {
        return $this->renderContent($this->subject, $variables);
    }

    /**
     * Render content by replacing variables.
     */
    private function renderContent(string $content, array $variables = []): string
    {
        foreach ($variables as $key => $value) {
            $stringValue = (string) $value;
            $content = str_replace('{{'.$key.'}}', $stringValue, $content);
            $content = str_replace('{{ '.$key.' }}', $stringValue, $content);
        }

        return $content;
    }

    /**
     * Get the available variables for this template.
     */
    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Extract variables from content.
     */
    public static function extractVariables(string $content): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Validate that all required variables are provided.
     */
    public function validateVariables(array $variables): bool
    {
        $required = $this->getAvailableVariables();
        $provided = array_keys($variables);

        return empty(array_diff($required, $provided));
    }

    /**
     * Get missing variables.
     */
    public function getMissingVariables(array $variables): array
    {
        $required = $this->getAvailableVariables();
        $provided = array_keys($variables);

        return array_diff($required, $provided);
    }

    /**
     * Create a new template from the current one.
     */
    public function duplicate(string $newName): static
    {
        return static::create([
            'name' => $newName,
            'slug' => Str::slug($newName),
            'subject' => $this->subject,
            'html_content' => $this->html_content,
            'text_content' => $this->text_content,
            'variables' => $this->variables,
            'metadata' => $this->metadata,
            'category' => $this->category,
            'is_active' => false, // Duplicates are inactive by default
        ]);
    }
}
