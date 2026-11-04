# AMIVON HOSTING

Responsive customer portal for AMIVON HOSTING.

## Run
```bash
npm install
npm start
```

## Render environment variables

Set secrets in Render Dashboard → amivon-hosting → Environment. Never commit API keys or paste them into public code.

Required for customer accounts:
- `DATABASE_URL`: Internal Database URL from the Render PostgreSQL database details.
- `JWT_SECRET`: A long, randomly generated secret (at least 32 random bytes).
- `APP_BASE_URL`: `https://amivon-hosting.onrender.com` until the custom domain is connected.

Namecheap domain availability:
- `NAMECHEAP_API_USER`
- `NAMECHEAP_API_KEY`
- `NAMECHEAP_USERNAME`
- `NAMECHEAP_CLIENT_IP`: actual outbound IP of the caller, enabled in Namecheap API access. The Render caller's egress IP must match the Namecheap whitelist.

Paystack:
- `PAYSTACK_SECRET_KEY`: Paystack secret key. Checkout verifies the transaction server-side before provisioning.

Webuzo provisioning:
- `WEBUZO_HOST`: server hostname/IP, without protocol.
- `WEBUZO_API_USER`: Webuzo administrator or reseller API user.
- `WEBUZO_API_KEY`: API key generated in Webuzo Admin Panel → Settings → API Keys.

Hosting package mapping:
Set `HOSTING_PLANS_JSON` to JSON mapping each public plan to its exact Webuzo package name and price in KES cents. Example shape (replace all example values with your actual packages and prices):
```json
{
  "starter": { "name": "Personal Hosting", "amountKobo": 0, "webuzoPlan": "EXACT_WEBUZO_PACKAGE_NAME" },
  "business": { "name": "Business Hosting", "amountKobo": 0, "webuzoPlan": "EXACT_WEBUZO_PACKAGE_NAME" },
  "reseller": { "name": "Reseller Hosting", "amountKobo": 0, "webuzoPlan": "EXACT_WEBUZO_PACKAGE_NAME" }
}
```
Replace each zero with the amount in the smallest KES unit (KES price × 100). Zero values intentionally do not enable checkout.

## Implemented
- Customer signup/sign-in with hashed passwords, HTTP-only session cookies and rate limiting.
- Persistent customer/order schema initialized in PostgreSQL.
- Namecheap domain availability lookup.
- Paystack initialization and server-side transaction verification, including amount/currency checks.
- Webuzo Admin API Add User provisioning after verified payment.
- Customer order dashboard and encrypted storage of generated hosting passwords.

## Production checklist
- The current Render PostgreSQL free instance is temporary and expires on 2026-12-04; upgrade it or migrate to a durable database before storing real customer records.
- Configure every required environment variable and test with Paystack test keys before going live.
- Namecheap API requires API access approval and a whitelisted caller IP. Render free egress may not provide a single static address; use an eligible static egress option or route Namecheap calls through the provisioned Namecheap VPS.
- Webuzo VPS must be provisioned, Webuzo installed, API key created and the exact package names configured.
- Configure Paystack live keys only after successful test transactions.
- Connect `amivonenterhosts.com` after the app and integrations pass tests.
- The Webuzo API call uses the documented Admin API `act=add_user` operation. Verify it against the actual Webuzo Business server before accepting live payments.
