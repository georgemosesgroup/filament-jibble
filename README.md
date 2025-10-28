# GPOS Jibble Integration

A fresh Laravel 11 + Filament 4 application prepared to integrate the complete [Jibble](https://www.jibble.io/) API. The stack provides:

- Laravel 11 skeleton with database migrations already executed against SQLite for local prototyping.
- Filament 4 admin panel, including a Jibble API Explorer page to test requests without writing code.
- A configurable HTTP client, resource manager, and dynamic endpoint builder that cover every Jibble API path through configuration.

## Requirements

- PHP 8.2 or higher with required extensions.
- Laravel 11 or 12 (Illuminate components ^11/^12).
- Composer 2.4+.
- Node 18+ and npm if you plan to run the Vite dev server for the Filament UI assets.

## Getting Started

1. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment configuration**
   Copy `.env.example` to `.env` and update the Jibble credentials and base URLs:
   ```bash
   cp .env.example .env
   ```

   | Key | Description |
   | --- | --- |
| `JIBBLE_BASE_URL` | Workspace API host (default `https://workspace.prod.jibble.io/v1`). |
| `JIBBLE_TIME_TRACKING_BASE_URL` | Time tracking API host (default `https://time-tracking.prod.jibble.io/v1`). |
| `JIBBLE_TIME_ATTENDANCE_BASE_URL` | Time attendance API host (default `https://time-attendance.prod.jibble.io/v1`). |
| `JIBBLE_STORAGE_BASE_URL` | Storage API host used for fetching binary blobs (default `https://storage.prod.jibble.io/v1`). |
| `JIBBLE_STORAGE_PUBLIC_BASE_URL` | Public CDN host for displaying stored images (default `https://storage.prod.jibble.io`). |
   | `JIBBLE_PATH_PREFIX` | Path prefix appended to every request (default `/api/v1`). Set to an empty string if your token already targets a versioned host. |
   | `JIBBLE_API_TOKEN` | Personal access token or OAuth bearer token. |
| `JIBBLE_ORGANIZATION_UUID` | Default organization scope injected into `{organization}` placeholders. Required for org-scoped endpoints (e.g., people, projects). |
| `JIBBLE_CLIENT_ID` / `JIBBLE_CLIENT_SECRET` | Client credentials (API Key ID/Secret). If `JIBBLE_API_TOKEN` is empty, the app will automatically request a token using these values. |
   | `JIBBLE_WEBHOOK_SECRET` | Shared secret used when validating incoming webhooks. |
   | `JIBBLE_HTTP_TIMEOUT` | Request timeout in seconds. |
   | `JIBBLE_RETRY_TIMES` / `JIBBLE_RETRY_SLEEP` | Automatic retry configuration (attempts & sleep in milliseconds). |

   Additional placeholders can be supplied per request inside the Filament explorer or through the programmatic API.

3. **Generate the application key & run migrations**
   ```bash
   php artisan key:generate
   php artisan migrate
   ```

4. **Launch the application**
   ```bash
   php artisan serve
   npm run dev
   ```
   Visit `http://localhost:8000/admin` to access the Filament panel. Use `php artisan make:filament-user` to create login credentials.

## Jibble API Integration

All integration logic lives under `app/Services/Jibble`:

- `JibbleClient` handles authentication, retries, pagination, and placeholder substitution.
- `JibbleManager` registers resources defined in `config/jibble.php` and exposes a fluent `endpoint()` builder for ad-hoc calls.
- `GenericResource` provides standard CRUD helpers (list, all, find, create, update, delete) for any configured endpoint.
- `JibbleApiExplorer` Filament page lets you execute requests, inspect responses, and iterate on placeholder configuration from the UI.

> **Token handling**: If you provide `JIBBLE_API_TOKEN`, it will be used directly. Otherwise, set `JIBBLE_CLIENT_ID` and `JIBBLE_CLIENT_SECRET` (your API key ID & secret) and the app will request and cache an access token via the OAuth client-credentials flow.

Add or adjust resource definitions inside `config/jibble.php` to cover every Jibble module. Each entry accepts:

```php
'members' => [
    'path' => 'organizations/{organization}/members',
    'detail_path' => 'organizations/{organization}/members/{id}', // optional, defaults to path + /{id}
    'id_placeholder' => 'id',                                     // optional
    'defaults' => [ 'foo' => 'bar' ],                             // optional placeholder defaults
],
```

For endpoints not yet described in the config, call them directly with:

```php
Jibble::endpoint('organizations/{organization}/custom-resource')
    ->replace('organization', $uuid)
    ->query(['perPage' => 100])
    ->get();
```

## Filament Jibble API Explorer

Inside the admin panel navigate to **Integrations → Jibble API** to:

- Pick a configured resource or define a custom endpoint.
- Run GET/POST/PUT/PATCH/DELETE requests with optional pagination, query parameters, and JSON payloads.
- Provide placeholder values for any path segment (e.g., `{member}` or `{team}`).
- Inspect the status code, headers, meta/links blocks, and formatted JSON payload directly in the UI.

This explorer is intended for quick verification while building dedicated Filament resources or REST endpoints.

## Synced Resources

After saving your credentials you can pull data into the local database with:

```bash
php artisan jibble:sync
```

Use `--resource=people`, `--resource=time_entries`, `--resource=timesheets`, or `--resource=timesheets_summary` to trigger a single sync pipeline. The panel ships with read-only resources for connections, people, time entries, timesheets, timesheet summaries, and sync logs, all grouped under **Integrations**.

## Filament Panel Modes

The `FilamentJibblePlugin` automatically adapts to whether your Filament panel has tenancy enabled:

- **Single-tenant panels**: Each authenticated user gets a **Jibble Settings** entry in the Filament user menu. The page lets them save API credentials, verify the connection, and trigger a manual sync. Ensure your `User` model uses the `Gpos\FilamentJibble\Models\Concerns\HasJibbleConnections` trait (it defaults the relation to `user_id`).
- **Multi-tenant panels**: When tenancy is configured on the panel, the plugin keeps the Filament resources (connections, people, timesheet summaries, sync logs) and adds a **Tenant Jibble Settings** page to the user menu. The page wraps the resource form to present a friendlier tenant-level configuration, including default project/group preferences stored in `jibble_connections.settings`.

Toggle tenancy with the `FILAMENT_ADMIN_TENANCY_ENABLED` environment flag (or adjust the options in `config/filament-admin.php`). When enabled, create `App\Models\Tenant` records with an `owner_id`, `name`, and optional `slug` so Filament can populate the tenant switcher for each user.

A tenant registration page and profile editor are included when tenancy is active, letting you spin up a tenant and tweak its settings directly from the Filament user menu. In a multi-tenant flow, save Jibble credentials on **Tenant Jibble Settings** (client ID/secret or token, organization lookup), then visit the tenant profile page to choose default projects and groups for that tenant.

If you use a custom user class, set `FILAMENT_JIBBLE_USER_MODEL` (or `filament-jibble.user_model`) so the package can resolve the correct relation.

If your panel uses a different tenant relationship name (for example `branch`), set `FILAMENT_JIBBLE_TENANT_RELATIONSHIP=branch` so the package registers the same relationship on its models automatically.
If your project stores tenant references under another column (for example `branch_id`), set `FILAMENT_JIBBLE_TENANT_FOREIGN_KEY=branch_id` **before running the package migrations** so all tables use the matching column name. When the column isn't a UUID, also configure `FILAMENT_JIBBLE_TENANT_FOREIGN_KEY_TYPE` (`uuid`, `ulid`, `string`, `integer`, `bigInteger`, or `unsignedBigInteger`) to match the data type used in your application.

## Next Steps

1. Map any remaining endpoints into `config/jibble.php` so they can be addressed by name.
2. Create typed resource classes when you need custom logic (extend `GenericResource` or implement `ResourceContract`).
3. Build Filament resources/pages that hydrate their tables and forms using the `JibbleManager` instead of local Eloquent models.
4. Wire up scheduled jobs or queues where you need to sync data between Jibble and your domain database.

## Troubleshooting

- Missing or incorrect tokens will throw `JibbleException` messages explaining which configuration value is required.
- Adjust `JIBBLE_PATH_PREFIX` if requests are returning 404; the combination of base URL + prefix must point at the version of the API you intend to consume.
- Use the explorer’s “Custom endpoint” option to smoke test endpoints before promoting them into `config/jibble.php`.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
