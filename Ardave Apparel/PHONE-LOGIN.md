# SMS login with Twilio Verify

Live SMS is disabled unless all settings below are present. No local code, JSON
file, legacy `is_verified` flag, or contact phone number can authorize SMS login.
Only a matching Twilio Verify `approved` check can authorize it.

## Server setup

Supply these **server-side environment variables** to the Apache/PHP process:

```text
SMS_LOGIN_ENABLED=true
TWILIO_ACCOUNT_SID=<your Twilio Account SID>
TWILIO_AUTH_TOKEN=<your Twilio Auth Token>
TWILIO_VERIFY_SERVICE_SID=<your Verify Service SID>
```

Configure the actual values outside the project, for example in the server's
private Apache configuration or service environment. Restart Apache so PHP sees
the settings. Never put actual values in source, browser code, screenshots, or
commits. `.env.example` contains only empty placeholders; `.gitignore` excludes
local environment files and `.htaccess` denies HTTP access to them and the legacy
JSON OTP store. On a different web server, configure equivalent deny rules.

Create a Twilio Verify Service with SMS enabled; use its VA-prefixed SID, not a
Messaging Service SID. Enable the Philippines in Verify geographic permissions.
Use a funded account; trial restrictions require an eligible verified recipient.
Use the default 10-minute validity and a 4–10 digit code (6 recommended). PHP cURL
must have a working trusted CA bundle. TLS verification is deliberately enabled;
configure `curl.cainfo` if the XAMPP certificate store needs setup. Serve production
login pages over HTTPS. Requests time out after three seconds and fail closed.

Apply the additive migration on each installation:

```powershell
C:\xampp\php\php.exe database/migrate-phone-auth.php
```

It has already been applied to this local XAMPP database. It is safe to rerun and
does not change or automatically trust existing `users.phone`/`customers.phone`.
The separate `auth_phone_identities` table enforces one verified number per user
and one user per number. It references the existing users table.

## Using the flow

1. Sign in using the existing email/password or Google method.
2. Open **My Profile → Set up / change verified phone login** (`customer/phone.php`).
3. Enter your Philippine mobile number and request a code. Verify it with the
   provider. Only then is the number linked; an existing linked number remains
   valid until its replacement is successfully verified.
4. Log out. Choose **Continue with SMS code** on the normal login page.
5. Request and enter the SMS code. The server checks it with Twilio before calling
   the existing session initializer, which regenerates the session ID and loads
   the current database role. Admins retain the dashboard redirect. Customers
   retain valid in-app return destinations.

Accepted formats include `09171234567`, `9171234567`, `639171234567`, and
`+63 (917) 123-4567`; all normalize to `+639171234567`. Other countries, landlines,
extensions, and malformed values are rejected.

The response deliberately says **if eligible**. Neither that page nor Twilio's
`pending` response proves delivery. Unknown accounts and provider send failures
receive the same public response. Provider details, credentials, and verification
SIDs are never returned to the browser. Bounded provider timeouts and a minimum
response duration reduce account discovery through timing.

## Protections and operation

- Session-bound random challenges, POST-only mutations, CSRF tokens, and no-store
  responses. Phone enrollment requires an authenticated account on every step.
- Server-enforced 60-second resend cooldown; 10-minute maximum challenge lifetime;
  resending does not extend the challenge. At most five checks per challenge.
- Database-backed limits shared across sessions: sends limited to 5 per phone/hour,
  10 per IP/hour, and 100 globally/hour; authenticated setup also has 5 per user
  per 15 minutes. Checks: 10 per phone/15 minutes, 30 per IP/15 minutes, and 10 per
  authenticated user/15 minutes. Twilio may impose additional limits.
- Atomic unique verification consumption prevents reuse across concurrent sessions.
  The user/phone association is rechecked when login completes. No fallback code
  or local comparison can grant access when Twilio fails or is disabled.
- The IP limiter uses `REMOTE_ADDR`; behind a reverse proxy, configure trusted IP
  restoration at the web server. Client-supplied forwarding headers are not trusted.
- Periodically delete expired `auth_phone_limits` rows and `auth_phone_challenges`
  rows whose `expires_at` is over a day old. Consumed verification SIDs contain no
  codes; keep them as replay tombstones. No OTP codes are stored locally.
- The old `API/smsAPI.php` now reports failure, not fake delivery. `API/otpAPI.php`
  is not used by this flow. Existing email OTP/password/Google behavior is separate.

## Verification status

Run the negative/security tests:

```powershell
C:\xampp\php\php.exe tests/phone-auth.php
```

These test normalization, invalid inputs, disabled-provider failure, CSRF,
unsafe redirects, persisted rate limiting, unique phone ownership, and one-time
consumption constraints. Temporary database rows are rolled back. Tests do not
mock a successful Twilio response or simulate a successful OTP login.

Local Chrome checks cover 1440px, 390px, and 320px phone-page rendering/input,
disabled sends, invalid CSRF, disabled send/check/resend, missing challenges,
authentication-required enrollment, existing login controls, and HTTP denial of
environment/legacy OTP files. PHP syntax checks pass.

**Real text delivery and approved OTP login have not been tested:** credentials
are absent. After setup, test real enrollment, SMS receipt, a wrong code, resend
before/after 60 seconds, expiry, successful login, reuse rejection, changing to a
number owned by another account, and customer/admin return destinations. Confirm
provider failures never create a login session. An eligible unknown number must
receive the same public request message without gaining access.

Provider references:
- https://www.twilio.com/docs/verify/api/verification
- https://www.twilio.com/docs/verify/api/verification-check
- https://www.twilio.com/docs/verify/api/rate-limits-and-timeouts
