
# LATAM API (Single-module)

A Drupal module that exposes JSON endpoints and centralizes per-country API settings. Countries are configurable. Supports API-Key or OAuth2 client_credentials with a built-in token cache. Includes permission gating, optional shared-secret header, and optional rate limiting.

## Features
- Configurable list of country codes.
- Per-country settings: base URL, API key, locale, OAuth token URL, client id/secret, scope, and a per-country "pinpoint" URL.
- Endpoints:
  - `GET /api/latam/ping?cc=XX` – returns active country config snapshot (non-sensitive fields).
  - `POST /api/latam/pinpoint?cc=XX` – forwards arbitrary JSON to the configured `pinpoint_url` with automatic Authorization.
- Access control via permission `access latam api` and optional header `X-LATAM-TOKEN`.
- Internal OAuth token manager caching tokens per (token URL + client id + scope).
- Optional IP rate limiting using Drupal's flood system (disabled by default).

## Install
1. Copy the `latam_api/` folder to `modules/custom/`.
2. Enable and clear cache:
   ```bash
   drush en latam_api -y && drush cr
   ```

## Configure
`/admin/config/services/latam-api`

- Country codes: comma-separated list to control which country fieldsets are shown.
- Per-country fields: base_url, api_key, locale, oauth_token_url, oauth_client_id, oauth_client_secret, oauth_scope, pinpoint_url.
- Security: optional header token gate (`X-LATAM-TOKEN`).
- Rate limiting: optional per-route IP throttle (requests per minute).

Auth priority: OAuth (if all OAuth fields are present) → API key → none.

## Endpoints

### Ping
```
GET /api/latam/ping?cc=XX
```

### Pinpoint
```
POST /api/latam/pinpoint?cc=XX
Content-Type: application/json
```

## Services
- `latam_api.client` – wrapper around Guzzle that chooses Authorization based on country config.
- `latam_api.key_manager` – internal token cache for OAuth client_credentials, TTL ≈ 13 minutes.

## Mermaid diagram
```mermaid
flowchart LR
  A[Client] -->|HTTP| B{AccessCheck}
  B -->|permission ok| C[ApiController]
  B -->|forbidden| Z[[403 or 429]]

  C -->|/ping| D[Client Service]
  C -->|/pinpoint| D

  D -->|needs OAuth?| E{OAuth configured?}
  E -- yes --> F[KeyManagerService getToken]
  F --> G[(KeyValue cache)]
  F --> H[OAuth Token URL] --> F
  E -- no --> I[Use API Key or none]

  D -->|Authorization set| J[Upstream API (base_url / pinpoint_url)]
  J --> K[Response JSON]
  K --> C --> A
```
