=== Atlas Report Viewer for Power BI ===
Contributors: atlaspolicy
Tags: power bi, reports, embed, business intelligence
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed Power BI reports and dashboards in WordPress pages via shortcode, with Azure AD sign-in handled on the server and per-report access control.

== Description ==

Atlas Report Viewer for Power BI lets you save each of your Power BI reports in WordPress, then drop it into any page or post with a shortcode.

Your Power BI sign-in happens on the server, so your Azure AD credentials are never sent to your visitors' browsers. Credentials are also encrypted before they are saved in your WordPress database.

Features:

* Embed Power BI **reports** and **dashboards** with a shortcode
* Sign-in handled on the server — credentials never reach the browser
* Credentials encrypted in your WordPress database
* Choose who can see each report: everyone, logged-in users only, or administrators only
* Reports resize to fit the page width and automatically match the report's own proportions, so they look right on phones and desktops
* Optionally show or hide the Power BI filter pane per report
* Open a specific page (tab) of a multi-page report
* Group reports into your own categories
* Pick the style and color of the loading spinner your visitors see
* A one-click **Copy** button next to every report in the admin list, so you never have to type a shortcode by hand

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/atlas-report-viewer-for-power-bi` directory, or install it from the **Plugins > Add New** screen in WordPress.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Power BI Reports > Settings** and enter your Azure AD details (see the FAQ below for what you need).
4. Go to **Power BI Reports > Add New Report**, give it a title, and enter the Report ID and Group/Workspace ID from Power BI.
5. Publish the report, then copy its shortcode from the **Shortcode** column on the **Power BI Reports** list and paste it into any page or post.

== Frequently Asked Questions ==

= What do I need from Azure AD and Power BI? =

Four things, all entered on the **Power BI Reports > Settings** screen:

* **Client ID** — the application (client) ID of your Azure AD app registration
* **Client Secret** — the secret value from that same app registration
* **Master User (UPN)** — the email address of a service account that can open your reports
* **Master User Password** — that account's password

The service account needs a Power BI Pro license and must have multi-factor authentication turned off, because the plugin signs in with the username and password directly. You do not need a Tenant ID — the plugin works it out from the service account's email address.

= Where do I find a report's Report ID and Group/Workspace ID? =

Open the report in Power BI and look at the address bar. The URL contains both values, in this form:

`https://app.powerbi.com/groups/{Group ID}/reports/{Report ID}/...`

= Can I set the size of the embedded report? =

Yes. Each report has **Width** and **Min Height** settings. Width is the space the report takes on the page (`100%` by default). Min Height is only the starting height while the report loads — once it appears, the height adjusts automatically so the report keeps its correct proportions.

You can also override both for a single placement directly in the shortcode:

`[powerbi_report id="123" width="100%" height="800px"]`

= Do visitors need a Power BI account? =

No. The plugin signs in with your service account on the server, so visitors just see the report. Use the **Content Restriction** setting on each report to decide who on your site is allowed to see it.

= Do my Power BI reports appear as pages on my site? =

No. A "Power BI Report" in WordPress is just a saved configuration — it has no page of its own and is not listed anywhere publicly. Reports only appear where you place the shortcode.

= I re-entered my credentials or moved my site and reports stopped loading. What happened? =

Your credentials are encrypted using your site's security keys from `wp-config.php`. If those keys change — which can happen when a site is moved, restored from a backup, or when the keys are deliberately regenerated — the saved credentials can no longer be read. Go to **Power BI Reports > Settings** and enter the Client Secret and Master User Password again.

= What happens if I delete the plugin? =

Your Azure AD credentials and display preferences are removed. Your saved reports and report categories are kept, so reinstalling the plugin brings them back — you only need to re-enter your credentials.

= Where is the source code? =

The full source code, including the React/TypeScript front end, is available on GitHub:
https://github.com/AtlasPublicPolicy/report-viewer-for-power-bi

Everything the plugin needs is already included in the download — you do not need any build tools to use it.

== External Services ==

This plugin connects to two Microsoft services to sign in and display your Power BI reports.

= Microsoft Azure Active Directory (Entra ID) =

Used to sign in and obtain an access token for Power BI. Your site sends your Client ID, Client Secret, service account email address and password to `https://login.microsoftonline.com`.

This request is made from your server, never from a visitor's browser. The resulting token is stored temporarily on your server and reused for all visitors until it is close to expiring (roughly once an hour), so a visit to a page with a report does not normally trigger a new sign-in request.

Privacy Policy: https://privacy.microsoft.com/en-us/privacystatement
Terms of Use: https://azure.microsoft.com/en-us/support/legal/

= Microsoft Power BI =

Used to display your reports. The report itself is loaded from `https://app.powerbi.com` in the visitor's browser, along with the short-lived access token your server obtained above. What the report shows is determined entirely by your Power BI workspace.

Privacy Policy: https://privacy.microsoft.com/en-us/privacystatement
Terms of Service: https://powerbi.microsoft.com/en-us/terms-of-service/

== Changelog ==

= 1.0.0 =
* Initial release.
