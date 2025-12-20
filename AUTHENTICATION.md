# User Signup & Onboarding Flow

This document describes the unified signup and onboarding flow for Troop Tracker.  
All identity providers — **Email**, **Google OAuth**, and **XenForo OAuth** — follow the same secure, consistent registration pipeline.

The system ensures:

- Every user completes the **Registration Completion Form**
- Every user is **approved by an admin/moderator** before gaining access

---

## Overview

Troop Tracker separates **identity** from **registration**:

- **Identity step**  
  Proves who the user is (Email, Google, XenForo)

- **Registration step**  
  Collects required information (TK number, name, etc.)

- **Approval step**  
  Admins verify and activate the account

This ensures a clean, secure, and unified onboarding experience.

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
