<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Provides methods for inspecting an HTMX request.
 *
 * This trait can be used in Laravel FormRequest classes to add helper
 * methods for accessing HTMX-specific request headers.
 *
 * @see https://htmx.org/reference/#request_headers
 */
trait HtmxRequestTrait
{
    /**
     * Indicates that the request is made via HTMX.
     *
     * @return bool True if the HX-Request header is present and truthy
     */
    public function isHtmxRequest(): bool
    {
        return filter_var($this->header('HX-Request', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Indicates that the request is via an element using `hx-boost`.
     *
     * @return bool True if the HX-Boosted header is present and truthy
     */
    public function isBoosted(): bool
    {
        return filter_var($this->header('HX-Boosted', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Indicates if the request is for history restoration after a miss in the local history cache.
     *
     * @return bool True if the HX-History-Restore-Request header is present and truthy
     */
    public function isHistoryRestoreRequest(): bool
    {
        return filter_var($this->header('HX-History-Restore-Request', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * The current URL of the browser when the HTMX request was made.
     *
     * @return string|null The current browser URL or null if not provided
     */
    public function getCurrentUrl(): ?string
    {
        return $this->header('HX-Current-Url');
    }

    /**
     * The user response to an `hx-prompt`.
     *
     * @return string|null The prompt response or null if not provided
     */
    public function getPromptResponse(): ?string
    {
        return $this->header('HX-Prompt');
    }

    /**
     * The `id` of the target element if it exists.
     *
     * @return string|null The target element ID or null if not provided
     */
    public function getTarget(): ?string
    {
        return $this->header('HX-Target');
    }

    /**
     * The `name` of the triggered element if it exists.
     *
     * @return string|null The trigger element name or null if not provided
     */
    public function getTriggerName(): ?string
    {
        return $this->header('HX-Trigger-Name');
    }

    /**
     * The `id` of the triggered element if it exists.
     *
     * @return string|null The trigger element ID or null if not provided
     */
    public function getTriggerId(): ?string
    {
        return $this->header('HX-Trigger');
    }
}
