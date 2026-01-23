# Changelog

All notable changes to the Router Tracer plugin will be documented in this file.

## 🚀 Version 1.1.0 (January 2026)

### 📦 New Features
- **Apache .htaccess Rewrite Detection**: Detects when Apache mod_rewrite modifies URLs before PHP processes them, helping identify `.htaccess` rules causing redirect loops
- **SEF Plugin Configuration Display**: Shows current SEF settings and enabled redirect-related plugins in log entries
- **System Plugin Ordering Display**: Lists all enabled system plugins in execution order, highlighting SEF/redirect plugins
- **Call Chain Tracking**: Identifies which plugin, module, or component triggered each event

### 🔧 Improvements
- **Enhanced Log Viewer**: Dark-themed viewer with expandable entries, JSON syntax highlighting, and caller badges
- **Dump Log Feature**: Copy formatted log to clipboard for easy sharing
- **Better Loop Detection**: Improved pattern matching for trailing slash toggle loops
- **Request ID Tracking**: Each request gets a unique ID for correlating related log entries

## 🚀 Version 1.0.0 (January 2026)

### 📦 New Features
- **Initial Release**: Complete router and URL event logging for Joomla 5
- **Event Logging**: Captures `onAfterInitialise`, `onAfterRoute`, `onAfterDispatch`, `onBeforeRender`, `onBeforeCompileHead`, `onAfterRender`, `onBeforeRespond`, `onAfterRespond`, and `onError`
- **Stack Traces**: Optional PHP stack traces to identify what code triggers each event
- **HTTP Headers Logging**: Captures request headers for debugging
- **Redirect Detection**: Scans for redirect headers and meta refresh tags
- **Log Rotation**: Automatic archiving when log reaches configured size limit
- **URL Filtering**: Filter logging to specific URL patterns
- **Frontend/Backend Filtering**: Selectively log site or administrator requests
- **Built-in Log Viewer**: Dark-themed viewer with filtering by request ID and event type
- **Admin Buttons**: View, Download, and Clear log directly from plugin settings
- **AJAX Integration**: Uses `com_ajax` with proper `SubscriberInterface` pattern

### 🔧 Technical Details
- Joomla 5 native architecture using `services/provider.php` and `SubscriberInterface`
- Custom form field for admin settings buttons
- JSON-formatted log entries for easy parsing
- Session-based request tracking
