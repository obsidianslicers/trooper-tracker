# Authentication & Onboarding

Unified signup flow for all identity providers (Email, Google OAuth, XenForo OAuth).

**Key Features:**
- Multi-provider authentication with unified registration
- Mandatory admin approval before access
- Secure session-based registration flow

---

## Authentication Flow

Troop Tracker separates identity verification from registration:

- **Identity**: Proves who the user is
- **Registration**: Collects required information
- **Approval**: Admins verify and activate accounts

---

## Identity Providers

### ✅ Email Signup
1. User clicks **Sign up with Email**
2. System creates a short‑lived `registration_auth` session key
3. User is redirected to `/register`

### ✅ Google OAuth
1. User clicks **Sign up with Google**
2. Redirect to Google OAuth
3. On callback:
   - If user exists and is approved → login
   - If user exists and is pending → show pending screen
   - If new user → store OAuth data + create `registration_auth` → redirect to `/register`

### ✅ XenForo OAuth
Same flow as Google OAuth.

When you enable the full XenForo integration, XenForo is more than an auth provider.
It also becomes the source for forum identity, event thread automation, support upgrade reporting, and profile banner display.

If you are using XenForo as a required provider, also see:

- [XENFORO_OAUTH.md](XENFORO_OAUTH.md)

That guide documents:

- required XenForo add-ons
- API key scopes
- forum automation features
- user synchronization
- support totals from XenForo upgrades
- profile banners from XenForo user groups

---

## Registration Completion Form

All users — regardless of identity provider — must complete the same form:

- Name  
- Email (prefilled for OAuth users)  
- TK Number  
- Password (email users only)  
- Terms acceptance  

After submission:

- User is created with `status = pending`
- OAuth logins (if any) are attached
- Session keys are cleared
- User is shown the **Pending Approval** screen

---

## Admin Approval

Admins review pending users and approve them.  
Once approved, users may log in using:

- Email + password  
- Google OAuth  
- XenForo OAuth

**OAuth Configuration:** See [ENVIRONMENT_VARIABLES.md](ENVIRONMENT_VARIABLES.md) for OAuth client ID/secret setup.  

## XenForo-Required Mode

Troop Tracker can be configured to require XenForo-linked accounts.

When `TRACKER_REQUIRE_XENFORO=true`:

- email/password login is disabled
- non-XenForo OAuth providers are blocked
- authenticated users without a linked XenForo account are redirected to complete XenForo linking

This mode is recommended when Troop Tracker relies on XenForo for:

- user identity
- forum thread creation and updates
- support totals from XenForo upgrades
- profile banners from XenForo user groups
- XenForo user synchronization

---

## Mermaid Flowchart

```mermaid
flowchart TD

    A[User chooses signup method] --> B{Identity Provider}

    B -->|Email| C1[Email Start Endpoint<br>/auth/email/start]
    B -->|Google OAuth| C2[Google OAuth Redirect]
    B -->|XenForo OAuth| C3[XenForo OAuth Redirect]

    C1 --> D1[Set registration_auth session]
    C2 --> D2[OAuth Callback<br>Check existing user]
    C3 --> D3[OAuth Callback<br>Check existing user]

    D2 -->|Existing + Approved| L[Login User]
    D3 -->|Existing + Approved| L

    D2 -->|Existing + Pending| P[Show Pending Approval]
    D3 -->|Existing + Pending| P

    D2 -->|New User| E[Store oauth_pending + registration_auth]
    D3 -->|New User| E

    D1 --> F[Redirect to /register]
    E --> F

    F --> G[Registration Completion Form]

    G --> H[Create User<br>status=pending]
    H --> I[Attach OAuth Login if applicable]
    I --> J[Clear Session]
    J --> P
