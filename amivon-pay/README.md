# AMIVON PAY — Paysasa test deployment

Standalone PHP 8.3 application in the amivon-pay folder.

Included: customer registration/login, KES wallet ledger, Paysasa hosted checkout, M-Pesa/wallet method selection, session status verification, one-time wallet credit after Paysasa confirms paid/completed, transaction history and admin overview.

Render configuration: Docker runtime; Docker context directory amivon-pay; Dockerfile path Dockerfile. PHP 8.3 Apache image includes PDO SQLite and cURL.

Set Render environment variables:
- APP_ENV=production
- APP_URL=https://amivon-pay.onrender.com
- ADMIN_EMAIL=amivoncompanyltd@gmail.com
- ADMIN_PASSWORD=strong unique password
- PAYSASA_BASE_URL=https://api.paysasa.com/api/v1
- PAYSASA_API_KEY=your Paysasa TEST composite API key
- PAYSASA_BUSINESS_ID=your Paysasa business ID

Do not commit secrets.

IMPORTANT: This initial Render free-service setup uses SQLite on the service filesystem. Render free services have ephemeral filesystems, so customer and wallet data can be lost after restart/redeploy. This is strictly for integration testing; do not use for production funds. Before production, migrate to durable PostgreSQL or attach a persistent disk on a paid service and implement the exact Paysasa signed-webhook verification specification. P2P transfers and payouts are not enabled because their full request schemas were not included in the supplied API excerpt.
