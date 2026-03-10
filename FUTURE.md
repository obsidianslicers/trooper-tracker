# Club App Technical Blueprint: Laravel + Svelte + Capacitor

## 1. Architecture Overview

- **Backend:** Laravel 11+ acting as a "Headless" API.
- **Architecture Pattern:** CQRS-lite (Magic Bus). A single API entry point (`/api/bus`) handles all "Messages" (Commands/Queries).
- **Frontend:** Svelte 5 (SPA Mode). A single codebase built with SvelteKit.
- **Mobile Wrapper:** Capacitor. Wraps the Svelte build into native iOS/Android binaries.
- **Real-time:** Firebase Cloud Messaging (FCM) for push notifications.

## 2. Laravel Backend: The Gateway Implementation

### The Gateway Controller

This single controller resolves classes, injects identity, and handles global safety checks.

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageBusController extends Controller
{
    public function __invoke(Request $request)
    {
        $type = $request->input('type');
        $class = "App\\Messages\\{$type}";

        if (!class_exists($class)) {
            return response()->json(['error' => "Message [{$type}] not found."], 404);
        }

        try {
            $message = app()->makeWith($class, ['params' => $request->input('payload', [])]);

            if (property_exists($message, 'actor')) {
                $message->actor = $request->user();
            }

            if (method_exists($message, 'authorize') && !$message->authorize()) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }

            return DB::transaction(function () use ($message) {
                return response()->json([
                    'data' => $message->handle(),
                    'status' => 'success'
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Server Error'], 500);
        }
    }
}
```

## 3. The Message Structure (Commonized)

Commands/Queries handle their own validation and authorization.

```php
namespace App\Messages;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseMessage {
    public ?\App\Models\User $actor = null;
    public array $params = [];
}

class SaveEventCommand extends BaseMessage {
    public function __construct(array $params) {
        $v = Validator::make($params, [
            'name' => 'required|string|min:5',
            'start_date' => 'required|date',
        ]);

        if ($v->fails()) throw new ValidationException($v);
        $this->params = $v->validated();
    }

    public function authorize(): bool {
        return $this->actor && $this->actor->role === 'admin';
    }

    public function handle() {
        return \App\Models\Event::create($this->params);
    }
}
```

## 4. The Frontend: Svelte 5 SPA

### SvelteKit Configuration

`src/routes/+layout.js`

```js
export const ssr = false;
export const prerender = true;
```

`svelte.config.js`

```js
import adapter from '@sveltejs/adapter-static';

export default {
    kit: {
        adapter: adapter({
            pages: 'dist',
            assets: 'dist',
            fallback: 'index.html'
        })
    }
};
```

## 5. Mobile Bridge & API Helper

Standardizes how Svelte talks to the Laravel Bus and handles native mobile persistence.

`src/lib/api.js`

```js
import { Preferences } from '@capacitor/preferences';
import { goto } from '$app/navigation';

export async function send(type, payload = {}) {
    const { value: token } = await Preferences.get({ key: 'auth_token' });

    const response = await fetch('https://api.yourclub.com/api/bus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ type, payload })
    });

    if (response.status === 401) {
        await Preferences.remove({ key: 'auth_token' });
        goto('/login');
    }

    return response.json();
}
```

## 6. Implementation Notes

- **Routing:** Use `[id]` for required and `[[optional]]` for optional parameters.
- **Layouts:** Use Route Groups like `(app)` and `(admin)` for automatic UI shielding.
- **Security:** Svelte handles UX protection; Laravel Bus handles actual data authorization.
- **Capacitor:** Use `npx cap sync` to push Svelte `dist` changes to native mobile folders.

## 7. Mobile OAuth Registration & Callbacks

Mobile apps require **Deep Linking** to return the user from the browser back into the application.

### Google Console Setup
* **Application Type:** Web Application (Capacitor uses a web-view).
* **Origins:** `http://localhost` and `capacitor://localhost`.
* **Redirect URI:** Use your production web URL (e.g., `https://club.com/auth/callback`).

### Deep Link Configuration
* **Android:** Configure an `intent-filter` in `AndroidManifest.xml` for your callback domain.
* **iOS:** Add your domain to `Associated Domains` in Xcode.
* **Capacitor Plugin:** Use `@capacitor/app` to listen for the "appUrlOpen" event.

### The Redirect Logic
When the app is opened via a Deep Link, the `App.addListener('appUrlOpen', ...)` event triggers. You parse the URL, extract the `code`, and send it to your `LoginWithSocialCommand` on the Laravel Bus.

## 8. Unified Deep Link Strategy (Web & Mobile)

To maintain a consistent UX across all platforms, use the **Universal Link** pattern.

### The Redirect Flow
1. **Initiate:** Svelte opens the browser to Google/Xenforo.
2. **Callback:** Provider redirects to `https://club.com/auth/callback`.
3. **Web:** The standard Svelte route handles the `code`.
4. **Mobile:** The OS intercepts the URL and opens the Capacitor App.
5. **App Listener:** The `appUrlOpen` event captures the URL, extracts the `code`, and dispatches the `LoginWithSocialCommand`.

### Key Configuration
* **Android:** `assetlinks.json` must be hosted at `.well-known/assetlinks.json` on your Laravel server.
* **iOS:** `apple-app-site-association` (AASA) file must be hosted at `.well-known/apple-app-site-association`.
* **Benefit:** This proves to the OS that your app "owns" the domain, allowing for seamless redirects without the "Open in this app?" prompt.

When Google redirects back to your URL on a mobile device, the OS "snaps" the user into the app. You need a listener in your root +layout.svelte to catch that URL and send the data to the Bus.

`src/routes/+layout.svelte`

```js
<script>
  import { onMount } from 'svelte';
  import { App } from '@capacitor/app';
  import { goto } from '$app/navigation';
  import { send } from '$lib/api';

  onMount(() => {
    // This listener catches the "snap" back from the browser
    App.addListener('appUrlOpen', async (data) => {
      // data.url example: https://yourclub.com/auth/callback?code=xyz...
      const url = new URL(data.url);
      const code = url.searchParams.get('code');
      const provider = url.pathname.includes('google') ? 'google' : 'xenforo';

      if (code) {
        // Send to your commonized Magic Bus command
        const response = await send('LoginWithSocialCommand', { provider, code });
        
        // Save token and go to dashboard
        localStorage.setItem('auth_token', response.data.token);
        goto('/dashboard');
      }
    });
  });
</script>
```