# LATAM API

A Drupal module that exposes JSON endpoints and centralizes per-country API settings. Countries are configurable. Supports API-Key or OAuth2 client_credentials with a built-in token cache. Includes permission gating, optional shared-secret header, and optional rate limiting.

## Features
- Configurable list of country codes.
- Per-country settings: base URL, API key, locale, OAuth token URL, client id/secret, scope, and a per-country `pinpoint` URL.
- Endpoints:
  - `GET /api/latam/ping?cc=XX` – returns active country config snapshot (non-sensitive fields).
  - `POST /api/latam/pinpoint?cc=XX` – forwards arbitrary JSON to the configured `pinpoint_url` with automatic Authorization.
- Access control via permission `access latam api` and optional header `X-LATAM-TOKEN`.
- Internal OAuth token manager caching tokens per (token URL + client id + scope).
- Optional IP rate limiting using Drupal’s flood system (disabled by default).

## Requirements
- Drupal 10 or 11
- PHP compatible with your Drupal core
- Drush recommended for install and cache clears

## Install
1. Copy the `latam_api/` folder to `modules/custom/`.
2. Enable and clear cache:
   ```bash
   drush en latam_api -y && drush cr
   ```

## Configure
`/admin/config/services/latam-api`

Global:
- Country codes: comma-separated list to control which country fieldsets are shown (for example `MX,CL,BR`).
- Security: optional header token gate (`X-LATAM-TOKEN`). If enabled, requests must include that header with the configured value.
- Rate limiting: optional per-route IP throttle (requests per minute).

Per country (each code you list):
- `base_url`
- `api_key` (used if OAuth is not configured)
- `locale`
- `oauth_token_url`, `oauth_client_id`, `oauth_client_secret`, `oauth_scope`
- `pinpoint_url` (target used by the `POST /api/latam/pinpoint` endpoint)

Auth priority: OAuth (if all OAuth fields are present) → API key → none.

### Configuration overrides (do not commit secrets)
Prefer `settings.php` to set real secrets per environment:
```php
$config['latam_api.settings']['countries']['MX']['oauth_token_url'] = 'https://auth.example.com/oauth/token';
$config['latam_api.settings']['countries']['MX']['oauth_client_id'] = 'your-client-id';
$config['latam_api.settings']['countries']['MX']['oauth_client_secret'] = 'your-secret';
$config['latam_api.settings']['countries']['MX']['oauth_scope'] = 'scope1 scope2';

$config['latam_api.settings']['require_header_token'] = TRUE;
$config['latam_api.settings']['header_token'] = 'shared-secret-here';

$config['latam_api.settings']['rate_limit']['enabled'] = TRUE;
$config['latam_api.settings']['rate_limit']['requests_per_minute'] = 120;
```

## Permissions
- `access latam api` – required to hit the endpoints.
- Admin UI is behind standard configuration permissions and the module’s settings route.

## Endpoints

### Ping
```
GET /api/latam/ping?cc=XX
```

Response:
```json
{
  "ok": true,
  "country": "MX",
  "base_url": "https://api.example.mx",
  "locale": "es_MX",
  "auth_mode": "oauth",
  "timestamp": "2025-09-23T00:00:00+00:00"
}
```

### Pinpoint
```
POST /api/latam/pinpoint?cc=XX
Content-Type: application/json
```

Example:
```bash
curl -X POST 'https://your-site.test/api/latam/pinpoint?cc=MX'   -H 'Content-Type: application/json'   -d '{"email":"a@b.com","perfil":"x","invInicial":1,"invMensual":1,"plazo":12,"NumContrato":"123","fondo":"foo","clave":"bar"}'
```

## Services
- `latam_api.client` – wrapper around Guzzle that chooses Authorization based on country config.
- `latam_api.key_manager` – internal token cache for OAuth client_credentials, TTL ≈ 13 minutes.

## Rate limiting
- If enabled, uses Drupal’s `flood` service to limit requests per IP per route.
- Default is disabled.

## Error handling
- 400 – invalid JSON body or missing parameters
- 403 – permission denied or missing header token
- 429 – too many requests if rate limiting enabled
- 500 – configuration or upstream error

## Logging
- Module logs to the `latam_api` channel.
- OAuth errors include response body when possible.

## Extending
- Add routes in `latam_api.routing.yml` and controllers in `src/Controller`.
- Use `latam_api.client` for consistent upstream requests.

## Mermaid diagram
```mermaid
flowchart LR
  A[Client] -->|HTTP| B{AccessCheck}
  B -->|permission ok| C[ApiController]
  B -->|forbidden| Z[[403 or 429]]

  C -->|/ping| D[Client Service]
  C -->|/pinpoint| D

  D -->|needs auth?| E{OAuth configured?}
  E -- yes --> F[KeyManagerService getToken]
  F --> G[(KeyValue cache)]
  F --> H[OAuth token URL]
  H --> F
  E -- no --> I[Use API key or none]

  D --> J[Upstream API]
  J --> K[Response JSON]
  K --> C --> A
```
