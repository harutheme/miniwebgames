# Webgames Plugin Documentation

## Overview
This plugin powers the Mini Web Games platform by registering the core data structures, handling game player logic (lazy-load), tracking interactions (like/report), and injecting global ad/CMP scripts.

## Directory Structure
- `includes/`: PHP classes for logic.
  - `class-cpt-game.php`: Registers `game` CPT, `game-category`, `game-tag`.
  - `class-cpt-report.php`: Registers `game-report` CPT for error tracking.
  - `class-acf-fields.php`: Registers ACF field group for `game` (Source Type, URL, Cover).
  - `class-shortcodes.php`: Handles `[webgames_player]` and `[webgames_sidebar_list]`.
  - `class-settings-page.php`: Admin menu "Webgames Settings" for Global Scripts & Ads.
  - `class-ads-manager.php`: Outputs CMP scripts in `<head>` and renders ad shortcodes.
  - `class-ajax-handler.php`: Handles AJAX for Like, Dislike, Report.
  - `class-comment-spam.php`: Adds honeypot to comments and prevents spam bots.
- `assets/`:
  - `css/elements.css`: Global UI components (Labels, Buttons, Forms). MUST be updated when adding new standard components.
  - `css/player.css`: Styles for the game player, toolbar, and buttons.
  - `js/player.js`: Logic for lazy-loading iframe, LocalStorage (favorites), and AJAX calls.
- `languages/`: Contains `webgames.pot` for translations.

## Core Workflows
1. **Frontend Game Player**: 
   - Managed via `[webgames_player]`. It initially loads a cover image and a "Play" button to avoid loading heavy 3rd-party iframes on page load.
   - Includes a Toolbar for user actions (Share, Fullscreen, Like, Favorite, Report).
2. **User Interaction**:
   - `Favorite`: Stored locally in browser `localStorage`.
   - `Like/Dislike`: Sends AJAX POST. Server increments post meta. Browser saves state to avoid duplicate clicks.
   - `Report`: AJAX modal creates a `game-report` CPT and emails admin. Uses Honeypot + Nonce + Rate Limiting.
3. **Ads Integration**:
   - Centralized in `Webgames Settings`.
   - `class-ads-manager.php` hooks into `wp_head` for CMP/GA scripts.
   - Top Ad & Sidebar Ad code snippets are accessed via `[webgames_top_ad]` and `[webgames_sidebar_ad]`.

## Anti-Spam
- Commenting relies on native WP Guest Comments (to capture emails safely) but is fortified with a CSS-hidden `website_url` input. If filled, the comment is rejected.
