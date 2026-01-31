# XenForo OAuth2 Provider for Laravel Socialite

This custom Socialite provider enables OAuth2 authentication with XenForo 2.3+ forums.

## Features

- Full OAuth2 support for XenForo 2.3+
- Supports both confidential and public client types
- PKCE support (recommended for all clients)
- User profile retrieval including avatar URLs
- Configurable scopes

## Configuration

### 1. XenForo Setup

On your XenForo forum, create an OAuth2 client:

1. Navigate to **Admin CP > Setup > OAuth2 clients**
2. Click **Add OAuth2 client**
3. Fill in the following:
   - **Title**: Your Application Name
   - **Description**: Brief description of your app
   - **Homepage URL**: Your application's homepage
   - **Client Type**: 
     - `Confidential` - For server-side apps (default, recommended for Laravel)
     - `Public` - For JavaScript/mobile apps (uses PKCE)
   - **Redirect URIs**: Add your callback URL(s)
     - Example: `https://yourdomain.com/auth/xenforo/callback`

4. After creation, note your **Client ID** and **Client Secret**

### 2. Laravel Environment Variables

Add the following to your `.env` file:

```env
XENFORO_CLIENT_ID=your-client-id-here
XENFORO_CLIENT_SECRET=your-client-secret-here
XENFORO_REDIRECT_URI=https://yourdomain.com/auth/xenforo/callback
XENFORO_BASE_URL=https://forum.example.com
```

**Note**: `XENFORO_BASE_URL` should be the base URL of your XenForo installation (without trailing slash).

### 3. Configuration File

The provider configuration is already set up in `config/services.php`:

```php
'xenforo' => [
    'client_id' => env('XENFORO_CLIENT_ID'),
    'client_secret' => env('XENFORO_CLIENT_SECRET'),
    'redirect' => env('XENFORO_REDIRECT_URI'),
    'base_url' => env('XENFORO_BASE_URL'),
],
```

## Usage

### Basic Authentication Flow

#### Redirect to XenForo

```php
use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/xenforo', function () {
    return Socialite::driver('xenforo')->redirect();
});
```

#### Handle Callback

```php
Route::get('/auth/xenforo/callback', function () {
    $user = Socialite::driver('xenforo')->user();
    
    // User data available:
    $user->getId();        // XenForo user_id
    $user->getNickname();  // Username
    $user->getName();      // Username (same as nickname)
    $user->getEmail();     // Email address
    $user->getAvatar();    // Avatar URL (original size if available)
    
    // Access token for API calls
    $token = $user->token;
    $refreshToken = $user->refreshToken;
    $expiresIn = $user->expiresIn;
    
    // Find or create user in your database
    // ...
    
    return redirect('/dashboard');
});
```

### Custom Scopes

You can request additional scopes:

```php
return Socialite::driver('xenforo')
    ->scopes(['read', 'profile', 'post'])
    ->redirect();
```

**Available XenForo Scopes**:
- `read` - Read user data
- `profile` - Access profile information
- `post` - Create posts/threads (if enabled)

### Stateless Authentication

For API-based authentication:

```php
$user = Socialite::driver('xenforo')->stateless()->user();
```

## XenForo API Endpoints

The provider uses the following XenForo endpoints:

- **Authorization**: `{base_url}/oauth/authorize`
- **Token**: `{base_url}/oauth/token`
- **User Info**: `{base_url}/api/me`
- **Revocation**: `{base_url}/oauth/revoke` (for revoking tokens)

## User Data Mapping

The provider maps XenForo user data as follows:

| Socialite Field | XenForo Field | Description |
|----------------|---------------|-------------|
| `id` | `user_id` | Unique user identifier |
| `nickname` | `username` | Username |
| `name` | `username` | Display name (same as username) |
| `email` | `email` | Email address |
| `avatar` | `avatar_urls.*` | Avatar URL (prefers original > large > medium) |

## Avatar Sizes

The provider attempts to retrieve avatars in the following order:
1. Original (`o`) - Full resolution
2. Large (`l`) - Large size
3. Medium (`m`) - Medium size

If no avatar is available, `null` is returned.

## Error Handling

```php
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

try {
    $user = Socialite::driver('xenforo')->user();
} catch (InvalidStateException $e) {
    // Invalid state, possible CSRF attempt
    return redirect('/login')->withError('Authentication failed. Please try again.');
} catch (\Exception $e) {
    // Other authentication errors
    return redirect('/login')->withError('Unable to authenticate with XenForo.');
}
```

## Testing

Run the unit tests:

```bash
php artisan test --filter=XenforoProviderTest
```

## Security Considerations

1. **Always use HTTPS** in production for both your app and XenForo installation
2. **Validate redirect URIs** - Only add trusted redirect URIs in XenForo
3. **Keep secrets secure** - Never commit credentials to version control
4. **Use state parameter** - Socialite automatically includes CSRF protection
5. **Token storage** - Store access tokens securely and encrypt sensitive data
6. **Token refresh** - Implement token refresh for long-lived sessions

## PKCE Support

For public clients (JavaScript/mobile apps), XenForo 2.3 supports PKCE. While this provider is configured for confidential clients by default, PKCE can be enabled:

```php
// This would require extending the provider to add PKCE support
// PKCE is recommended for all client types as an additional security layer
```

## Troubleshooting

### "Invalid redirect URI"
- Ensure the redirect URI in your `.env` matches exactly what's configured in XenForo
- Check for trailing slashes and protocol (http vs https)

### "Invalid client credentials"
- Verify `XENFORO_CLIENT_ID` and `XENFORO_CLIENT_SECRET` are correct
- Ensure there are no extra spaces in your `.env` file

### "User data not returned"
- Check that `XENFORO_BASE_URL` is set correctly without trailing slash
- Verify the XenForo API is accessible
- Ensure the access token has the correct scopes

### "SSL certificate problem"
- In development, you may need to configure Guzzle to accept self-signed certificates
- **Never disable SSL verification in production**

## Reference

- [XenForo OAuth2 Documentation](https://xenforo.com/community/threads/have-you-seen-single-sign-on-and-more-with-oauth2-in-xenforo-2-3.218304/)
- [Laravel Socialite Documentation](https://laravel.com/docs/12.x/socialite)
- [OAuth 2.0 Specification](https://oauth.net/2/)
- [PKCE Extension](https://oauth.net/2/pkce/)
