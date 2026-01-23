# Router Tracer - Joomla SEF URL Debugging Plugin

A powerful debugging tool for diagnosing SEF URL issues and redirect loops in Joomla 5.

## The Problem

Redirect loops and SEF URL conflicts are notoriously difficult to debug in Joomla. When you have multiple SEF plugins (like 4SEF, sh404SEF, or Joomla's core SEF plugin) working together, they can conflict in ways that cause:

- **Trailing slash loops**: URL toggles between `/page` and `/page/` endlessly
- **Redirect chains**: Multiple plugins each adding their own redirects
- **Silent URL modifications**: Changes happen but you can't identify the source

## The Solution

Router Tracer logs every URL manipulation event with:

- **Timestamps and elapsed time** for each event
- **Stack traces** showing exactly what code triggered the change
- **Apache .htaccess detection** identifying rewrites before PHP runs
- **SEF plugin configuration** showing which plugins are active and their settings
- **Call chain tracking** identifying which extension triggered each event

## Features

### Comprehensive Event Logging
- `onAfterInitialise`, `onAfterRoute`, `onAfterDispatch`
- `onBeforeRender`, `onBeforeCompileHead`, `onAfterRender`
- `onBeforeRespond`, `onAfterRespond`, `onError`

### Built-in Log Viewer
- Dark-themed interface
- Filter by request ID or event type
- Expandable entries with JSON syntax highlighting
- Loop detection with visual warnings
- Dump log to clipboard for sharing

### Apache Rewrite Detection
Captures `REDIRECT_*` server variables to identify when Apache's mod_rewrite modifies URLs before Joomla processes them.

### Plugin Execution Order
Shows all enabled system plugins in their execution order, highlighting SEF-related plugins that could cause redirects.

## Installation

1. Download the latest release ZIP file
2. Install via Joomla's Extension Manager
3. Enable the plugin at Extensions > Plugins > System - Router Tracer
4. Configure logging options in the plugin settings

## Configuration Options

| Option | Description |
|--------|-------------|
| Log File Name | Name of the log file in Joomla's logs folder |
| Log Stack Traces | Include PHP stack traces in log entries |
| Log HTTP Headers | Capture request headers |
| Log Redirect Detection | Scan for redirect headers and meta refresh |
| Max Log Size | Auto-rotate when log reaches this size (MB) |
| Log Frontend | Enable logging for site requests |
| Log Backend | Enable logging for administrator requests |
| URL Filter | Only log URLs containing specific strings |

## Using the Log Viewer

Access the log viewer from the plugin settings page:

1. **View Log** - Opens the dark-themed log viewer in a new window
2. **Download** - Downloads the raw log file
3. **Clear Log** - Archives and clears the current log

In the viewer:
- Use the **Request ID filter** to see all events from a single page load
- Use the **Event filter** to focus on specific events
- Click any entry to expand and see full details
- Use **Dump Log** to copy formatted log to clipboard

## Diagnosing Redirect Loops

1. Enable the plugin and reproduce the redirect loop
2. Open the log viewer and look for entries with **LOOP DETECTED** badges
3. Check the **SEF Configuration** section to see which plugins are enabled
4. Look at the **Call Chain** to identify which extension is triggering redirects
5. Check for **Apache .htaccess rewrites** that might conflict with Joomla

## Requirements

- Joomla 5.0 or later
- PHP 8.1 or later

## License

This project is licensed under the terms of the GNU General Public License v3.0. See the [LICENSE](LICENSE) file for details.

## Author

[CyberSalt](https://cybersalt.org)
