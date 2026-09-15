=== A11yFix ===
Contributors: yodsira
Tags: accessibility, wcag, ada, eaa, audit
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accessibility audit with real markup fixes — and a preview before anything goes live. No overlay widgets.

== Description ==

A11yFix does not add widgets to your site. It finds real markup problems — and, for safe categories, fixes the actual DOM after showing you exactly what will change.

**The audit** scans your homepage, latest posts and one static page (on a schedule or on demand) and reports:

* images without alt text
* skipped heading levels (h2 → h4) and multiple h1
* links and buttons with no accessible name (icon-only links)
* form fields without labels
* pages that do not declare their language (`<html lang>`)
* missing or empty `<title>`
* generic link text ("read more", "click here")

Every issue comes with a severity and a one-phrase "how to fix" — the report is useful on its own, even if you fix everything by hand.

**The repairs** (each is a separate toggle):

Safe, on by default:

* fill missing `alt` from the media library title/alt field
* declare the page language on `<html>`
* add `scope` to table headers

Risky, off by default — review the preview first:

* accessible names for unlabeled controls (from URL slugs / placeholders)
* titles for untitled iframes

**Preview before anything goes live.** A11yFix records every attribute it would add — target, attribute, new value — and shows the list before you enable a repair. Nothing is ever removed from your markup; repairs only add attributes. When a repair has nothing to change, the page is served byte-for-byte unchanged.

**Honest scope:** automated checks catch a part of WCAG issues (roughly the mechanical 20–40%); contrast, focus order and ARIA patterns still need a human expert. Use A11yFix to keep the floor high and the report short.

**Why not an overlay?** Overlay widgets that inject toolbars and rewrite the page at runtime are rejected by the accessibility community: they don't fix markup, sometimes break assistive tech, and create false confidence. A11yFix is the opposite approach: find problems, fix the markup, show your work.

== Installation ==

1. Upload the `a11yfix` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Open the A11yFix menu → press "Run scan now".
4. Review the report; enable repairs one by one, using Preview first.

== Frequently Asked Questions ==

= Will it slow my site down? =

The scan runs on a cron request, never on a visitor's page load. With only safe repairs on, enabled fixes run through native WordPress filters; the output-buffered DOM pass activates only when you enable DOM-level repairs, and pages without needed changes are returned untouched.

= Can it break my layout? =

Repairs only add attributes (alt, lang, scope, aria-label, title, value) and never touch tags, classes or styles. The preview shows every change before you enable a repair, and the front-end fixes have a master switch.

= Is my site compliant after this? =

No automated tool can promise compliance. A11yFix fixes mechanical issues and produces a clear report; a full audit needs a specialist. Anything promising "one-click compliance" is selling you a lawsuit risk.

= Where is my data stored? =

Scan results live in your own database. Nothing is sent to third-party services; the scanner crawls only your own site.

== A11yFix Pro ==

The companion plugin adds the client-facing layer on top of the free audit:

* client-ready compliance report (print to PDF) with an agency white-label
* weekly rescans of up to 25 pages with change digests
* an honest "what automation does not cover" section

<a href="https://yodsira.com/buy/a11yfix">Buy A11yFix Pro — $99/year</a>

== Changelog ==

= 0.1.1 =
* Added: A11yFix Pro banner — the compliance-report companion plugin is now available.

= 0.1.0 =

= 0.1.0 =
* First release: audit (9 rules), safe repairs with per-repair toggles, change preview, daily scheduled scan, clean uninstall.
