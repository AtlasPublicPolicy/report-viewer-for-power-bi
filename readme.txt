=== Report Viewer for Power BI ===
Contributors: Atlas Public Policy
Tags: power bi, reports, embed, business intelligence
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed Power BI reports and dashboards in WordPress pages via shortcode, with Azure AD authentication and per-report content restriction.

== Description ==

Report Viewer for Power BI lets you create Power BI Report posts in WordPress, then embed them anywhere using the `[powerbi_report]` shortcode. Authentication is handled server-side via the Azure AD ROPC flow — credentials never reach the browser.

Features:

* Embed Power BI reports and dashboards via shortcode
* Server-side Azure AD token generation (credentials stay on the server)
* Per-report content restriction: public, logged-in users, or administrators only
* Custom report categories for organising your library
* Configurable display dimensions per report
* Optional page name for paginated reports

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/report-viewer-for-power-bi` directory, or install via the WordPress plugin screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Power BI Reports → Settings** and enter your Azure AD credentials.
4. Create a **Power BI Report** post and enter the Report ID and Group/Workspace ID.
5. Add `[powerbi_report id="{post_id}"]` to any page or post.

== Frequently Asked Questions ==

= What Azure AD credentials do I need? =

You need a Tenant ID, Client ID, Client Secret, and a Master User account (UPN + password) with Power BI access. The Master User account must have MFA disabled. See the Microsoft Power BI Embedded documentation for full setup instructions.

= Does this require Composer? =

The distributed ZIP includes all PHP dependencies pre-installed. Composer is only needed if you are developing or building from source.

= How do I enable development mode? =

Set `RVPBI_DEV_MODE` to `true` in `report-viewer-for-power-bi.php`, then start the Vite dev server with `cd react-app && npm run dev`.

= Where is the source code? =

The full source code, including the React/TypeScript frontend, is available on GitHub:
https://github.com/AtlasPublicPolicy/report-viewer-for-power-bi

== External Services ==

This plugin connects to two Microsoft services to authenticate and embed Power BI reports.

= Microsoft Azure Active Directory (Entra ID) =

Used to generate an OAuth access token via the Resource Owner Password Credentials (ROPC) flow. A request is sent to `https://login.microsoftonline.com` on each page load containing a Power BI report shortcode (subject to a 45-minute client-side cache). Your Azure AD credentials are stored in your WordPress database and are never sent to the browser.

Privacy Policy: https://privacy.microsoft.com/en-us/privacystatement
Terms of Use: https://azure.microsoft.com/en-us/support/legal/

= Microsoft Power BI =

Used to generate report embed URLs and render reports in an iframe on your site. Embed content is loaded from `https://app.powerbi.com`. The data displayed is determined entirely by your Power BI workspace configuration.

Privacy Policy: https://privacy.microsoft.com/en-us/privacystatement
Terms of Service: https://powerbi.microsoft.com/en-us/terms-of-service/

== Changelog ==

= 1.0.0 =
* Initial release.
