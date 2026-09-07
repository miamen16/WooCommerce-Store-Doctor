=== Store Doctor for WooCommerce ===
Contributors: mohamed
Tags: woocommerce, diagnostics, store health, seo, inventory
Requires PHP: 7.4
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Diagnose your WooCommerce store. Fix what hurts. Grow what works.

== Description ==

Store Doctor for WooCommerce scans your store and turns problems into:
Issue -> Explanation -> Recommendation -> Fix.

The current release includes the core scanner engine, health scoring,
issue center, product health checks, auto-fix engine, reports/history,
and Dokan vendor health integration.

Included scanners:

* Product completeness (title, description, SKU, category, cross-sells)
* Images (featured image, gallery)
* Inventory (stock status, low stock, unmanaged stock, including variations)
* Pricing (missing price, zero price, invalid sale price, including variations)
* Basic SEO (short titles, missing short description, missing alt text)

== Architecture ==

store-doctor-for-woocommerce.php Bootstrap, autoloader, activation/deactivation
includes/Core/
    Plugin.php                   Singleton that wires everything together
    Database.php                 Custom table schema
                                  (wp_wcsd_issues, wp_wcsd_health_history,
                                  wp_wcsd_fix_backups)
    AbstractScanner.php          Base class every scanner extends
    Scanner.php                  Runs all registered scanners, computes results
    IssueManager.php             Persists/reads issues
    ScoreManager.php             Overall score + history
    Scheduler.php                Daily cron scan
    VendorHealth.php             Vendor health aggregation for Dokan stores
includes/Scanners/               ProductScanner, ImageScanner, InventoryScanner,
                                  PricingScanner, SEOScanner
includes/Core/
    AbstractFixer.php            Base class every fixer extends
    FixManager.php               Registry + preview/apply/revert orchestration,
                                  backup bookkeeping
includes/Fixers/                 CategoryFixer, FeaturedImageFixer, ImageAltTextFixer
includes/Admin/
    Dashboard.php                Admin menu + screens
    ProductFilter.php            Native Products-list filtering
    VendorsPage.php               Admin vendor health screen
includes/Dokan/
    VendorDashboardTab.php       Dokan vendor Store Health dashboard endpoint

templates/                       Dashboard, issues, vendors and Dokan health views
assets/                          admin.css, admin.js

== Extending ==

To add a new scanner:

1. Create includes/Scanners/YourScanner.php extending AbstractScanner.
2. Implement id(), label(), and scan() (return an array of issues via
   $this->make_issue(...)).
3. Register it via the `wcsd_register_scanners` action:

    add_action( 'wcsd_register_scanners', function( $scanner ) {
        $scanner->add( new \YourNamespace\YourScanner() );
    } );

To add a new fixer, matching a scanner issue's 'fixer' id:

1. Create includes/Fixers/YourFixer.php extending AbstractFixer.
2. Implement id(), label(), preview(), apply(), and revert_one().
3. Register it via the `wcsd_register_fixers` action:

    add_action( 'wcsd_register_fixers', function( $fix_manager ) {
        $fix_manager->add( new \YourNamespace\YourFixer() );
    } );

No core files need to change for either.

== How the Auto Fix Engine works ==

1. User clicks "Fix" on a grouped issue row in the Issue Center.
2. wcsd_fix_preview finds every open issue of that type, asks the
   matching fixer to preview() the change per object (current -> proposed),
   and shows it in a modal. Nothing is written yet.
3. User confirms "Apply Changes" -> wcsd_fix_apply calls apply(), which
   writes the change AND returns old/new values per object. FixManager
   records one backup row per object under a shared batch_id, and marks
   the resolved issues as 'resolved'.
4. Each batch appears under "Recent Fixes" with a Revert button.
   wcsd_fix_revert replays revert_one() for every un-reverted backup
   row in that batch, restoring the old value.

== Roadmap ==

Phase 8  AI Doctor (Pro) — natural-language "why is my score low / what should I fix first"

== Notes on current implementation ==

* Issues are stored as a snapshot per scanner: each scan replaces that
  scanner's previous rows rather than appending forever.
* Health history is append-only, used for the trend table/graph.
* Auto Fix Engine covers 3 fixers matching the 3 fixable MVP issue
  types: missing_category (assigns the default/Uncategorized term),
  missing_featured_image (promotes the first gallery image — skipped
  if there is no gallery image to promote), missing_image_alt (sets
  alt text to the product name only when the featured image is not shared
  by multiple products/variations). Everything else stays "Manual review"
  by design — auto-writing a description or SKU would be guessing.
* Inventory and pricing scanners inspect variable product variations
  individually so variation-level stock and price problems are not missed.
* ImageAltTextFixer snapshots the original alt text before writing and
  skips shared featured images because attachment alt text is global and
  cannot safely represent multiple product names.
* Click-to-filter: the "Products" count on each Issue Center row, and
  each category score on the Dashboard, link into WordPress's native
  Products list (edit.php?post_type=product) pre-filtered via
  post__in to exactly the affected products — implemented in
  includes/Admin/ProductFilter.php, hooking pre_get_posts. No
  separate custom screen; search/sort/bulk-actions on the native list
  keep working normally.
* Store Health History graph: a small hand-rolled canvas line chart
  (no external charting library — keeps the plugin dependency-free)
  showing the overall score trend on the Dashboard. Redraws on window
  resize and scales for high-DPI screens via devicePixelRatio.
* Dokan integration adds an admin vendor-health screen and a Store Health
  endpoint inside the Dokan vendor dashboard. Vendor data is restricted
  to the vendor's own products on the vendor endpoint.
