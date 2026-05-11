# OAuth 2.0 Authorization Code Flow in Troop Tracker

This document explains how Troop Tracker implements secure OAuth 2.0 authentication for both mobile (Capacitor) and web platforms using Google and XenForo as OAuth providers.

## Overview: Authorization Code Flow

Troop Tracker uses the **OAuth 2.0 Authorization Code Flow**, which is the **most secure** flow for native and web applications.

### The Flow (Step-by-Step)

```
┌─────────────┐                                    ┌──────────────┐
│ Mobile/Web  │                                    │ Google or    │
│ App         │                                    │ XenForo      │
└─────────────┘                                    └──────────────┘
      │                                                   │
      │ 1. Opens OAuth provider login                    │
      ├──────────────────────────────────────────────────>
      │                                                   │
      │ 2. User logs in & grants permission              │
      │                                                   │
      │ 3. Redirects with authorization CODE             │
      <──────────────────────────────────────────────────┤
      │                                                   │
      │ 4. App extracts code, sends to backend           │
      ├─────────────────────┐                            │
      │                     │                            │
      │                ┌─────────────┐                   │
      │                │ Your Server │                   │
      │                │ (Laravel)   │                   │
      │                └─────────────┘                   │
      │                     │ 5. Server exchanges code  │
      │                     │    for token (using       │
      │                     │    CLIENT_SECRET)         │
      │                     │                            │
      │                     ├──────────────────────────>│
      │                     │                            │
      │                     │ 6. Returns token          │
      │                     │                            │
      │                     │<──────────────────────────┤
      │                     │                            │
      │ 7. Server validates & logs user in               │
      │<────────────────────┤                            │
      │                                                   │
```

### Why This Flow is Secure

1. **Authorization Code is short-lived** — Can only be used once and expires quickly
2. **TOKEN EXCHANGE happens server-to-server** — Your backend uses the `CLIENT_SECRET` (never exposed to the app)
3. **Client Secret is never on the device** — Only the backend knows the secret
4. **Tokens stay on the server** — The mobile/web app never directly handles tokens
5. **Each request is authenticated** — The server validates the user before responding

**The app only ever sees the authorization code, never the token itself.**

## Platform-Specific Redirect URIs

### Web Browser Flow

1. User clicks "Login with Google" in the web browser
2. Frontend redirects: `window.location.assign(oauth_url)`
3. Browser navigates to `https://google.com/oauth/authorize?redirect_uri=https://app.example.com/auth/callback/google`
4. After login, Google redirects to: `https://app.example.com/auth/callback/google?code=ABC123&state=xyz`
5. SvelteKit router captures the query params and calls `oauthState.finalizeCallback()`
6. Code is sent to Laravel backend

### Mobile (Capacitor) Flow

1. User clicks "Login with Google" in the mobile app
2. Frontend calls `Browser.open(oauth_url)` — opens native browser overlay
3. Capacitor opens: `https://google.com/oauth/authorize?redirect_uri=trooper://oauth-callback/google`
4. After login, Google redirects to the **deep link**: `trooper://oauth-callback/google?code=ABC123&state=xyz`
5. Operating system recognizes the `trooper://` scheme and routes it back to the app
6. Capacitor fires `appUrlOpen` event
7. `platformState.listenForAppUrlOpen()` captures the deep link URL and extracts the code
8. `oauthState.finalizeCallback()` is called
9. Code is sent to Laravel backend
10. `Browser.close()` closes the native overlay

## Environment Configuration

### tracker-ui Configuration (Dynamic from Server)

Instead of hardcoding OAuth configuration in `.env`, Troop Tracker fetches configuration dynamically from the server via the **`App.GetConfig`** gateway message call. This allows:

- **Single codebase** for all environments (dev, staging, production)
- **Server-side control** of OAuth URLs without recompilation
- **Runtime adaptation** based on the server's configuration

**Example Response from App.GetConfig:**

```json
{
  "oauth": {
    "google": {
      "clientId": "your-client-id.apps.googleusercontent.com",
      "redirectUriWeb": "https://app.example.com/auth/callback/google",
      "redirectUriNative": "trooper://oauth-callback/google"
    },
    "xenforo": {
      "clientId": "your-xenforo-client-id",
      "redirectUriWeb": "https://app.example.com/auth/callback/xenforo",
      "redirectUriNative": "trooper://oauth-callback/xenforo"
    }
  }
}
```

The frontend fetches this config once at app startup and stores it in a state manager for use during OAuth flows.

### tracker-app/.env

Configure OAuth credentials on your backend server:

```env
# Google OAuth (from Google Cloud Console)
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://app.example.com/api/oauth/callback/google

# XenForo OAuth (from XenForo settings)
XENFORO_CLIENT_ID=your-xenforo-client-id
XENFORO_CLIENT_SECRET=your-xenforo-client-secret
XENFORO_REDIRECT_URI=https://app.example.com/api/oauth/callback/xenforo
```

The backend exposes these values (minus the secrets) via the **`App.GetConfig`** endpoint, which the frontend calls to get the client IDs and redirect URIs.

## Implementation in Troop Tracker

### Configuration Retrieval

The frontend calls `App.GetConfig` at startup to fetch OAuth configuration from the server:

```typescript
// In your app initialization (e.g., +layout.svelte or a setup service)
const configResponse = await gateway.send(new GetConfigMessage());

// Store in a state manager
configState.setOauthConfig({
    google: configResponse.oauth.google,
    xenforo: configResponse.oauth.xenforo,
});
```

This happens once when the app loads, and the configuration is available to all OAuth flows.

### Frontend (tracker-ui)

**File:** `src/lib/states/platform-state.svelte.ts`

Determines the platform and opens OAuth:

```typescript
async openAuthTarget(target_url: string): Promise<void> {
    if (this.isNative()) {
        // Mobile: open in native browser overlay
        await Browser.open({
            url: target_url,
            presentationStyle: 'fullscreen',
        });
        return;
    }

    if (this.isBrowser()) {
        // Web: full page navigation
        window.location.assign(target_url);
    }
}
```

Listens for deep links on native:

```typescript
async listenForAppUrlOpen(callback: AppUrlOpenCallback): Promise<PluginListenerHandle | null> {
    if (!this.isNative()) {
        return null;
    }

    return await App.addListener('appUrlOpen', async (event: URLOpenListenerEvent) => {
        if (!event.url) {
            return;
        }

        // Deep link URL (e.g., trooper://oauth-callback/google?code=ABC&state=xyz)
        await callback(new URL(event.url));
    });
}
```

**File:** `src/lib/states/oauth-state.svelte.ts`

Initiates OAuth and handles the callback:

```typescript
async launch(providerUrl: string): Promise<void> {
    await platformState.openAuthTarget(this.buildProviderUrl(providerUrl));
}

async finalizeCallback(result: OauthCallbackResult): Promise<void> {
    // result.code = authorization code from provider
    // result.oauth = provider name (google, xenforo, etc)

    // Send code to Laravel backend via gateway message
    const response = await gateway.send(
        new CompleteOauthMessage({
            code: result.code,
            provider: result.oauth,
        })
    );

    // Backend returns authenticated user or registration flow
    const action = response?.action;

    if (action === 'authenticated') {
        // User is logged in, redirect to dashboard
        await goto('#/');
        return;
    }

    if (action === 'register') {
        // First time login, show registration form
        this.pendingRegistration = {
            email: response?.email ?? null,
            name: response?.name ?? null,
            registrationMethod: response?.registrationMethod ?? null,
        };
        await goto('#/auth/register');
        return;
    }
}
```

### Backend (tracker-app)

**Message:** `app/Messages/CompleteOauth.php`

Frontend sends the authorization code to the backend via the gateway:

```typescript
// Frontend sends code to backend
const response = await gateway.send(new CompleteOauthMessage({
    code: result.code,
    provider: result.oauth, // 'google' or 'xenforo'
}));
```

**Handler:** `app/Messages/Handlers/CompleteOauthHandler.php`

The backend handler exchanges the authorization code for a token:

```php
declare(strict_types=1);

class CompleteOauthHandler
{
    public function __construct(
        private OauthService $oauth_service,
    ) {}

    public function handle(CompleteOauth $message): CompleteOauthResponse
    {
        try {
            // Exchange code for token (server-to-server, using CLIENT_SECRET)
            $token = $this->oauth_service->exchangeCodeForToken(
                provider: $message->provider,
                code: $message->code,
                clientId: config("oauth.{$message->provider}.client_id"),
                clientSecret: config("oauth.{$message->provider}.client_secret"), // SECURE!
                redirectUri: config("oauth.{$message->provider}.redirect_uri"),
            );

            // Validate token and find/create trooper
            $trooper = $this->oauth_service->findOrCreateTrooper(
                provider: $message->provider,
                token: $token,
            );

            // Return response indicating success or registration flow
            if ($trooper->is_verified) {
                return new CompleteOauthResponse(
                    action: 'authenticated',
                    trooper: $trooper,
                    message: 'Login successful!',
                );
            }

            return new CompleteOauthResponse(
                action: 'register',
                email: $trooper->email,
                name: $trooper->name,
                registrationMethod: $message->provider,
                message: 'Complete your signup to finish linking your account.',
            );
        } catch (OauthException $e) {
            throw $e; // Let gateway error handling catch it
        }
    }
}
```

## OAuth Provider Registration

### Google Cloud Console

Register one OAuth client with both redirect URIs:

1. Go to Google Cloud Console → Credentials
2. Create OAuth 2.0 Client ID (application type: "Web application")
3. Add Authorized redirect URIs:
   - `https://app.example.com/auth/callback/google`
   - `trooper://oauth-callback/google`

### XenForo Settings

Configure in XenForo Admin Panel → Setup → OAuth:

1. Create OAuth client
2. Set Client ID and Client Secret
3. Add Authorized redirect URIs:
   - `https://app.example.com/auth/callback/xenforo`
   - `trooper://oauth-callback/xenforo`

## Security Checklist

- [ ] Client secrets are in `.env`, never committed to Git
- [ ] Deep link scheme (`trooper://`) is registered in native app config (Capacitor)
- [ ] Backend validates all redirect URIs against a whitelist
- [ ] Authorization code is exchanged server-to-server using the client secret
- [ ] Tokens are validated and expired properly
- [ ] HTTPS is enforced in production
- [ ] State parameter is used to prevent CSRF attacks
- [ ] Sensitive endpoints require authentication

## Troubleshooting

### "Invalid redirect_uri" Error

- Verify the redirect URI in `.env` matches exactly what's registered in Google/XenForo
- Check that trailing slashes match (some providers are strict)
- Clear browser cache and retry

### Deep Link Not Captured on Mobile

- Verify the deep link scheme is registered in `capacitor.config.ts`
- Check that `listenForAppUrlOpen()` listener is set up before opening the browser
- Test on physical device (some simulators don't handle deep links correctly)

### Authorization Code Not Sent to Backend

- Check browser console for errors in `finalizeCallback()`
- Verify `authState.completeOauth()` is making the POST request to your backend
- Check backend logs for 400/401 errors on the callback endpoint

## References

- [OAuth 2.0 Authorization Framework](https://tools.ietf.org/html/rfc6749)
- [Capacitor Browser API](https://capacitorjs.com/docs/apis/browser)
- [Capacitor App API (Deep Links)](https://capacitorjs.com/docs/apis/app)
- [Google OAuth Documentation](https://developers.google.com/identity/protocols/oauth2)
