# My Plugin — WordPress Plugin Scaffold

A WordPress plugin scaffold that mounts a React + Vite application via shortcode. Demonstrates REST API endpoints, TanStack Query data fetching, styled-components theming, and class-based PHP architecture intended as a starting point for building real plugin features.

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

Open `report-viewer-pbi.php` and set:

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
2. Run `scripts/export.php` → creates `report-viewer-pbi.zip`

Upload `report-viewer-pbi.zip` to your WordPress site via **Plugins → Add New → Upload Plugin**.

> **Remember** to set `RVPBI_DEV_MODE` back to `false` before building.

---

## Running the Plugin Checker

The [WordPress Plugin Check (PCP)](https://wordpress.org/plugins/plugin-check/) plugin validates your code against WordPress.org submission requirements.

### Option A — WP-CLI (recommended)

```bash
wp plugin check report-viewer-pbi
```

Run from your WordPress root (or prefix with `wp --path=/path/to/wordpress` if needed). Results print directly to the terminal.

### Option B — Admin UI

1. Install and activate the **Plugin Check** plugin from **Plugins → Add New**.
2. Go to **Tools → Plugin Check**.
3. Select **Atlas Scaffold** from the dropdown and click **Check it!**.

### Saving results

Pipe WP-CLI output to a markdown file for review:

```bash
wp plugin check report-viewer-pbi --format=csv > PLUGINCHECK.csv
```

Or use the `--format=json` flag if you prefer structured output.

---

## Usage

1. Go to **Settings → My Plugin** and set your **Display Title**.
2. Add `[my_plugin]` to any page or post.
3. The React app mounts and renders the title, a paginated table of post names (from the custom REST endpoint), and a table of registered post types (from the WP native API).

> **Note:** The posts table requires the user to be logged in as an Administrator (or any role granted the `my_plugin_read_posts` capability). Deactivate and reactivate the plugin after first install to ensure the capability is applied.

---

## REST API

The plugin registers a custom REST namespace at `my-plugin/v1`.

### `GET /wp-json/my-plugin/v1/posts`

Returns a paginated list of published post IDs and titles.

**Requires:** the `my_plugin_read_posts` capability (see [Capabilities](#capabilities) below).

**Query parameters:**

| Parameter  | Type    | Default | Description                          |
|------------|---------|---------|--------------------------------------|
| `page`     | integer | `1`     | Page number to retrieve.             |
| `per_page` | integer | `10`    | Items per page. Min `1`, max `100`.  |

**Example request:**

```
GET /wp-json/my-plugin/v1/posts?page=1&per_page=5
```

**Example response:**

```json
[
  { "id": 42, "title": "Hello World" },
  { "id": 43, "title": "My Second Post" }
]
```

**Response headers** (mirrors WP core conventions):

| Header            | Description                              |
|-------------------|------------------------------------------|
| `X-WP-Total`      | Total number of published posts.         |
| `X-WP-TotalPages` | Total pages at the current `per_page`.   |

---

## Capabilities

The plugin registers one custom capability:

| Capability              | Description                                      |
|-------------------------|--------------------------------------------------|
| `my_plugin_read_posts`  | Grants access to the `/posts` REST endpoint.     |

This capability is automatically added to the **Administrator** role on plugin activation and removed from all roles on deactivation.

To grant access to an additional role (e.g. Editor), add this to your theme's `functions.php` or a custom plugin:

```php
add_action( 'init', function () {
    $role = get_role( 'editor' );
    if ( $role ) {
        $role->add_cap( 'my_plugin_read_posts' );
    }
} );
```

---

## PHP → JS Data (`window.ReportViewerPBI`)

PHP passes data to the React app via `wp_localize_script`. The shape is defined in `src/wp-globals.d.ts`:

| Key                    | Type    | Source (PHP)                         | Description                              |
|------------------------|---------|--------------------------------------|------------------------------------------|
| `restUrl`              | string  | `rest_url()`                         | WP REST API base URL                     |
| `nonce`                | string  | `wp_create_nonce('wp_rest')`         | Nonce for authenticated REST requests    |
| `powerbiDisplayStatus` | boolean | CMB2 settings                        | Show loading/error states to end users   |
| `spinnerType`          | string  | CMB2 settings                        | react-spinners component key             |
| `spinnerColor`         | string  | CMB2 settings                        | CSS color for the spinner                |

To add more data, extend `RVPBI_Assets::js_data()` in `includes/assets.php` and update the `Window` interface in `src/wp-globals.d.ts`.

---

## React App Architecture

The React app follows a three-layer pattern:

**1. API layer — `src/api/api.ts`**
A single shared axios client (base URL + `X-WP-Nonce` header from `window.ReportViewerPBI`). Each endpoint gets one typed async function. Add new endpoints here.

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
`
```

`ThemeProvider` is mounted in `src/main.tsx` and wraps the entire app.

---

## Project Structure

```
report-viewer-pbi/
├── report-viewer-pbi.php      # RVPBI bootstrap — constants, requires, init/activate/deactivate
├── composer.json
├── includes/
│   ├── assets.php             # RVPBI_Assets — script/style enqueuing (dev + prod)
│   ├── cpt.php                # powerbi_report CPT + powerbi_category taxonomy
│   ├── report-metabox.php     # CMB2 meta boxes for the CPT
│   ├── powerbi-settings.php   # PowerBI_Settings — CMB2 options page (Azure AD credentials)
│   ├── powerbi-token.php      # PowerBI_Token_Provider — ROPC auth + embed config
│   ├── powerbi-shortcode.php  # PowerBI_Shortcode — [powerbi_report] shortcode
│   └── powerbi-rest.php       # PowerBI_REST_Controller — REST route (report-viewer-for-pbi/v1)
├── react-app/
│   ├── index.html
│   ├── vite.config.ts
│   ├── package.json
│   ├── tsconfig.json
│   └── src/
│       ├── styled.d.ts        # DefaultTheme module augmentation
│       ├── main.tsx           # Entry — QueryClientProvider + ThemeProvider + App
│       ├── wp-globals.d.ts    # TypeScript types for window.ReportViewerPBI
│       ├── styles/
│       │   └── theme.ts       # Design tokens (colors, font, spacing)
│       ├── api/
│       │   └── api.ts         # axios client, fetchPosts(), fetchPostTypes()
│       ├── hooks/
│       │   ├── usePosts.ts    # Paginated query for my-plugin/v1/posts
│       │   └── usePostTypes.ts # Query for wp/v2/types
│       ├── App/
│       │   ├── App.tsx
│       │   ├── App.styles.ts
│       │   └── index.ts
│       └── components/
│           ├── PaginatedTable/
│           │   ├── PaginatedTable.tsx
│           │   ├── PaginatedTable.styles.ts
│           │   └── index.ts
│           ├── PostsTable/
│           │   ├── PostsTable.tsx
│           │   ├── PostsTable.styles.ts
│           │   └── index.ts
│           └── PostTypesTable/
│               ├── PostTypesTable.tsx
│               ├── PostTypesTable.styles.ts
│               └── index.ts
├── scripts/
│   └── export.php             # Zip builder
└── vendor/                    # Composer deps
```
