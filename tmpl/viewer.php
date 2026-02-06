<?php
/**
 * @package     Cybersalt.Plugin
 * @subpackage  System.RouterTracer
 *
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE file for details.
 *
 * This file is part of cs-joomla-router-tracer.
 *
 * cs-joomla-router-tracer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * cs-joomla-router-tracer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with cs-joomla-router-tracer.  If not, see <https://www.gnu.org/licenses/>.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Variables available:
 * @var string $token     CSRF token
 * @var string $ajaxUrl   Base AJAX URL
 * @var string $lang      Language tag (e.g. en-GB)
 */

// Build JS language object for use in JavaScript
$jsLang = json_encode([
    'loading'              => Text::_('PLG_SYSTEM_ROUTERTRACER_LOADING'),
    'errorLabel'           => Text::_('PLG_SYSTEM_ROUTERTRACER_ERROR_LABEL'),
    'errorUnknown'         => Text::_('PLG_SYSTEM_ROUTERTRACER_ERROR_UNKNOWN'),
    'failedLoadLog'        => Text::_('PLG_SYSTEM_ROUTERTRACER_FAILED_LOAD_LOG'),
    'noLogEntries'         => Text::_('PLG_SYSTEM_ROUTERTRACER_NO_LOG_ENTRIES'),
    'noEntriesMatch'       => Text::_('PLG_SYSTEM_ROUTERTRACER_NO_ENTRIES_MATCH'),
    'loopDetected'         => Text::_('PLG_SYSTEM_ROUTERTRACER_LOOP_DETECTED'),
    'htaccessRewrite'      => Text::_('PLG_SYSTEM_ROUTERTRACER_BADGE_HTACCESS_REWRITE'),
    'warning'              => Text::_('PLG_SYSTEM_ROUTERTRACER_BADGE_WARNING'),
    'urlChanged'           => Text::_('PLG_SYSTEM_ROUTERTRACER_BADGE_URL_CHANGED'),
    'apacheHtaccess'       => Text::_('PLG_SYSTEM_ROUTERTRACER_APACHE_HTACCESS'),
    'rewriteDetected'      => Text::_('PLG_SYSTEM_ROUTERTRACER_BADGE_REWRITE_DETECTED'),
    'noRewrite'            => Text::_('PLG_SYSTEM_ROUTERTRACER_NO_REWRITE'),
    'otherRedirectVars'    => Text::_('PLG_SYSTEM_ROUTERTRACER_OTHER_REDIRECT_VARS'),
    'systemPluginsHeading' => Text::_('PLG_SYSTEM_ROUTERTRACER_SYSTEM_PLUGINS_HEADING'),
    'sefConfiguration'     => Text::_('PLG_SYSTEM_ROUTERTRACER_SEF_CONFIGURATION'),
    'sefUrls'              => Text::_('PLG_SYSTEM_ROUTERTRACER_SEF_URLS'),
    'urlRewriting'         => Text::_('PLG_SYSTEM_ROUTERTRACER_URL_REWRITING'),
    'urlSuffix'            => Text::_('PLG_SYSTEM_ROUTERTRACER_URL_SUFFIX'),
    'enabled'              => Text::_('PLG_SYSTEM_ROUTERTRACER_BADGE_ENABLED'),
    'disabled'             => Text::_('PLG_SYSTEM_ROUTERTRACER_BADGE_DISABLED'),
    'yes'                  => Text::_('JYES'),
    'no'                   => Text::_('JNO'),
    'callChainHeading'     => Text::_('PLG_SYSTEM_ROUTERTRACER_CALL_CHAIN_HEADING'),
    'urlChangedDuring'     => Text::_('PLG_SYSTEM_ROUTERTRACER_URL_CHANGED_DURING'),
    'labelFrom'            => Text::_('PLG_SYSTEM_ROUTERTRACER_LABEL_FROM'),
    'labelTo'              => Text::_('PLG_SYSTEM_ROUTERTRACER_LABEL_TO'),
    'trailingSlashAdded'   => Text::_('PLG_SYSTEM_ROUTERTRACER_TRAILING_SLASH_ADDED'),
    'trailingSlashRemoved' => Text::_('PLG_SYSTEM_ROUTERTRACER_TRAILING_SLASH_REMOVED'),
    'stackTrace'           => Text::_('PLG_SYSTEM_ROUTERTRACER_STACK_TRACE'),
    'showingEntries'       => Text::_('PLG_SYSTEM_ROUTERTRACER_SHOWING_ENTRIES'),
    'btnPrevious'          => Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_PREVIOUS'),
    'btnNext'              => Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_NEXT'),
    'dumpLoading'          => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_LOADING'),
    'copied'               => Text::_('PLG_SYSTEM_ROUTERTRACER_COPIED'),
    'dumpHeader'           => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_HEADER'),
    'dumpGenerated'        => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_GENERATED'),
    'dumpTotal'            => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_TOTAL'),
    'dumpRequestId'        => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_REQUEST_ID'),
    'dumpElapsed'          => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_ELAPSED'),
    'dumpUrl'              => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_URL'),
    'dumpUrlChanged'       => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_URL_CHANGED'),
    'dumpLoopDetected'     => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_LOOP_DETECTED'),
    'dumpPattern'          => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_PATTERN'),
    'dumpData'             => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_DATA'),
    'dumpStackTrace'       => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_STACK_TRACE'),
    'dumpNoEntries'        => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_NO_ENTRIES'),
    'dumpFailed'           => Text::_('PLG_SYSTEM_ROUTERTRACER_DUMP_FAILED'),
    'clearFailed'          => Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_FAILED'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_VIEWER_TITLE'); ?></title>
    <style>
        :root {
            --primary: #1a73e8;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --bg-dark: #1e1e1e;
            --bg-card: #2d2d2d;
            --bg-hover: #3d3d3d;
            --text: #e0e0e0;
            --text-muted: #999;
            --border: #404040;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #1557b0; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #218838; }
        .btn-secondary { background: var(--bg-card); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--bg-hover); }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            background: var(--bg-card);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
        }

        .stat-warning { color: var(--warning); }
        .stat-danger { color: var(--danger); }
        .stat-success { color: var(--success); }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            font-size: 12px;
            color: var(--text-muted);
        }

        input, select {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .log-container {
            background: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
        }

        .log-entry {
            border-bottom: 1px solid var(--border);
            padding: 12px 15px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .log-entry:hover {
            background: var(--bg-hover);
        }

        .log-entry.expanded {
            background: var(--bg-hover);
        }

        .log-entry.has-warning {
            border-left: 3px solid var(--warning);
        }

        .log-entry.has-error {
            border-left: 3px solid var(--danger);
        }

        .log-entry.has-loop {
            border-left: 3px solid var(--danger);
            background: rgba(220, 53, 69, 0.1);
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .log-meta {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .log-timestamp {
            font-family: monospace;
            font-size: 12px;
            color: var(--text-muted);
        }

        .log-request-id {
            font-family: monospace;
            font-size: 11px;
            background: var(--bg-dark);
            padding: 2px 6px;
            border-radius: 3px;
            color: var(--primary);
        }

        .log-event {
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            background: var(--bg-dark);
        }

        .log-event.event-error { background: var(--danger); color: white; }
        .log-event.event-warning { background: var(--warning); color: #333; }

        .log-elapsed {
            font-size: 11px;
            color: var(--text-muted);
        }

        .log-url {
            font-family: monospace;
            font-size: 12px;
            color: var(--text);
            word-break: break-all;
            margin-top: 5px;
        }

        .log-details {
            display: none;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .log-entry.expanded .log-details {
            display: block;
        }

        .json-viewer {
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            background: var(--bg-dark);
            padding: 12px;
            border-radius: 4px;
            max-height: 400px;
            overflow: auto;
        }

        .json-key { color: #9cdcfe; }
        .json-string { color: #ce9178; }
        .json-number { color: #b5cea8; }
        .json-boolean { color: #569cd6; }
        .json-null { color: #569cd6; }

        .warning-badge {
            background: var(--warning);
            color: #333;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }

        .loop-badge {
            background: var(--danger);
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Caller badges - shows which plugin/extension triggered the event */
        .caller-badge {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            font-family: monospace;
        }

        .caller-plugin {
            background: #8b5cf6;
            color: white;
        }

        .caller-component {
            background: #06b6d4;
            color: white;
        }

        .caller-module {
            background: #f59e0b;
            color: #1a1a1a;
        }

        .caller-core {
            background: #64748b;
            color: white;
        }

        .caller-chain {
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 4px;
            border-left: 3px solid #8b5cf6;
        }

        .caller-chain h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #a78bfa;
            text-transform: uppercase;
        }

        .caller-chain-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .caller-method {
            color: #93c5fd;
            font-family: monospace;
            font-size: 12px;
        }

        .caller-class {
            color: var(--text-muted);
            font-family: monospace;
            font-size: 11px;
        }

        .caller-arrow {
            color: #8b5cf6;
            font-weight: bold;
        }

        /* System plugins display */
        .system-plugins-info {
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(6, 182, 212, 0.1);
            border-radius: 4px;
            border-left: 3px solid #06b6d4;
        }

        .system-plugins-info h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #22d3ee;
            text-transform: uppercase;
        }

        .plugin-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .plugin-badge {
            background: #374151;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-family: monospace;
        }

        .plugin-redirect {
            background: #7c2d12;
            color: #fbbf24;
            font-weight: bold;
        }

        /* SEF config display */
        .sef-config-info {
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(245, 158, 11, 0.1);
            border-radius: 4px;
            border-left: 3px solid #f59e0b;
        }

        .sef-config-info h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #fbbf24;
            text-transform: uppercase;
        }

        .config-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }

        .config-item {
            font-size: 12px;
        }

        .redirect-plugins-detail {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(245, 158, 11, 0.3);
        }

        .redirect-plugin-item {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .redirect-plugin-item code {
            font-size: 10px;
            background: rgba(0,0,0,0.3);
            padding: 1px 4px;
            border-radius: 2px;
        }

        /* Apache rewrite detection display */
        .apache-rewrite-info {
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(239, 68, 68, 0.15);
            border-radius: 4px;
            border-left: 3px solid #ef4444;
        }

        .apache-rewrite-info h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #f87171;
            text-transform: uppercase;
        }

        .apache-rewrite-info.detected {
            background: rgba(239, 68, 68, 0.25);
            border-left-width: 5px;
        }

        .rewrite-detected-badge {
            display: inline-block;
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 10px;
            animation: pulse 1.5s infinite;
        }

        .rewrite-detail {
            font-size: 12px;
            margin-bottom: 6px;
            font-family: monospace;
        }

        .rewrite-detail strong {
            color: #fca5a5;
        }

        .rewrite-arrow {
            color: #ef4444;
            font-weight: bold;
            margin: 0 8px;
        }

        .rewrite-from {
            color: #fca5a5;
            text-decoration: line-through;
        }

        .rewrite-to {
            color: #86efac;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
        }

        .pagination-info {
            font-size: 13px;
            color: var(--text-muted);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state h3 {
            margin: 0 0 10px 0;
            color: var(--text);
        }

        .stack-trace {
            margin-top: 10px;
        }

        .stack-trace h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: var(--text-muted);
        }

        .stack-frame {
            font-family: monospace;
            font-size: 11px;
            padding: 4px 8px;
            background: var(--bg-dark);
            margin-bottom: 2px;
            border-radius: 2px;
        }

        .stack-frame .file { color: #9cdcfe; }
        .stack-frame .line { color: #b5cea8; }
        .stack-frame .class { color: #4ec9b0; }
        .stack-frame .function { color: #dcdcaa; }

        .url-change {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid var(--warning);
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .url-change h4 {
            margin: 0 0 8px 0;
            color: var(--warning);
            font-size: 12px;
        }

        .url-change-detail {
            font-family: monospace;
            font-size: 11px;
            margin: 4px 0;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--bg-card);
            border-radius: 8px;
            padding: 20px;
            max-width: 400px;
            width: 90%;
        }

        .modal h3 {
            margin: 0 0 15px 0;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_VIEWER_HEADING'); ?>
            </h1>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="refreshLog()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    <?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_REFRESH'); ?>
                </button>
                <button class="btn btn-primary" onclick="dumpLog()" title="<?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_DUMP_LOG_TITLE'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_DUMP_LOG'); ?>
                </button>
                <button class="btn btn-success" onclick="downloadLog()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_DOWNLOAD'); ?>
                </button>
                <button class="btn btn-danger" onclick="showClearModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    <?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_LOG'); ?>
                </button>
            </div>
        </div>

        <div class="stats-bar" id="statsBar">
            <div class="stat">
                <span class="stat-value" id="statEntries">-</span>
                <span class="stat-label"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_STAT_LOG_ENTRIES'); ?></span>
            </div>
            <div class="stat">
                <span class="stat-value" id="statRequests">-</span>
                <span class="stat-label"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_STAT_REQUESTS'); ?></span>
            </div>
            <div class="stat">
                <span class="stat-value" id="statSize">-</span>
                <span class="stat-label"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_STAT_FILE_SIZE'); ?></span>
            </div>
            <div class="stat">
                <span class="stat-value stat-warning" id="statWarnings">-</span>
                <span class="stat-label"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_STAT_WARNINGS'); ?></span>
            </div>
            <div class="stat">
                <span class="stat-value stat-danger" id="statLoops">-</span>
                <span class="stat-label"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_STAT_POTENTIAL_LOOPS'); ?></span>
            </div>
            <div class="stat">
                <span class="stat-value stat-danger" id="statErrors">-</span>
                <span class="stat-label"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_STAT_ERRORS'); ?></span>
            </div>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_REQUEST_ID'); ?></label>
                <input type="text" id="filterRequestId" placeholder="<?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_REQUEST_ID_PLACEHOLDER'); ?>" style="width: 150px;">
            </div>
            <div class="filter-group">
                <label><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_EVENT'); ?></label>
                <select id="filterEvent" style="min-width: 180px;">
                    <option value=""><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_ALL_EVENTS'); ?></option>
                    <option value="onAfterInitialise">onAfterInitialise</option>
                    <option value="onAfterRoute">onAfterRoute</option>
                    <option value="onParseRoute">onParseRoute</option>
                    <option value="onBuildRoute">onBuildRoute</option>
                    <option value="onAfterDispatch">onAfterDispatch</option>
                    <option value="onBeforeRender">onBeforeRender</option>
                    <option value="onAfterRender">onAfterRender</option>
                    <option value="onBeforeRespond">onBeforeRespond</option>
                    <option value="onAfterRespond">onAfterRespond</option>
                    <option value="onError">onError</option>
                    <option value="FATAL_ERROR">FATAL_ERROR</option>
                </select>
            </div>
            <div class="filter-group">
                <label><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_SHOW'); ?></label>
                <select id="filterLimit">
                    <option value="50"><?php echo sprintf(Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_N_ENTRIES'), 50); ?></option>
                    <option value="100" selected><?php echo sprintf(Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_N_ENTRIES'), 100); ?></option>
                    <option value="250"><?php echo sprintf(Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_N_ENTRIES'), 250); ?></option>
                    <option value="500"><?php echo sprintf(Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_N_ENTRIES'), 500); ?></option>
                </select>
            </div>
            <div class="filter-group">
                <label>
                    <input type="checkbox" id="filterWarningsOnly"> <?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_FILTER_WARNINGS_ONLY'); ?>
                </label>
            </div>
            <button class="btn btn-secondary" onclick="applyFilters()"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_APPLY_FILTERS'); ?></button>
        </div>

        <div class="log-container" id="logContainer">
            <div class="loading"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_LOADING'); ?></div>
        </div>
    </div>

    <!-- Clear Confirmation Modal -->
    <div class="modal-overlay" id="clearModal">
        <div class="modal">
            <h3><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_MODAL_TITLE'); ?></h3>
            <p><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_MODAL_TEXT'); ?></p>
            <div class="modal-buttons">
                <button class="btn btn-secondary" onclick="hideClearModal()"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_BTN_CANCEL'); ?></button>
                <button class="btn btn-danger" onclick="clearLog()"><?php echo Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_LOG'); ?></button>
            </div>
        </div>
    </div>

    <script>
        const ajaxUrl = '<?php echo $ajaxUrl; ?>';
        const RT_LANG = <?php echo $jsLang; ?>;
        let currentOffset = 0;
        let totalEntries = 0;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadLog();

            // Enter key on filter inputs
            document.getElementById('filterRequestId').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') applyFilters();
            });
        });

        function loadStats() {
            fetch(ajaxUrl + '&action=stats')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const s = data.stats;
                        document.getElementById('statEntries').textContent = s.entry_count;
                        document.getElementById('statRequests').textContent = s.request_count;
                        document.getElementById('statSize').textContent = s.file_size_human;
                        document.getElementById('statWarnings').textContent = s.warnings;
                        document.getElementById('statLoops').textContent = s.potential_loops;
                        document.getElementById('statErrors').textContent = s.errors;
                    }
                })
                .catch(err => console.error('Failed to load stats:', err));
        }

        function loadLog() {
            const container = document.getElementById('logContainer');
            container.innerHTML = '<div class="loading">' + escapeHtml(RT_LANG.loading) + '</div>';

            const requestId = document.getElementById('filterRequestId').value;
            const limit = document.getElementById('filterLimit').value;

            let url = ajaxUrl + '&action=view&lines=' + limit + '&offset=' + currentOffset;
            if (requestId) url += '&request_id=' + encodeURIComponent(requestId);

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        totalEntries = data.total;
                        renderEntries(data.entries);
                    } else {
                        container.innerHTML = '<div class="empty-state"><h3>' + escapeHtml(RT_LANG.errorLabel) + '</h3><p>' + escapeHtml(data.error || RT_LANG.errorUnknown) + '</p></div>';
                    }
                })
                .catch(err => {
                    container.innerHTML = '<div class="empty-state"><h3>' + escapeHtml(RT_LANG.errorLabel) + '</h3><p>' + escapeHtml(RT_LANG.failedLoadLog) + escapeHtml(err.message) + '</p></div>';
                });
        }

        function renderEntries(entries) {
            const container = document.getElementById('logContainer');
            const eventFilter = document.getElementById('filterEvent').value;
            const warningsOnly = document.getElementById('filterWarningsOnly').checked;

            // Apply client-side filters
            let filtered = entries;
            if (eventFilter) {
                filtered = filtered.filter(e => e.event === eventFilter);
            }
            if (warningsOnly) {
                filtered = filtered.filter(e => {
                    return e.data?.redirect_detected?.analysis?.warning ||
                           e.data?.loop_analysis?.potential_loop ||
                           e.event === 'onError' ||
                           e.event === 'FATAL_ERROR';
                });
            }

            if (filtered.length === 0) {
                container.innerHTML = '<div class="empty-state"><h3>' + escapeHtml(RT_LANG.noLogEntries) + '</h3><p>' + escapeHtml(RT_LANG.noEntriesMatch) + '</p></div>';
                return;
            }

            let html = '';
            filtered.forEach((entry, idx) => {
                const hasWarning = entry.data?.redirect_detected?.analysis?.warning;
                const hasLoop = entry.data?.loop_analysis?.potential_loop;
                const hasError = entry.event === 'onError' || entry.event === 'FATAL_ERROR';
                const hasUrlChange = entry.data?.url_change;
                const hasApacheRewrite = entry.data?.url?.apache_rewrite?.rewrite_detected;

                let classes = 'log-entry';
                if (hasLoop) classes += ' has-loop';
                else if (hasError) classes += ' has-error';
                else if (hasWarning || hasUrlChange || hasApacheRewrite) classes += ' has-warning';

                let eventClass = 'log-event';
                if (hasError) eventClass += ' event-error';
                else if (hasWarning || hasLoop) eventClass += ' event-warning';

                const url = entry.data?.url?.full_url || entry.data?.url?.request_uri || '-';

                html += '<div class="' + classes + '" onclick="toggleEntry(this)">';
                html += '<div class="log-header">';
                html += '<div class="log-meta">';
                html += '<span class="log-timestamp">' + entry.timestamp + '</span>';
                html += '<span class="log-request-id">' + entry.request_id + '</span>';
                html += '<span class="' + eventClass + '">' + entry.event + '</span>';
                if (hasLoop) html += '<span class="loop-badge">' + escapeHtml(RT_LANG.loopDetected) + '</span>';
                if (hasApacheRewrite) html += '<span class="loop-badge" style="background: #ef4444;">' + escapeHtml(RT_LANG.htaccessRewrite) + '</span>';
                if (hasWarning && !hasLoop) html += '<span class="warning-badge">' + escapeHtml(RT_LANG.warning) + '</span>';
                if (hasUrlChange) html += '<span class="warning-badge">' + escapeHtml(RT_LANG.urlChanged) + '</span>';

                // Display caller information
                if (entry.caller && entry.caller.primary) {
                    const caller = entry.caller.primary;
                    let callerHtml = '<span class="caller-badge caller-' + caller.type + '">';
                    if (caller.type === 'plugin') {
                        callerHtml += 'plg_' + caller.group + '_' + caller.name;
                    } else if (caller.type === 'module') {
                        callerHtml += caller.name;
                    } else if (caller.type === 'component') {
                        callerHtml += caller.name;
                    } else if (caller.type === 'core') {
                        callerHtml += 'core:' + (caller.area || 'joomla');
                    } else {
                        callerHtml += caller.type;
                    }
                    callerHtml += '</span>';
                    html += callerHtml;
                }

                html += '</div>';
                html += '<span class="log-elapsed">' + entry.elapsed_ms + 'ms</span>';
                html += '</div>';
                html += '<div class="log-url">' + escapeHtml(url) + '</div>';

                // Details section
                html += '<div class="log-details">';

                // Apache .htaccess rewrite detection (shows if URL was modified by Apache before PHP)
                if (entry.data?.url?.apache_rewrite) {
                    const rewrite = entry.data.url.apache_rewrite;
                    const hasRewrite = rewrite.rewrite_detected || rewrite.redirect_url || rewrite.redirect_status;
                    html += '<div class="apache-rewrite-info' + (rewrite.rewrite_detected ? ' detected' : '') + '">';
                    html += '<h4>' + escapeHtml(RT_LANG.apacheHtaccess);
                    if (rewrite.rewrite_detected) {
                        html += '<span class="rewrite-detected-badge">' + escapeHtml(RT_LANG.rewriteDetected) + '</span>';
                    }
                    html += '</h4>';

                    if (rewrite.rewrite_detected) {
                        html += '<div class="rewrite-detail">';
                        html += '<span class="rewrite-from">' + escapeHtml(rewrite.rewrite_from) + '</span>';
                        html += '<span class="rewrite-arrow">→</span>';
                        html += '<span class="rewrite-to">' + escapeHtml(rewrite.rewrite_to) + '</span>';
                        html += '</div>';
                    }

                    if (rewrite.redirect_url) {
                        html += '<div class="rewrite-detail"><strong>REDIRECT_URL:</strong> ' + escapeHtml(rewrite.redirect_url) + '</div>';
                    }
                    if (rewrite.redirect_status) {
                        html += '<div class="rewrite-detail"><strong>REDIRECT_STATUS:</strong> ' + rewrite.redirect_status + '</div>';
                    }
                    if (rewrite.the_request) {
                        html += '<div class="rewrite-detail"><strong>THE_REQUEST:</strong> ' + escapeHtml(rewrite.the_request) + '</div>';
                    }
                    if (rewrite.script_url) {
                        html += '<div class="rewrite-detail"><strong>SCRIPT_URL:</strong> ' + escapeHtml(rewrite.script_url) + '</div>';
                    }
                    if (rewrite.script_name) {
                        html += '<div class="rewrite-detail"><strong>SCRIPT_NAME:</strong> ' + escapeHtml(rewrite.script_name) + '</div>';
                    }
                    if (rewrite.path_info) {
                        html += '<div class="rewrite-detail"><strong>PATH_INFO:</strong> ' + escapeHtml(rewrite.path_info) + '</div>';
                    }
                    if (rewrite.other_redirect_vars && Object.keys(rewrite.other_redirect_vars).length > 0) {
                        html += '<div class="rewrite-detail"><strong>' + escapeHtml(RT_LANG.otherRedirectVars) + '</strong></div>';
                        Object.keys(rewrite.other_redirect_vars).forEach(key => {
                            html += '<div class="rewrite-detail" style="margin-left: 15px;"><code>' + key + '</code>: ' + escapeHtml(String(rewrite.other_redirect_vars[key]).substring(0, 100)) + '</div>';
                        });
                    }

                    if (!hasRewrite) {
                        html += '<div class="rewrite-detail" style="color: #86efac;">' + escapeHtml(RT_LANG.noRewrite) + '</div>';
                    }

                    html += '</div>';
                }

                // System plugins list (shows enabled plugins that could cause redirects)
                if (entry.data?.system_plugins && entry.data.system_plugins.length > 0) {
                    html += '<div class="system-plugins-info">';
                    html += '<h4>' + escapeHtml(RT_LANG.systemPluginsHeading) + '</h4>';
                    html += '<div class="plugin-list">';
                    entry.data.system_plugins.forEach(plugin => {
                        const isRedirectRelated = ['redirect', 'sef', 'languagefilter', 'languagecode', 'joomsef', 'sh404sef', 'acesef'].some(
                            name => plugin.name.toLowerCase().includes(name)
                        );
                        const badgeClass = isRedirectRelated ? 'plugin-badge plugin-redirect' : 'plugin-badge';
                        html += '<span class="' + badgeClass + '" title="Order: ' + plugin.ordering + '">';
                        html += plugin.name;
                        if (isRedirectRelated) html += ' ⚠️';
                        html += '</span>';
                    });
                    html += '</div>';
                    html += '</div>';
                }

                // SEF Configuration (critical for redirect debugging)
                if (entry.data?.sef_config) {
                    const cfg = entry.data.sef_config;
                    html += '<div class="sef-config-info">';
                    html += '<h4>' + escapeHtml(RT_LANG.sefConfiguration) + '</h4>';
                    html += '<div class="config-grid">';
                    html += '<span class="config-item"><strong>' + escapeHtml(RT_LANG.sefUrls) + '</strong> ' + (cfg.sef ? RT_LANG.yes : RT_LANG.no) + '</span>';
                    html += '<span class="config-item"><strong>' + escapeHtml(RT_LANG.urlRewriting) + '</strong> ' + (cfg.sef_rewrite ? RT_LANG.yes : RT_LANG.no) + '</span>';
                    html += '<span class="config-item"><strong>' + escapeHtml(RT_LANG.urlSuffix) + '</strong> ' + (cfg.sef_suffix ? RT_LANG.yes : RT_LANG.no) + '</span>';
                    html += '</div>';

                    if (cfg.redirect_plugins) {
                        html += '<div class="redirect-plugins-detail">';
                        Object.keys(cfg.redirect_plugins).forEach(name => {
                            const p = cfg.redirect_plugins[name];
                            html += '<div class="redirect-plugin-item">';
                            html += '<strong>' + name + ':</strong> ' + (p.enabled ? '<span style="color: var(--warning)">' + escapeHtml(RT_LANG.enabled) + '</span>' : escapeHtml(RT_LANG.disabled));
                            if (p.params && Object.keys(p.params).length > 0) {
                                html += ' <code>' + JSON.stringify(p.params).substring(0, 100) + '</code>';
                            }
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    html += '</div>';
                }

                // Caller chain info (shows what triggered this event)
                if (entry.caller && entry.caller.chain && entry.caller.chain.length > 1) {
                    html += '<div class="caller-chain">';
                    html += '<h4>' + escapeHtml(RT_LANG.callChainHeading) + '</h4>';
                    entry.caller.chain.forEach((caller, i) => {
                        let name = '';
                        if (caller.type === 'plugin') {
                            name = 'plg_' + caller.group + '_' + caller.name;
                        } else if (caller.type === 'module' || caller.type === 'component') {
                            name = caller.name;
                        } else if (caller.type === 'core') {
                            name = 'core:' + (caller.area || 'joomla');
                        }
                        html += '<div class="caller-chain-item">';
                        html += '<span class="caller-badge caller-' + caller.type + '">' + name + '</span>';
                        if (caller.method) html += ' <span class="caller-method">::' + caller.method + '()</span>';
                        if (caller.class) html += ' <span class="caller-class">' + escapeHtml(caller.class) + '</span>';
                        if (i < entry.caller.chain.length - 1) html += ' <span class="caller-arrow">→</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                // URL Change info
                if (hasUrlChange) {
                    html += '<div class="url-change">';
                    html += '<h4>' + escapeHtml(RT_LANG.urlChangedDuring) + '</h4>';
                    html += '<div class="url-change-detail"><strong>' + escapeHtml(RT_LANG.labelFrom) + '</strong> ' + escapeHtml(entry.data.url_change.previous_url) + '</div>';
                    html += '<div class="url-change-detail"><strong>' + escapeHtml(RT_LANG.labelTo) + '</strong> ' + escapeHtml(entry.data.url_change.current_url) + '</div>';
                    if (entry.data.url_change.trailing_slash_added) {
                        html += '<div class="url-change-detail" style="color: var(--warning);">' + escapeHtml(RT_LANG.trailingSlashAdded) + '</div>';
                    }
                    if (entry.data.url_change.trailing_slash_removed) {
                        html += '<div class="url-change-detail" style="color: var(--warning);">' + escapeHtml(RT_LANG.trailingSlashRemoved) + '</div>';
                    }
                    html += '</div>';
                }

                // Stack trace
                if (entry.stack_trace && entry.stack_trace.length > 0) {
                    html += '<div class="stack-trace">';
                    html += '<h4>' + escapeHtml(RT_LANG.stackTrace) + '</h4>';
                    entry.stack_trace.forEach(frame => {
                        html += '<div class="stack-frame">';
                        if (frame.file) html += '<span class="file">' + escapeHtml(frame.file) + '</span>';
                        if (frame.line) html += ':<span class="line">' + frame.line + '</span>';
                        if (frame.class) html += ' <span class="class">' + escapeHtml(frame.class) + '</span>';
                        if (frame.function) html += '::<span class="function">' + escapeHtml(frame.function) + '()</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                // Full JSON data
                html += '<div class="json-viewer">' + syntaxHighlight(JSON.stringify(entry.data, null, 2)) + '</div>';
                html += '</div>';
                html += '</div>';
            });

            // Pagination
            html += '<div class="pagination">';
            html += '<span class="pagination-info">' + RT_LANG.showingEntries.replace('%s', filtered.length).replace('%s', totalEntries) + '</span>';
            html += '<div class="btn-group">';
            if (currentOffset > 0) {
                html += '<button class="btn btn-secondary" onclick="prevPage()">' + escapeHtml(RT_LANG.btnPrevious) + '</button>';
            }
            const limit = parseInt(document.getElementById('filterLimit').value);
            if (currentOffset + limit < totalEntries) {
                html += '<button class="btn btn-secondary" onclick="nextPage()">' + escapeHtml(RT_LANG.btnNext) + '</button>';
            }
            html += '</div>';
            html += '</div>';

            container.innerHTML = html;
        }

        function toggleEntry(el) {
            el.classList.toggle('expanded');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function syntaxHighlight(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function(match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }

        function refreshLog() {
            loadStats();
            loadLog();
        }

        function applyFilters() {
            currentOffset = 0;
            loadLog();
        }

        function prevPage() {
            const limit = parseInt(document.getElementById('filterLimit').value);
            currentOffset = Math.max(0, currentOffset - limit);
            loadLog();
        }

        function nextPage() {
            const limit = parseInt(document.getElementById('filterLimit').value);
            currentOffset += limit;
            loadLog();
        }

        function showClearModal() {
            document.getElementById('clearModal').classList.add('active');
        }

        function hideClearModal() {
            document.getElementById('clearModal').classList.remove('active');
        }

        function clearLog() {
            fetch(ajaxUrl + '&action=clear')
                .then(r => r.json())
                .then(data => {
                    hideClearModal();
                    if (data.success) {
                        refreshLog();
                    } else {
                        alert(RT_LANG.errorLabel + ': ' + (data.error || RT_LANG.errorUnknown));
                    }
                })
                .catch(err => {
                    hideClearModal();
                    alert(RT_LANG.clearFailed + err.message);
                });
        }

        function downloadLog() {
            window.location.href = ajaxUrl + '&action=download';
        }

        function dumpLog() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> ' + escapeHtml(RT_LANG.dumpLoading);
            btn.disabled = true;

            // Fetch all log entries (up to 10000)
            fetch(ajaxUrl + '&action=view&lines=10000&offset=0')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.entries) {
                        // Format as readable text
                        let output = RT_LANG.dumpHeader + '\n';
                        output += RT_LANG.dumpGenerated + ' ' + new Date().toISOString() + '\n';
                        output += RT_LANG.dumpTotal + ' ' + data.total + '\n';
                        output += '================================\n\n';

                        data.entries.forEach(entry => {
                            output += '--- [' + entry.timestamp + '] ' + entry.event + ' ---\n';
                            output += RT_LANG.dumpRequestId + ' ' + entry.request_id + '\n';
                            output += RT_LANG.dumpElapsed + ' ' + entry.elapsed_ms + 'ms\n';

                            if (entry.data?.url?.full_url) {
                                output += RT_LANG.dumpUrl + ' ' + entry.data.url.full_url + '\n';
                            }

                            if (entry.data?.url_change) {
                                output += RT_LANG.dumpUrlChanged + '\n';
                                output += '  ' + RT_LANG.labelFrom + ' ' + entry.data.url_change.previous_url + '\n';
                                output += '  ' + RT_LANG.labelTo + ' ' + entry.data.url_change.current_url + '\n';
                            }

                            if (entry.data?.loop_analysis?.potential_loop) {
                                output += RT_LANG.dumpLoopDetected + '\n';
                                output += '  ' + RT_LANG.dumpPattern + ' ' + (entry.data.loop_analysis.pattern || 'unknown') + '\n';
                            }

                            output += RT_LANG.dumpData + ' ' + JSON.stringify(entry.data, null, 2) + '\n';

                            if (entry.stack_trace && entry.stack_trace.length > 0) {
                                output += RT_LANG.dumpStackTrace + '\n';
                                entry.stack_trace.forEach(frame => {
                                    output += '  ' + (frame.file || '?') + ':' + (frame.line || '?');
                                    if (frame.class) output += ' ' + frame.class;
                                    if (frame.function) output += '::' + frame.function + '()';
                                    output += '\n';
                                });
                            }

                            output += '\n';
                        });

                        // Copy to clipboard
                        navigator.clipboard.writeText(output).then(() => {
                            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> ' + escapeHtml(RT_LANG.copied);
                            setTimeout(() => {
                                btn.innerHTML = originalText;
                                btn.disabled = false;
                            }, 2000);
                        }).catch(err => {
                            // Fallback for older browsers
                            const textarea = document.createElement('textarea');
                            textarea.value = output;
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);

                            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> ' + escapeHtml(RT_LANG.copied);
                            setTimeout(() => {
                                btn.innerHTML = originalText;
                                btn.disabled = false;
                            }, 2000);
                        });
                    } else {
                        alert(RT_LANG.dumpNoEntries);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    alert(RT_LANG.dumpFailed + err.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }
    </script>
</body>
</html>
