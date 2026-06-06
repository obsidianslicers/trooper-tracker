<?php

declare(strict_types=1);

namespace App\Messages\App\Queries;

use App\Messages\Message;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 */
final readonly class GetConfig extends Message
{
}