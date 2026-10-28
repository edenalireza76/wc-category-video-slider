=== WC Category Video Slider ===
Contributors: raykapack
Tags: woocommerce, category, video, slider, elementor
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
WC requires at least: 8.0
WC tested up to: 11.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Video gallery for WooCommerce product categories, shown as a customizable slider above the category description.

== Description ==

* Per-category video gallery: upload to the media library or paste an external link (YouTube, Aparat, Vimeo, direct .mp4/.webm).
* Custom cover image per video (YouTube thumbnails are used automatically when no cover is set).
* Drag & drop ordering, bulk add from the media library, per-category on/off and heading.
* Automatic output right above (or below) the category description:
  - `woocommerce_archive_description` hook – used by the Elementor Pro "Archive Description" widget, WoodMart and most themes.
  - Optional `get_the_archive_description` filter – for the Elementor "Archive Description" dynamic tag.
* Elementor widget "Category Video Slider" with full style controls (responsive slides per view, gap, ratio, height, padding, margin, typography, colors, borders, shadows, play button, arrows, dots).
* Global design settings: WooCommerce → Category Videos.
* Lightweight vanilla-JS slider based on native CSS scroll-snap (no jQuery, no Swiper conflicts), touch-friendly, RTL aware, works even when JS is delayed.
* Lazy: iframes/videos are only loaded when the visitor presses play (great for Core Web Vitals).
* Lightbox or inline playback, keyboard accessible.
* VideoObject structured data (JSON-LD).
* HPOS and Cart/Checkout blocks compatible. Persian (fa_IR) translation included.

== Shortcode ==

`[rcvs_video_slider]` – current category.
`[rcvs_video_slider id="15" heading="Videos of {category}" autoplay="yes" arrows="yes" dots="no" titles="yes" play_mode="inline"]`

== Developer hooks ==

* `rcvs_category_videos` (filter) – prepared items of a category.
* `rcvs_slider_html` (filter) – final HTML.
* `rcvs_inline_css` (filter) – generated CSS.
* `rcvs_schema_graph` (filter) – VideoObject graph.
* `rcvs_archive_hook_priority` (filter) – priority on woocommerce_archive_description (default 9 / 11).
* `rcvs_first_page_only` (filter) – show only on page 1 (default true).
* `rcvs_breakpoint_tablet`, `rcvs_breakpoint_mobile` (filters).
* `rcvs_term_saved` (action).

== Changelog ==

= 1.0.0 =
* Initial release.
