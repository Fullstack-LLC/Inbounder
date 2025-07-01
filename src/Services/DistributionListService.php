<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inbounder\Models\DistributionList;

/**
 * Service for managing distribution lists.
 */
class DistributionListService
{
    /**
     * Create a new distribution list.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createList(array $data): DistributionList
    {
        $this->validateListData($data);

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        return DistributionList::create($data);
    }

    /**
     * Update an existing distribution list.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateList(DistributionList $list, array $data): DistributionList
    {
        $this->validateListData($data, $list->id);

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['slug'])) {
            $updateData['slug'] = $data['slug'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['category'])) {
            $updateData['category'] = $data['category'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }
        if (isset($data['metadata'])) {
            $updateData['metadata'] = $data['metadata'];
        }

        $list->update($updateData);

        return $list->fresh();
    }

    /**
     * Get a list by slug.
     */
    public function getListBySlug(string $slug): ?DistributionList
    {
        return DistributionList::where('slug', $slug)->active()->first();
    }

    /**
     * Get all active lists.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveLists(?string $category = null)
    {
        $query = DistributionList::active();

        if ($category) {
            $query->byCategory($category);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Add subscribers to a list.
     */
    public function addSubscribers(DistributionList $list, array $subscribers): array
    {
        $results = [
            'added' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        foreach ($subscribers as $subscriberData) {
            try {
                $this->validateSubscriberData($subscriberData);

                $key = null;
                if (isset($subscriberData['user_id'])) {
                    $existing = $list->subscribers()->where('user_id', $subscriberData['user_id'])->first();
                    $key = 'user_id';
                } elseif (isset($subscriberData['email'])) {
                    $existing = $list->subscribers()->where('email', $subscriberData['email'])->first();
                    $key = 'email';
                } else {
                    throw new \InvalidArgumentException('Must provide user_id or email to add subscriber');
                }

                if ($existing) {
                    $existing->update(array_merge($subscriberData, [
                        'is_active' => true,
                    ]));
                    $results['updated']++;
                } else {
                    $list->addSubscriber($subscriberData);
                    $results['added']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'user_id' => $subscriberData['user_id'] ?? null,
                    'email' => $subscriberData['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Remove subscribers from a list.
     *
     * @param  array  $identifiers  (array of user_ids or emails)
     */
    public function removeSubscribers(DistributionList $list, array $identifiers): array
    {
        $results = [
            'removed' => 0,
            'not_found' => 0,
            'errors' => [],
        ];

        foreach ($identifiers as $idOrEmail) {
            try {
                $removed = false;
                if (is_int($idOrEmail) || ctype_digit($idOrEmail)) {
                    $removed = $list->removeSubscriber((int) $idOrEmail, null);
                } elseif (is_string($idOrEmail)) {
                    $removed = $list->removeSubscriber(null, $idOrEmail);
                }
                if ($removed) {
                    $results['removed']++;
                } else {
                    $results['not_found']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'identifier' => $idOrEmail,
                    'email' => is_string($idOrEmail) ? $idOrEmail : null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Remove all subscribers from a list.
     */
    public function removeAllSubscribers(DistributionList $list): int
    {
        return $list->subscribers()->delete();
    }

    /**
     * Remove inactive subscribers from a list.
     */
    public function removeInactiveSubscribers(DistributionList $list): int
    {
        return $list->subscribers()->where('is_active', false)->delete();
    }

    /**
     * Get subscribers for a list.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubscribers(DistributionList $list, bool $activeOnly = true)
    {
        $query = $list->subscribers();

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('email')->get();
    }

    /**
     * Send a campaign to a distribution list.
     */
    public function sendCampaignToList(
        DistributionList $list,
        string $templateSlug = null,
        array $variables = [],
        array $options = []
    ): array {
        $templateSlug = $templateSlug ?: optional($list->getDefaultTemplate())->slug;
        if (!$templateSlug) {
            throw new \InvalidArgumentException('No template slug provided and no default template set for this list.');
        }
        $subscribers = $this->getSubscribers($list, true);
        $results = [
            'total_subscribers' => $subscribers->count(),
            'emails_sent' => 0,
            'emails_failed' => 0,
            'errors' => [],
        ];
        $emailDispatcher = app(TemplatedEmailJobDispatcher::class);
        foreach ($subscribers as $subscriber) {
            try {
                $subscriberVariables = array_merge($variables, [
                    'name' => $subscriber->getFullName(),
                    'email' => $subscriber->email,
                ]);
                $emailDispatcher->sendToOne(
                    $subscriber->email,
                    $templateSlug,
                    $subscriberVariables,
                    $options
                );
                $results['emails_sent']++;
            } catch (\Exception $e) {
                $results['emails_failed']++;
                $results['errors'][] = [
                    'email' => $subscriber->email,
                    'error' => $e->getMessage(),
                ];
            }
        }
        return $results;
    }

    /**
     * Get list statistics.
     */
    public function getListStats(): array
    {
        $total = DistributionList::count();
        $active = DistributionList::active()->count();
        $categories = DistributionList::getCategories();

        return [
            'total_lists' => $total,
            'active_lists' => $active,
            'inactive_lists' => $total - $active,
            'categories' => count($categories),
            'category_list' => $categories,
        ];
    }

    /**
     * Validate list data.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateListData(array $data, ?int $excludeId = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:distribution_lists,slug' . ($excludeId ? ",{$excludeId}" : ''),
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate subscriber data.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateSubscriberData(array $data): void
    {
        // Get the user model and table from config
        $userModel = config('mailgun.user_model', \App\Models\User::class);
        $userTable = (new $userModel)->getTable();

        $rules = [
            'user_id' => "nullable|integer|exists:{$userTable},id",
            'email' => 'nullable|email|max:255',
            'metadata' => 'nullable|array',
        ];

        if (empty($data['user_id']) && empty($data['email'])) {
            throw new ValidationException(Validator::make([], ['user_id' => 'required_without:email', 'email' => 'required_without:user_id']));
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Generate a unique slug from a name.
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Check if slug exists and generate a unique one
        while (DistributionList::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
