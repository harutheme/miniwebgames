# Webgames Scrapper - Development Guidelines

This document outlines the strict rules and architectural standards for the `webgames-scrapper` plugin. All future AI and human contributors MUST adhere to these guidelines to maintain a clean, maintainable, and robust codebase.

## 1. Modular Architecture (Multi-Source Strategy)
The plugin is divided into isolated modules:
- **Module 1**: Bulk API Importer (`includes/class-api-importer.php`) - Menu placeholder for automated fetching.
- **Module 2**: Single URL Scraper (`includes/class-single-scraper.php`) - Auto-fills the 'Add New Game' screen using AJAX.
- **Module 3**: Source Downloader (`includes/class-html5-downloader.php`) - Scrapes HTML5 game iframe content and creates a ZIP file for local downloading.

### 1.1 Strategy Pattern for Scraping (The `parsers` folder)
To support multiple target websites without messy `if/else` blocks, all HTML parsing logic MUST go through the `includes/parsers/` directory.
- `interface-parser.php`: Defines `Webgames_Scraper_Parser_Interface` with methods `get_title()`, `get_description()`, `get_image_url()`, and `get_iframe_url()`.
- Implementations (e.g., `class-parser-musicgames.php`) implement this interface for specific target sites.
- If a new site needs to be scraped (e.g., `poki.com`), create `class-parser-poki.php`, implement the interface, and add the option to the `<select>` dropdowns in Module 2 and Module 3.

**RULE**: Modules must not intertwine logic. Module 2 and Module 3 ONLY handle UI and AJAX routing. They pass the raw HTML to the selected Parser, and the Parser returns the structured data.

## 2. Asset Management (No Inline CSS/JS)
- **CSS**: ALL styles for meta boxes and admin UI must reside in `assets/css/scraper-admin.css`. Do not use inline `style="..."` attributes in PHP or JS.
- **Javascript**: ALL frontend interaction logic must reside in `assets/js/scraper-admin.js` (for scraping) and `assets/js/downloader-admin.js` (for downloading).
- **Enqueueing**: Assets must be enqueued via `admin_enqueue_scripts` only on the relevant admin screens (e.g., `post-new.php?post_type=game` or `post.php`).

## 3. Internationalization (i18n)
**CRITICAL RULE**: Every user-facing string must be translatable.
- Use `__( 'Text', 'webgames-scrapper' )` for standard text.
- Use `esc_html__( 'Text', 'webgames-scrapper' )` when outputting to HTML.
- Update `languages/webgames-scrapper.pot` when adding new text strings.

## 4. Scraping Best Practices
- Use `wp_remote_get()` for fetching remote HTML. Handle `WP_Error` gracefully.
- When parsing HTML, prefer `DOMDocument` over RegEx for robustness.
- Suppress DOMDocument warnings using `libxml_use_internal_errors(true)`.

## 5. Security
- Protect all AJAX actions with nonces (`check_ajax_referer`).
- Ensure users have the `edit_posts` or `manage_options` capability before processing scraper requests.
- Sanitize all scraped data before storing or echoing it (`sanitize_text_field`, `wp_kses_post`).
