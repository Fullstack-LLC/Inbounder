<?php

namespace Fullstack\Inbounder;

use Fullstack\Inbounder\Contracts\WebhookEvent;

final class Event implements WebhookEvent
{
    /**
     * Attributes from the event.
     *
     * @var mixed[]
     */
    public array $attributes = [];

    /**
     * Create new Event.
     *
     * @param  mixed[]  $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Static event constructor.
     *
     * @param  mixed[]  $data
     * @return static
     */
    public static function constructFrom(array $data): self
    {
        return new self($data);
    }
}
