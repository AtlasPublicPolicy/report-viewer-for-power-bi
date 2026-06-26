# Report Viewer for Power BI

A WordPress plugin that embeds Microsoft Power BI reports and dashboards into any page or post via the `[powerbi_report]` shortcode.

---

## Requirements

- PHP 8.0+
- Composer
- Node 18+ / npm

---

## Setup

### 1. Install PHP dependencies

```bash
composer install
```

This installs CMB2 into `vendor/`.

### 2. Install JS dependencies

```bash
cd react-app && npm install
```

---

## Development

### Enable dev mode in the plugin

Open `report-viewer-for-pbi.php` and set:

```php
define( 'RVPBI_DEV_MODE', true );
```

### Start the Vite dev server

```bash
cd react-app && npm run dev
```

The plugin will load JS/CSS from `http://localhost:5173` instead of `dist/`.
WordPress must be able to reach that port (local dev environment assumed).

---

## Production Build & Export

Run everything in one step:

```bash
composer run build
```

This will:

1. `npm ci && npm run build` inside `react-app/` → outputs to `react-app/dist/`
2. Run `scripts/export.php` → creates `report-viewer-for-pbi.zip`

Upload `report-viewer-for-pbi.zip` to your WordPress site via **Plugins → Add New → Upload Plugin**.

> **Remember** to set `RVPBI_DEV_MODE` back to `false` before building.

---

## Running the Plugin Checker

The [WordPress Plugin Check (PCP)](https://wordpress.org/plugins/plugin-check/) plugin validates your code against WordPress.org submission requirements.

### Option A — WP-CLI (recommended)

```bash
wp plugin check report-viewer-for-pbi
```

Run from your WordPress root (or prefix with `wp --path=/path/to/wordpress` if needed). Results print directly to the terminal.

### Option B — Admin UI

1. Install and activate the **Plugin Check** plugin from **Plugins → Add New**.
2. Go to **Tools → Plugin Check**.
3. Select **Report Viewer for Power BI** from the dropdown and click **Check it!**.

### Saving results

Pipe WP-CLI output to a markdown file for review:

```bash
wp plugin check report-viewer-for-pbi --format=csv > PLUGINCHECK.csv
```

Or use the `--format=json` flag if you prefer structured output.

---

## Usage

1. Go to **Power BI Reports → Settings** and enter your Azure AD credentials (Client ID, Client Secret, Master User UPN, and Master User Password).
2. Go to **Power BI Reports → Add New** and create a report post. Enter the **Report ID** and **Group/Workspace ID** from the Power BI service. Set the embed type (report or dashboard), optional page name, display dimensions, and content restriction level.
3. Copy the **Shortcode** value shown in the report list (`[powerbi_report id="{post_id}"]`) and paste it into any page or post.
4. The React app mounts at the shortcode location and renders the embedded report or dashboard.

> **Note:** Content restriction is configured per report via the **Restriction** meta field. Options are `public` (anyone), `logged_in` (must be authenticated), or `administrator` (must have `manage_options`). The `powerbi_view` capability is added to the Administrator role on activation; deactivate and reactivate if it is missing.

**Shortcode attributes:**

| Attribute | Default             | Description                                 |
| --------- | ------------------- | ------------------------------------------- |
| `id`      | (required)          | WP post ID of the `powerbi_report` post.    |
| `width`   | post meta / `100%`  | Overrides the report post's width setting.  |
| `height`  | post meta / `600px` | Overrides the report post's height setting. |

---

## REST API

The plugin registers a custom REST namespace at `report-viewer-for-pbi/v1`.

### `GET /wp-json/report-viewer-for-pbi/v1/powerbi/embed`

Returns the embed configuration needed by the `powerbi-client-react` component. Token generation is performed server-side so credentials never reach the browser.

**Query parameters:**

| Parameter | Type    | Required | Description                              |
| --------- | ------- | -------- | ---------------------------------------- |
| `post_id` | integer | Yes      | WP post ID of the `powerbi_report` post. |

**Example request:**

```
GET /wp-json/report-viewer-for-pbi/v1/powerbi/embed?post_id=42
```

**Example response:**

```json
{
  "embedUrl": "https://app.powerbi.com/reportEmbed?...",
  "accessToken": "<azure-ad-bearer-token>",
  "reportId": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "embedType": "report",
  "pageName": "ReportSection1"
}
```

`pageName` is omitted when not configured on the report post.

**Response status codes:**

| Status | Meaning                                                                                         |
| ------ | ----------------------------------------------------------------------------------------------- |
| `200`  | Success — embed config returned.                                                                |
| `400`  | `post_id` does not refer to a valid `powerbi_report` post.                                      |
| `401`  | Report is restricted to logged-in users and the request is anonymous.                           |
| `403`  | Report is restricted to administrators and the current user lacks `manage_options`.             |
| `500`  | Report post is missing `pbi_report_id` or `pbi_group_id`, or Azure AD token acquisition failed. |

Responses include `Cache-Control: no-store` to prevent caching plugins from serving stale embed tokens.

---

## Capabilities

The plugin registers one custom capability:

| Capability     | Description                                                 |
| -------------- | ----------------------------------------------------------- |
| `powerbi_view` | Grants access to Power BI report content within the plugin. |

This capability is automatically added to the **Administrator** role on plugin activation and removed from all roles on deactivation.

To grant access to an additional role (e.g. Editor), add this to your theme's `functions.php` or a custom plugin:

```php
add_action( 'init', function () {
    $role = get_role( 'editor' );
    if ( $role ) {
        $role->add_cap( 'powerbi_view' );
    }
} );
```

> **Note:** Per-report content restriction (`public` / `logged_in` / `administrator`) is enforced independently of this capability, directly in the REST permission callback using the `pbi_restriction` post meta value.

---

## PHP → JS Data (`window.ReportViewerPBI`)

PHP passes data to the React app via `wp_localize_script`. The shape is defined in `src/wp-globals.d.ts`:

| Key                    | Type    | Source (PHP)                 | Description                            |
| ---------------------- | ------- | ---------------------------- | -------------------------------------- |
| `restUrl`              | string  | `rest_url()`                 | WP REST API base URL                   |
| `nonce`                | string  | `wp_create_nonce('wp_rest')` | Nonce for authenticated REST requests  |
| `powerbiDisplayStatus` | boolean | CMB2 settings                | Show loading/error states to end users |
| `spinnerType`          | string  | CMB2 settings                | react-spinners component key           |
| `spinnerColor`         | string  | CMB2 settings                | CSS color for the spinner              |

To add more data, extend `RVPBI_Assets::js_data()` in `includes/assets.php` and update the `Window` interface in `src/wp-globals.d.ts`.

---

## React App Architecture

The React app follows a three-layer pattern:

**1. API layer — `src/api/api.ts`**
A single shared axios client (base URL + `X-WP-Nonce` header from `window.ReportViewerPBI`). Exports `fetchEmbedConfig()` for the embed endpoint. Add new endpoints here.

**2. Hooks — `src/hooks/`**
TanStack Query wrappers around API functions. Hooks own pagination state (`useState` for page) and expose `{ data, page, setPage, totalPages, isLoading, isError }`. Add a new hook for each new data resource.

**3. Components — `src/components/`**
Presentational components that consume hooks. Each component lives in its own folder:

```
ComponentName/
├── ComponentName.tsx        # component logic and JSX
├── ComponentName.styles.ts  # styled-components
└── index.ts                 # barrel export
```

---

## Theming

Styling uses [styled-components](https://styled-components.com/) with `ThemeProvider`.

**Theme definition — `src/styles/theme.ts`**
Exports a `theme` object with `color`, `font`, and `space` keys. Edit this file to change design tokens globally.

**Type safety — `src/styled.d.ts`**
`DefaultTheme` is derived directly from `typeof theme`, so adding a new token to `theme.ts` makes it immediately available and typed in all styled components — no separate interface to maintain.

**Usage in styled components:**

```ts
const Title = styled.h1`
  color: ${({ theme }) => theme.color.textPrimary};
  margin-bottom: ${({ theme }) => theme.space.lg};
`;
```

`ThemeProvider` is mounted in `src/main.tsx` and wraps the entire app.

---

## Project Structure

```
report-viewer-for-pbi/
├── report-viewer-for-pbi.php           # RVPBI bootstrap — constants, requires, activation hooks
├── composer.json
├── composer.lock
├── includes/
│   ├── assets.php                  # RVPBI_Assets — script/style enqueuing (dev + prod)
│   ├── cpt.php                     # powerbi_report CPT + powerbi_category taxonomy
│   ├── report-metabox.php          # CMB2 meta boxes for report configuration
│   ├── powerbi-settings.php        # PowerBI_Settings — CMB2 options page (Azure AD credentials)
│   ├── powerbi-token.php           # PowerBI_Token_Provider — ROPC auth + embed config
│   ├── powerbi-shortcode.php       # PowerBI_Shortcode — [powerbi_report] shortcode handler
│   ├── powerbi-rest.php            # PowerBI_REST_Controller — REST route (report-viewer-for-pbi/v1)
│   └── admin-columns.php           # PowerBI_Admin_Columns — Shortcode column with copy button
├── react-app/
│   ├── index.html
│   ├── package.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   ├── tsconfig.node.json
│   └── src/
│       ├── main.tsx                # Entry — QueryClientProvider + ThemeProvider + PowerBIReport
│       ├── wp-globals.d.ts         # TypeScript types for window.ReportViewerPBI
│       ├── styled.d.ts             # DefaultTheme module augmentation
│       ├── api/
│       │   └── api.ts              # axios client + fetchEmbedConfig()
│       ├── hooks/
│       │   └── usePowerBIEmbed.ts  # TanStack Query hook (45-min stale time)
│       ├── styles/
│       │   └── theme.ts            # Design tokens (colors, font, spacing)
│       └── components/
│           └── PowerBIReport/
│               ├── PowerBIReport.tsx
│               ├── PowerBIReport.styles.ts
│               └── index.ts
├── scripts/
│   └── export.php                  # ZIP builder for distribution
└── vendor/                         # Composer dependencies (CMB2)
```
