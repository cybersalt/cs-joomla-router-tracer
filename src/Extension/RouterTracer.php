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

namespace Cybersalt\Plugin\System\RouterTracer\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Router;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

/**
 * Router Tracer Plugin - Logs all router and URL manipulation events for debugging
 */
class RouterTracer extends CMSPlugin implements SubscriberInterface
{
    /**
     * @var CMSApplication
     */
    protected $app;

    /**
     * @var string The log file path
     */
    private string $logFile;

    /**
     * @var string Unique request ID for correlating log entries
     */
    private string $requestId;

    /**
     * @var float Request start time for timing
     */
    private float $startTime;

    /**
     * @var array Track URL changes through the request
     */
    private array $urlHistory = [];

    /**
     * @var bool Whether we're in a shutdown handler
     */
    private bool $inShutdown = false;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The event dispatcher
     * @param   array                $config      Plugin configuration
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        $this->requestId = substr(md5(uniqid('', true)), 0, 8);
        $this->startTime = microtime(true);

        // Register shutdown function to catch fatal errors and final state
        register_shutdown_function([$this, 'onShutdown']);
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Core application lifecycle events
            'onAfterInitialise'     => 'onAfterInitialise',
            'onAfterRoute'          => 'onAfterRoute',
            'onAfterDispatch'       => 'onAfterDispatch',
            'onBeforeRender'        => 'onBeforeRender',
            'onAfterRender'         => 'onAfterRender',
            'onBeforeRespond'       => 'onBeforeRespond',
            'onAfterRespond'        => 'onAfterRespond',

            // Router-specific events
            'onParseRoute'          => 'onParseRoute',
            'onBuildRoute'          => 'onBuildRoute',

            // SEF events (custom router events)
            'onAfterRouterParse'    => 'onAfterRouterParse',

            // Head/document events
            'onBeforeCompileHead'   => 'onBeforeCompileHead',

            // Error events
            'onError'               => 'onError',

            // AJAX handler for com_ajax
            'onAjaxRoutertracer'    => 'onAjaxRoutertracer',
        ];
    }

    /**
     * Get the application instance, with fallback to Factory
     *
     * @return  CMSApplication|null
     */
    private function getApp(): ?CMSApplication
    {
        if ($this->app) {
            return $this->app;
        }

        try {
            return Factory::getApplication();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if logging should occur for this request
     *
     * @return  bool
     */
    private function shouldLog(): bool
    {
        $app = $this->getApp();

        if (!$app) {
            return false;
        }

        $isSite = $app->isClient('site');
        $isAdmin = $app->isClient('administrator');

        // Check frontend/backend filters
        if ($isSite && !$this->getParam('filter_frontend', 1)) {
            return false;
        }

        if ($isAdmin && !$this->getParam('filter_backend', 0)) {
            return false;
        }

        // Check URL filter if set
        $urlFilter = trim($this->getParam('url_filter', ''));
        if (!empty($urlFilter)) {
            $currentUrl = Uri::getInstance()->toString();
            $filters = array_filter(array_map('trim', explode("\n", $urlFilter)));

            $matches = false;
            foreach ($filters as $filter) {
                if (stripos($currentUrl, $filter) !== false) {
                    $matches = true;
                    break;
                }
            }

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a parameter value safely
     *
     * @param   string  $key      Parameter key
     * @param   mixed   $default  Default value
     *
     * @return  mixed
     */
    private function getParam(string $key, $default = null)
    {
        if ($this->params && method_exists($this->params, 'get')) {
            return $this->params->get($key, $default);
        }

        return $default;
    }

    /**
     * Get the log file path
     *
     * @return  string
     */
    private function getLogFile(): string
    {
        if (!isset($this->logFile)) {
            $fileName = $this->getParam('log_file', 'router_trace.log');
            $this->logFile = JPATH_ROOT . '/logs/' . $fileName;
        }

        return $this->logFile;
    }

    /**
     * Write to the log file
     *
     * @param   string  $event    Event name
     * @param   array   $data     Data to log
     * @param   bool    $force    Force logging even if shouldLog returns false
     *
     * @return  void
     */
    private function log(string $event, array $data = [], bool $force = false): void
    {
        if (!$force && !$this->shouldLog()) {
            return;
        }

        $logFile = $this->getLogFile();

        // Check log file size and rotate if needed
        $maxSize = (int) $this->getParam('max_log_size', 5) * 1024 * 1024; // MB to bytes
        if (file_exists($logFile) && filesize($logFile) > $maxSize) {
            $this->rotateLog();
        }

        $elapsed = round((microtime(true) - $this->startTime) * 1000, 2);

        $entry = [
            'timestamp'  => date('Y-m-d H:i:s.') . substr(microtime(), 2, 4),
            'request_id' => $this->requestId,
            'elapsed_ms' => $elapsed,
            'event'      => $event,
            'caller'     => $this->identifyCaller(),
            'data'       => $data,
        ];

        // Add stack trace if enabled
        if ($this->getParam('log_stack_traces', 1) && !$this->inShutdown) {
            $entry['stack_trace'] = $this->getFilteredStackTrace();
        }

        $logLine = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        // Ensure logs directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get a filtered stack trace (remove noise, focus on useful frames)
     *
     * @return  array
     */
    private function getFilteredStackTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $filtered = [];

        foreach ($trace as $frame) {
            // Skip our own methods
            if (isset($frame['class']) && $frame['class'] === self::class) {
                continue;
            }

            // Skip event dispatcher internals
            if (isset($frame['class']) && stripos($frame['class'], 'Dispatcher') !== false) {
                continue;
            }

            $entry = [];

            if (isset($frame['file'])) {
                // Make path relative to JPATH_ROOT for readability
                $entry['file'] = str_replace(JPATH_ROOT . '/', '', $frame['file']);
            }

            if (isset($frame['line'])) {
                $entry['line'] = $frame['line'];
            }

            if (isset($frame['class'])) {
                $entry['class'] = $frame['class'];
            }

            if (isset($frame['function'])) {
                $entry['function'] = $frame['function'];
            }

            if (!empty($entry)) {
                $filtered[] = $entry;
            }

            // Limit to 8 relevant frames
            if (count($filtered) >= 8) {
                break;
            }
        }

        return $filtered;
    }

    /**
     * Identify the caller (plugin/extension/component) from the stack trace
     *
     * @return  array  Caller information with type, name, and class
     */
    private function identifyCaller(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 25);
        $callers = [];

        foreach ($trace as $frame) {
            if (!isset($frame['class'])) {
                continue;
            }

            $class = $frame['class'];

            // Skip our own class
            if ($class === self::class) {
                continue;
            }

            // Skip Joomla core dispatchers and event handlers
            if (preg_match('/^Joomla\\\\(CMS\\\\)?Event\\\\/', $class) ||
                preg_match('/^Joomla\\\\Event\\\\/', $class)) {
                continue;
            }

            // Detect plugins: Vendor\Plugin\Group\Name\...
            if (preg_match('/^([A-Za-z0-9_]+)\\\\Plugin\\\\([A-Za-z0-9_]+)\\\\([A-Za-z0-9_]+)\\\\/', $class, $matches)) {
                $callers[] = [
                    'type'   => 'plugin',
                    'group'  => strtolower($matches[2]),
                    'name'   => strtolower($matches[3]),
                    'vendor' => $matches[1],
                    'class'  => $class,
                    'method' => $frame['function'] ?? null,
                ];
                continue;
            }

            // Detect modules: Vendor\Module\Name\...
            if (preg_match('/^([A-Za-z0-9_]+)\\\\Module\\\\([A-Za-z0-9_]+)\\\\/', $class, $matches)) {
                $callers[] = [
                    'type'   => 'module',
                    'name'   => 'mod_' . strtolower($matches[2]),
                    'vendor' => $matches[1],
                    'class'  => $class,
                    'method' => $frame['function'] ?? null,
                ];
                continue;
            }

            // Detect components: Vendor\Component\Name\...
            if (preg_match('/^([A-Za-z0-9_]+)\\\\Component\\\\([A-Za-z0-9_]+)\\\\/', $class, $matches)) {
                $callers[] = [
                    'type'   => 'component',
                    'name'   => 'com_' . strtolower($matches[2]),
                    'vendor' => $matches[1],
                    'class'  => $class,
                    'method' => $frame['function'] ?? null,
                ];
                continue;
            }

            // Detect Joomla core classes for context
            if (preg_match('/^Joomla\\\\CMS\\\\([A-Za-z0-9_]+)\\\\([A-Za-z0-9_]+)/', $class, $matches)) {
                $callers[] = [
                    'type'   => 'core',
                    'area'   => $matches[1],
                    'class'  => $class,
                    'method' => $frame['function'] ?? null,
                ];
                continue;
            }

            // Detect legacy plugins by file path
            if (isset($frame['file']) && preg_match('/plugins[\/\\\\]([a-z0-9_]+)[\/\\\\]([a-z0-9_]+)[\/\\\\]/i', $frame['file'], $matches)) {
                $callers[] = [
                    'type'   => 'plugin',
                    'group'  => strtolower($matches[1]),
                    'name'   => strtolower($matches[2]),
                    'file'   => str_replace(JPATH_ROOT . '/', '', $frame['file']),
                    'method' => $frame['function'] ?? null,
                ];
                continue;
            }

            // Detect legacy components by file path
            if (isset($frame['file']) && preg_match('/components[\/\\\\](com_[a-z0-9_]+)[\/\\\\]/i', $frame['file'], $matches)) {
                $callers[] = [
                    'type'   => 'component',
                    'name'   => strtolower($matches[1]),
                    'file'   => str_replace(JPATH_ROOT . '/', '', $frame['file']),
                    'method' => $frame['function'] ?? null,
                ];
                continue;
            }

            // Detect legacy modules by file path
            if (isset($frame['file']) && preg_match('/modules[\/\\\\](mod_[a-z0-9_]+)[\/\\\\]/i', $frame['file'], $matches)) {
                $callers[] = [
                    'type'   => 'module',
                    'name'   => strtolower($matches[1]),
                    'file'   => str_replace(JPATH_ROOT . '/', '', $frame['file']),
                    'method' => $frame['function'] ?? null,
                ];
                continue;
            }
        }

        // Remove duplicates and return unique callers
        $seen = [];
        $unique = [];
        foreach ($callers as $caller) {
            $key = ($caller['type'] ?? '') . ':' . ($caller['name'] ?? $caller['class'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $caller;
            }
        }

        // Return summary: primary caller + count of others
        if (empty($unique)) {
            return ['type' => 'core', 'name' => 'joomla'];
        }

        $result = [
            'primary' => $unique[0],
        ];

        // Add chain if multiple callers (helps trace the path)
        if (count($unique) > 1) {
            $result['chain'] = array_slice($unique, 0, 5);
        }

        return $result;
    }

    /**
     * Get list of enabled system plugins with their ordering
     *
     * @return  array
     */
    private function getEnabledSystemPlugins(): array
    {
        $plugins = [];

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select(['element', 'name', 'ordering', 'params'])
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('enabled') . ' = 1')
                ->order($db->quoteName('ordering') . ' ASC');

            $db->setQuery($query);
            $results = $db->loadObjectList();

            foreach ($results as $plugin) {
                $plugins[] = [
                    'name'     => $plugin->element,
                    'ordering' => (int) $plugin->ordering,
                ];
            }
        } catch (\Exception $e) {
            $plugins[] = ['error' => $e->getMessage()];
        }

        return $plugins;
    }

    /**
     * Get SEF and URL configuration settings
     *
     * @return  array
     */
    private function getSefConfiguration(): array
    {
        $config = [];

        try {
            $app = $this->getApp();

            if ($app) {
                $config['sef'] = (bool) $app->get('sef', 0);
                $config['sef_rewrite'] = (bool) $app->get('sef_rewrite', 0);
                $config['sef_suffix'] = (bool) $app->get('sef_suffix', 0);
                $config['unicodeslugs'] = (bool) $app->get('unicodeslugs', 0);
            }

            // Check for common redirect plugins configuration
            $db = Factory::getContainer()->get('DatabaseDriver');

            // Check redirect plugin status
            $query = $db->getQuery(true)
                ->select(['element', 'enabled', 'params'])
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' IN (' . implode(',', [
                    $db->quote('redirect'),
                    $db->quote('sef'),
                    $db->quote('languagefilter'),
                    $db->quote('languagecode'),
                ]) . ')');

            $db->setQuery($query);
            $redirectPlugins = $db->loadObjectList('element');

            $config['redirect_plugins'] = [];
            foreach ($redirectPlugins as $name => $plugin) {
                $params = json_decode($plugin->params, true) ?: [];
                $config['redirect_plugins'][$name] = [
                    'enabled' => (bool) $plugin->enabled,
                    'params'  => $params,
                ];
            }

        } catch (\Exception $e) {
            $config['error'] = $e->getMessage();
        }

        return $config;
    }

    /**
     * Rotate the log file
     *
     * @return  void
     */
    private function rotateLog(): void
    {
        $logFile = $this->getLogFile();

        if (file_exists($logFile)) {
            $rotatedFile = $logFile . '.' . date('Y-m-d-His');
            rename($logFile, $rotatedFile);

            // Keep only last 5 rotated files
            $pattern = $logFile . '.*';
            $files = glob($pattern);
            if ($files && count($files) > 5) {
                usort($files, function ($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                $toDelete = array_slice($files, 0, count($files) - 5);
                foreach ($toDelete as $file) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Capture current URL state
     *
     * @return  array
     */
    private function captureUrlState(): array
    {
        $uri = Uri::getInstance();

        $state = [
            'full_url'    => $uri->toString(),
            'path'        => $uri->getPath(),
            'query'       => $uri->getQuery(),
            'has_trailing_slash' => substr($uri->getPath(), -1) === '/',
        ];

        // Capture REQUEST_URI from server
        if (isset($_SERVER['REQUEST_URI'])) {
            $state['request_uri'] = $_SERVER['REQUEST_URI'];
        }

        // Capture original URL if different
        $original = Uri::getInstance('SERVER');
        if ($original->toString() !== $uri->toString()) {
            $state['original_url'] = $original->toString();
        }

        // Capture Apache mod_rewrite variables to detect .htaccess rewrites
        $apacheRewrite = $this->captureApacheRewriteInfo();
        if (!empty($apacheRewrite)) {
            $state['apache_rewrite'] = $apacheRewrite;
        }

        return $state;
    }

    /**
     * Capture Apache mod_rewrite information to detect .htaccess rewrites
     *
     * .htaccess internal rewrites happen BEFORE PHP runs, so we can only detect them
     * through special server variables that Apache sets after a rewrite.
     *
     * @return  array  Apache rewrite information (empty if no rewrite detected)
     */
    private function captureApacheRewriteInfo(): array
    {
        $info = [];

        // REDIRECT_URL: Set by Apache after an internal redirect/rewrite
        // This contains the URL BEFORE the rewrite happened
        if (!empty($_SERVER['REDIRECT_URL'])) {
            $info['redirect_url'] = $_SERVER['REDIRECT_URL'];
        }

        // REDIRECT_STATUS: HTTP status code of the internal redirect (usually 200)
        if (!empty($_SERVER['REDIRECT_STATUS'])) {
            $info['redirect_status'] = $_SERVER['REDIRECT_STATUS'];
        }

        // REDIRECT_QUERY_STRING: Original query string before rewrite
        if (isset($_SERVER['REDIRECT_QUERY_STRING'])) {
            $info['redirect_query_string'] = $_SERVER['REDIRECT_QUERY_STRING'];
        }

        // THE_REQUEST: The original HTTP request line (e.g., "GET /original-url HTTP/1.1")
        // This often contains the URL the client originally requested
        if (!empty($_SERVER['THE_REQUEST'])) {
            $info['the_request'] = $_SERVER['THE_REQUEST'];

            // Try to extract the original URL from THE_REQUEST
            if (preg_match('/^[A-Z]+\s+([^\s]+)\s+HTTP/i', $_SERVER['THE_REQUEST'], $matches)) {
                $info['original_request_uri'] = $matches[1];

                // Check if it differs from what PHP received
                if (isset($_SERVER['REQUEST_URI']) && $matches[1] !== $_SERVER['REQUEST_URI']) {
                    $info['rewrite_detected'] = true;
                    $info['rewrite_from'] = $matches[1];
                    $info['rewrite_to'] = $_SERVER['REQUEST_URI'];
                }
            }
        }

        // SCRIPT_URL / SCRIPT_URI: Sometimes set by Apache
        if (!empty($_SERVER['SCRIPT_URL'])) {
            $info['script_url'] = $_SERVER['SCRIPT_URL'];
        }
        if (!empty($_SERVER['SCRIPT_URI'])) {
            $info['script_uri'] = $_SERVER['SCRIPT_URI'];
        }

        // REQUEST_URI vs SCRIPT_NAME comparison
        // If REQUEST_URI differs from SCRIPT_NAME + PATH_INFO, rewriting may have occurred
        if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME'])) {
            $expectedUri = $_SERVER['SCRIPT_NAME'];
            if (!empty($_SERVER['PATH_INFO'])) {
                $expectedUri .= $_SERVER['PATH_INFO'];
            }
            if (!empty($_SERVER['QUERY_STRING'])) {
                $expectedUri .= '?' . $_SERVER['QUERY_STRING'];
            }

            // For Joomla with SEF, REQUEST_URI and SCRIPT_NAME will always differ
            // but we capture this for diagnostic purposes
            $info['script_name'] = $_SERVER['SCRIPT_NAME'];
            if (!empty($_SERVER['PATH_INFO'])) {
                $info['path_info'] = $_SERVER['PATH_INFO'];
            }
        }

        // Check for any REDIRECT_* variables (Apache sets these after rewrites)
        $redirectVars = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'REDIRECT_') === 0 && !empty($value)) {
                // Skip the ones we already captured
                if (!in_array($key, ['REDIRECT_URL', 'REDIRECT_STATUS', 'REDIRECT_QUERY_STRING'])) {
                    $redirectVars[$key] = $value;
                }
            }
        }
        if (!empty($redirectVars)) {
            $info['other_redirect_vars'] = $redirectVars;
        }

        return $info;
    }

    /**
     * Capture router state
     *
     * @return  array
     */
    private function captureRouterState(): array
    {
        $state = [];

        try {
            $app = $this->getApp();
            if (!$app) {
                return $state;
            }

            $router = $app->getRouter();

            if ($router) {
                $state['router_class'] = get_class($router);

                // getMode() only exists on SiteRouter, not AdministratorRouter
                if (method_exists($router, 'getMode')) {
                    $state['mode'] = $router->getMode();
                }

                // Get parsed vars
                if (method_exists($router, 'getVars')) {
                    $vars = $router->getVars();
                    if (!empty($vars)) {
                        $state['vars'] = $vars;
                    }
                }
            }
        } catch (\Exception $e) {
            $state['error'] = $e->getMessage();
        }

        return $state;
    }

    /**
     * Capture request headers
     *
     * @return  array
     */
    private function captureHeaders(): array
    {
        $headers = [];

        $relevantHeaders = [
            'HTTP_HOST',
            'HTTP_REFERER',
            'HTTP_USER_AGENT',
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED_PROTO',
            'HTTP_X_FORWARDED_HOST',
            'HTTPS',
            'REQUEST_METHOD',
            'SERVER_NAME',
            'SERVER_PORT',
        ];

        foreach ($relevantHeaders as $header) {
            if (isset($_SERVER[$header])) {
                $headers[$header] = $_SERVER[$header];
            }
        }

        return $headers;
    }

    /**
     * Track URL changes
     *
     * @param   string  $event  Event name
     *
     * @return  array|null  Change info if URL changed
     */
    private function trackUrlChange(string $event): ?array
    {
        $currentState = $this->captureUrlState();
        $currentUrl = $currentState['full_url'];

        if (empty($this->urlHistory)) {
            $this->urlHistory[] = [
                'event' => 'initial',
                'url'   => $currentUrl,
                'state' => $currentState,
            ];
            return null;
        }

        $lastEntry = end($this->urlHistory);
        $lastUrl = $lastEntry['url'];

        if ($currentUrl !== $lastUrl) {
            $change = [
                'previous_url'     => $lastUrl,
                'current_url'      => $currentUrl,
                'changed_event'    => $event,
                'previous_event'   => $lastEntry['event'],
                'trailing_slash_added'   => !$lastEntry['state']['has_trailing_slash'] && $currentState['has_trailing_slash'],
                'trailing_slash_removed' => $lastEntry['state']['has_trailing_slash'] && !$currentState['has_trailing_slash'],
            ];

            $this->urlHistory[] = [
                'event' => $event,
                'url'   => $currentUrl,
                'state' => $currentState,
            ];

            return $change;
        }

        return null;
    }

    /**
     * Event: onAfterInitialise
     *
     * First event after the application is initialized
     */
    public function onAfterInitialise(): void
    {
        $app = $this->getApp();

        $data = [
            'url'     => $this->captureUrlState(),
            'client'  => $app ? $app->getName() : 'unknown',
            'session' => [
                'id'    => session_id() ?: 'not_started',
                'name'  => session_name(),
            ],
        ];

        if ($this->getParam('log_headers', 1)) {
            $data['headers'] = $this->captureHeaders();
        }

        // Include SEF configuration and enabled system plugins (critical for debugging)
        $data['sef_config'] = $this->getSefConfiguration();
        $data['system_plugins'] = $this->getEnabledSystemPlugins();

        // Mark initial URL
        $this->trackUrlChange('onAfterInitialise');

        $this->log('onAfterInitialise', $data);
    }

    /**
     * Event: onAfterRoute
     *
     * After the application has routed the request
     */
    public function onAfterRoute(): void
    {
        $change = $this->trackUrlChange('onAfterRoute');
        $app = $this->getApp();
        $input = $app ? $app->getInput() : null;

        $data = [
            'url'    => $this->captureUrlState(),
            'router' => $this->captureRouterState(),
            'input'  => $input ? [
                'option' => $input->get('option'),
                'view'   => $input->get('view'),
                'layout' => $input->get('layout'),
                'id'     => $input->get('id'),
                'Itemid' => $input->get('Itemid'),
                'format' => $input->get('format', 'html'),
            ] : [],
        ];

        if ($change) {
            $data['url_change'] = $change;
        }

        $this->log('onAfterRoute', $data);
    }

    /**
     * Event: onParseRoute
     *
     * When the router parses the URL
     *
     * @param   \Joomla\Event\Event  $event  The event object
     */
    public function onParseRoute($event): void
    {
        $change = $this->trackUrlChange('onParseRoute');

        $data = [
            'url'    => $this->captureUrlState(),
            'router' => $this->captureRouterState(),
        ];

        if ($change) {
            $data['url_change'] = $change;
        }

        // Try to get router argument if available
        try {
            $arguments = $event->getArguments();
            if (!empty($arguments)) {
                $data['arguments'] = $this->sanitizeArguments($arguments);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        $this->log('onParseRoute', $data);
    }

    /**
     * Event: onBuildRoute
     *
     * When the router builds a URL
     *
     * @param   \Joomla\Event\Event  $event  The event object
     */
    public function onBuildRoute($event): void
    {
        $data = [
            'url' => $this->captureUrlState(),
        ];

        // Try to get router argument if available
        try {
            $arguments = $event->getArguments();
            if (!empty($arguments)) {
                $data['arguments'] = $this->sanitizeArguments($arguments);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        $this->log('onBuildRoute', $data);
    }

    /**
     * Event: onAfterRouterParse
     *
     * Custom event that some SEF extensions fire
     *
     * @param   \Joomla\Event\Event  $event  The event object
     */
    public function onAfterRouterParse($event): void
    {
        $change = $this->trackUrlChange('onAfterRouterParse');

        $data = [
            'url'    => $this->captureUrlState(),
            'router' => $this->captureRouterState(),
        ];

        if ($change) {
            $data['url_change'] = $change;
        }

        $this->log('onAfterRouterParse', $data);
    }

    /**
     * Event: onAfterDispatch
     *
     * After the component has been dispatched
     */
    public function onAfterDispatch(): void
    {
        $change = $this->trackUrlChange('onAfterDispatch');
        $app = $this->getApp();
        $input = $app ? $app->getInput() : null;

        $data = [
            'url'   => $this->captureUrlState(),
            'input' => $input ? [
                'option' => $input->get('option'),
                'view'   => $input->get('view'),
                'id'     => $input->get('id'),
            ] : [],
        ];

        if ($change) {
            $data['url_change'] = $change;
        }

        $this->log('onAfterDispatch', $data);
    }

    /**
     * Event: onBeforeRender
     *
     * Before the application renders
     */
    public function onBeforeRender(): void
    {
        $change = $this->trackUrlChange('onBeforeRender');

        $data = [
            'url' => $this->captureUrlState(),
        ];

        if ($change) {
            $data['url_change'] = $change;
        }

        $this->log('onBeforeRender', $data);
    }

    /**
     * Event: onBeforeCompileHead
     *
     * Before the document head is compiled
     */
    public function onBeforeCompileHead(): void
    {
        $change = $this->trackUrlChange('onBeforeCompileHead');

        $data = [
            'url' => $this->captureUrlState(),
        ];

        // Capture document metadata that might affect canonical URLs
        try {
            $app = $this->getApp();
            $doc = $app ? $app->getDocument() : null;
            if ($doc && method_exists($doc, 'getHeadData')) {
                $headData = $doc->getHeadData();

                // Look for canonical and redirect meta
                if (isset($headData['links'])) {
                    $data['links'] = $headData['links'];
                }

                // Check for meta refresh (redirect)
                if (isset($headData['custom'])) {
                    foreach ($headData['custom'] as $custom) {
                        if (stripos($custom, 'refresh') !== false) {
                            $data['meta_refresh'] = $custom;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        if ($change) {
            $data['url_change'] = $change;
        }

        $this->log('onBeforeCompileHead', $data);
    }

    /**
     * Event: onAfterRender
     *
     * After the application has rendered
     */
    public function onAfterRender(): void
    {
        if (!$this->getParam('log_redirects', 1)) {
            return;
        }

        $change = $this->trackUrlChange('onAfterRender');

        // Check if a redirect is pending
        $headers = headers_list();
        $redirectHeader = null;

        foreach ($headers as $header) {
            if (stripos($header, 'Location:') === 0) {
                $redirectHeader = trim(substr($header, 9));
                break;
            }
        }

        $data = [
            'url' => $this->captureUrlState(),
        ];

        if ($redirectHeader) {
            $data['redirect_detected'] = [
                'location'     => $redirectHeader,
                'http_code'    => http_response_code(),
                'analysis'     => $this->analyzeRedirect($redirectHeader),
            ];
        }

        if ($change) {
            $data['url_change'] = $change;
        }

        // Scan response body for meta refresh
        try {
            $app = $this->getApp();
            $body = $app ? $app->getBody() : null;
            if ($body && preg_match('/<meta[^>]*http-equiv=["\']refresh["\'][^>]*content=["\'](\d+);?\s*url=([^"\']+)["\'][^>]*>/i', $body, $matches)) {
                $data['meta_refresh_detected'] = [
                    'delay' => (int) $matches[1],
                    'url'   => $matches[2],
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        $this->log('onAfterRender', $data);
    }

    /**
     * Event: onBeforeRespond
     *
     * Before the response is sent
     */
    public function onBeforeRespond(): void
    {
        $change = $this->trackUrlChange('onBeforeRespond');

        $data = [
            'url'        => $this->captureUrlState(),
            'http_code'  => http_response_code(),
        ];

        // Check all headers for redirect info
        $headers = headers_list();
        $relevantHeaders = [];

        foreach ($headers as $header) {
            $lowerHeader = strtolower($header);
            if (
                strpos($lowerHeader, 'location') === 0 ||
                strpos($lowerHeader, 'x-redirect') === 0 ||
                strpos($lowerHeader, 'link') === 0
            ) {
                $relevantHeaders[] = $header;
            }
        }

        if (!empty($relevantHeaders)) {
            $data['redirect_headers'] = $relevantHeaders;
        }

        if ($change) {
            $data['url_change'] = $change;
        }

        $this->log('onBeforeRespond', $data);
    }

    /**
     * Event: onAfterRespond
     *
     * After the response is sent
     */
    public function onAfterRespond(): void
    {
        // Log final summary
        $data = [
            'total_elapsed_ms' => round((microtime(true) - $this->startTime) * 1000, 2),
            'url_history'      => $this->urlHistory,
            'total_changes'    => count($this->urlHistory) - 1,
        ];

        // Detect potential loop pattern
        if (count($this->urlHistory) > 1) {
            $data['loop_analysis'] = $this->analyzeForLoop();
        }

        $this->log('onAfterRespond', $data);
    }

    /**
     * Event: onError
     *
     * When an error occurs
     *
     * @param   \Joomla\Event\Event  $event  The event object
     */
    public function onError($event): void
    {
        $data = [
            'url'   => $this->captureUrlState(),
            'error' => [],
        ];

        try {
            $error = $event->getError();
            if ($error instanceof \Throwable) {
                $data['error'] = [
                    'message' => $error->getMessage(),
                    'code'    => $error->getCode(),
                    'file'    => str_replace(JPATH_ROOT . '/', '', $error->getFile()),
                    'line'    => $error->getLine(),
                ];
            }
        } catch (\Exception $e) {
            $data['error']['capture_failed'] = $e->getMessage();
        }

        $this->log('onError', $data, true); // Force log errors
    }

    /**
     * Shutdown handler - catch final state or fatal errors
     */
    public function onShutdown(): void
    {
        $this->inShutdown = true;

        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $data = [
                'fatal_error' => [
                    'type'    => $error['type'],
                    'message' => $error['message'],
                    'file'    => str_replace(JPATH_ROOT . '/', '', $error['file']),
                    'line'    => $error['line'],
                ],
                'url_history' => $this->urlHistory,
            ];

            $this->log('FATAL_ERROR', $data, true);
        }
    }

    /**
     * Analyze a redirect URL for potential issues
     *
     * @param   string  $redirectUrl  The redirect target URL
     *
     * @return  array
     */
    private function analyzeRedirect(string $redirectUrl): array
    {
        $currentUrl = Uri::getInstance()->toString();
        $analysis = [];

        // Check if only trailing slash differs
        $currentNormalized = rtrim($currentUrl, '/');
        $redirectNormalized = rtrim($redirectUrl, '/');

        if ($currentNormalized === $redirectNormalized) {
            $analysis['trailing_slash_redirect'] = true;
            $analysis['current_has_slash'] = substr($currentUrl, -1) === '/';
            $analysis['redirect_has_slash'] = substr($redirectUrl, -1) === '/';
            $analysis['warning'] = 'POTENTIAL LOOP: Only trailing slash differs!';
        }

        // Check for same host
        $currentUri = Uri::getInstance($currentUrl);
        $redirectUri = Uri::getInstance($redirectUrl);

        $analysis['same_host'] = $currentUri->getHost() === $redirectUri->getHost();
        $analysis['same_path'] = $currentUri->getPath() === $redirectUri->getPath();

        return $analysis;
    }

    /**
     * Analyze URL history for loop patterns
     *
     * @return  array
     */
    private function analyzeForLoop(): array
    {
        $analysis = [
            'potential_loop' => false,
            'pattern'        => null,
        ];

        $urls = array_column($this->urlHistory, 'url');
        $uniqueUrls = array_unique($urls);

        // If we have repeated URLs, there might be a loop
        if (count($urls) !== count($uniqueUrls)) {
            $analysis['potential_loop'] = true;
            $analysis['repeated_urls'] = array_count_values($urls);
        }

        // Check for trailing slash toggle pattern
        $paths = [];
        foreach ($this->urlHistory as $entry) {
            $paths[] = [
                'path'  => $entry['state']['path'] ?? '',
                'slash' => $entry['state']['has_trailing_slash'] ?? false,
            ];
        }

        // Look for alternating slash pattern
        $slashPattern = array_column($paths, 'slash');
        if (count($slashPattern) >= 2) {
            $alternating = true;
            for ($i = 1; $i < count($slashPattern); $i++) {
                if ($slashPattern[$i] === $slashPattern[$i - 1]) {
                    $alternating = false;
                    break;
                }
            }

            if ($alternating && count($slashPattern) > 2) {
                $analysis['potential_loop'] = true;
                $analysis['pattern'] = 'TRAILING_SLASH_TOGGLE';
                $analysis['warning'] = 'Detected alternating trailing slash pattern - likely cause of redirect loop!';
            }
        }

        return $analysis;
    }

    /**
     * Sanitize event arguments for logging
     *
     * @param   array  $arguments  Raw arguments
     *
     * @return  array
     */
    private function sanitizeArguments(array $arguments): array
    {
        $sanitized = [];

        foreach ($arguments as $key => $value) {
            if (is_object($value)) {
                $sanitized[$key] = get_class($value);

                // Extract useful info from Uri objects
                if ($value instanceof Uri) {
                    $sanitized[$key . '_url'] = $value->toString();
                }
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArguments($value);
            } elseif (is_string($value) || is_numeric($value) || is_bool($value)) {
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = gettype($value);
            }
        }

        return $sanitized;
    }

    /**
     * AJAX handler for the plugin
     * Called via index.php?option=com_ajax&plugin=routertracer&group=system&format=raw
     *
     * @param   Event  $event  The event object
     *
     * @return  void
     */
    public function onAjaxRoutertracer(Event $event): void
    {
        $app = $this->getApp();

        // Only allow in administrator
        if (!$app || !$app->isClient('administrator')) {
            $this->setAjaxResult($event, json_encode(['error' => 'Access denied']));
            return;
        }

        // Check user permissions
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage', 'com_plugins')) {
            $this->setAjaxResult($event, json_encode(['error' => 'Access denied - insufficient permissions']));
            return;
        }

        // Validate token
        if (!Session::checkToken('get') && !Session::checkToken('post')) {
            $this->setAjaxResult($event, json_encode(['error' => 'Invalid security token']));
            return;
        }

        $action = $app->getInput()->get('action', 'view', 'cmd');

        switch ($action) {
            case 'view':
                $result = $this->ajaxViewLog();
                break;

            case 'clear':
                $result = $this->ajaxClearLog();
                break;

            case 'download':
                $result = $this->ajaxDownloadLog();
                break;

            case 'stats':
                $result = $this->ajaxGetStats();
                break;

            case 'viewer':
                $result = $this->ajaxRenderViewer();
                break;

            case 'test':
                $result = $this->ajaxTestLogging();
                break;

            default:
                $result = json_encode(['error' => 'Unknown action']);
        }

        $this->setAjaxResult($event, $result);
    }

    /**
     * Set the AJAX result on the event object
     *
     * @param   Event   $event   The event object
     * @param   string  $result  The result to set
     *
     * @return  void
     */
    private function setAjaxResult(Event $event, string $result): void
    {
        // For Joomla's com_ajax, results are collected via the event
        if (method_exists($event, 'addResult')) {
            $event->addResult($result);
        } else {
            // Fallback for older Joomla versions - set result argument
            $results = $event->getArgument('result', []);
            $results[] = $result;
            $event->setArgument('result', $results);
        }
    }

    /**
     * AJAX: Test logging functionality and report diagnostics
     *
     * @return  string  JSON response
     */
    private function ajaxTestLogging(): string
    {
        $diagnostics = [
            'success'       => false,
            'log_file'      => $this->getLogFile(),
            'log_dir'       => dirname($this->getLogFile()),
            'dir_exists'    => false,
            'dir_writable'  => false,
            'file_exists'   => false,
            'file_writable' => false,
            'params_loaded' => false,
            'app_available' => false,
            'write_test'    => false,
            'errors'        => [],
        ];

        // Check params
        $diagnostics['params_loaded'] = ($this->params !== null);
        $diagnostics['params_class'] = $this->params ? get_class($this->params) : 'null';

        // Check app
        $app = $this->getApp();
        $diagnostics['app_available'] = ($app !== null);
        $diagnostics['app_client'] = $app ? $app->getName() : 'null';

        // Check directory
        $logDir = dirname($this->getLogFile());
        $diagnostics['dir_exists'] = is_dir($logDir);

        if (!$diagnostics['dir_exists']) {
            // Try to create it
            $created = @mkdir($logDir, 0755, true);
            $diagnostics['dir_created'] = $created;
            $diagnostics['dir_exists'] = is_dir($logDir);

            if (!$created) {
                $diagnostics['errors'][] = 'Failed to create logs directory: ' . $logDir;
            }
        }

        if ($diagnostics['dir_exists']) {
            $diagnostics['dir_writable'] = is_writable($logDir);

            if (!$diagnostics['dir_writable']) {
                $diagnostics['errors'][] = 'Logs directory is not writable: ' . $logDir;
            }
        }

        // Check file
        $logFile = $this->getLogFile();
        $diagnostics['file_exists'] = file_exists($logFile);

        if ($diagnostics['file_exists']) {
            $diagnostics['file_writable'] = is_writable($logFile);
            $diagnostics['file_size'] = filesize($logFile);
        }

        // Try to write a test entry
        if ($diagnostics['dir_writable'] || $diagnostics['file_writable']) {
            $testEntry = [
                'timestamp'  => date('Y-m-d H:i:s'),
                'request_id' => 'TEST_' . $this->requestId,
                'event'      => 'TEST_WRITE',
                'data'       => [
                    'message' => 'Test log entry from diagnostic check',
                    'time'    => time(),
                ],
            ];

            $testLine = json_encode($testEntry, JSON_UNESCAPED_SLASHES) . "\n";
            $result = @file_put_contents($logFile, $testLine, FILE_APPEND | LOCK_EX);

            $diagnostics['write_test'] = ($result !== false);
            $diagnostics['bytes_written'] = $result;

            if ($result === false) {
                $diagnostics['errors'][] = 'Failed to write to log file';
                $lastError = error_get_last();
                if ($lastError) {
                    $diagnostics['errors'][] = $lastError['message'];
                }
            }
        }

        // Check shouldLog result
        $diagnostics['should_log_frontend'] = $this->getParam('filter_frontend', 1);
        $diagnostics['should_log_backend'] = $this->getParam('filter_backend', 0);

        $diagnostics['success'] = $diagnostics['write_test'];

        return json_encode($diagnostics, JSON_PRETTY_PRINT);
    }

    /**
     * AJAX: View log contents
     *
     * @return  string  JSON response
     */
    private function ajaxViewLog(): string
    {
        $logFile = $this->getLogFile();
        $app = $this->getApp();
        $input = $app ? $app->getInput() : null;

        $lines = $input ? (int) $input->get('lines', 100, 'int') : 100;
        $offset = $input ? (int) $input->get('offset', 0, 'int') : 0;
        $requestId = $input ? $input->get('request_id', '', 'string') : '';

        if (!file_exists($logFile)) {
            return json_encode([
                'success' => true,
                'entries' => [],
                'total'   => 0,
                'message' => 'Log file does not exist yet',
            ]);
        }

        $content = file_get_contents($logFile);
        $allLines = array_filter(explode("\n", $content));
        $entries = [];

        foreach ($allLines as $line) {
            $entry = json_decode($line, true);
            if ($entry) {
                // Filter by request ID if specified
                if (!empty($requestId) && isset($entry['request_id']) && $entry['request_id'] !== $requestId) {
                    continue;
                }
                $entries[] = $entry;
            }
        }

        // Sort by timestamp descending (newest first)
        usort($entries, function ($a, $b) {
            return strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? '');
        });

        $total = count($entries);

        // Apply pagination
        if ($offset > 0 || $lines > 0) {
            $entries = array_slice($entries, $offset, $lines);
        }

        return json_encode([
            'success' => true,
            'entries' => $entries,
            'total'   => $total,
            'offset'  => $offset,
            'limit'   => $lines,
        ]);
    }

    /**
     * AJAX: Clear the log file
     *
     * @return  string  JSON response
     */
    private function ajaxClearLog(): string
    {
        $logFile = $this->getLogFile();

        if (file_exists($logFile)) {
            // Archive before clearing
            $archiveFile = $logFile . '.cleared.' . date('Y-m-d-His');
            copy($logFile, $archiveFile);

            // Clear the file
            file_put_contents($logFile, '');
        }

        return json_encode([
            'success' => true,
            'message' => 'Log file cleared successfully',
        ]);
    }

    /**
     * AJAX: Download log file
     *
     * @return  string  Raw log content for download
     */
    private function ajaxDownloadLog(): string
    {
        $logFile = $this->getLogFile();

        if (!file_exists($logFile)) {
            return '';
        }

        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="router_trace_' . date('Y-m-d_His') . '.log"');
        header('Content-Length: ' . filesize($logFile));

        return file_get_contents($logFile);
    }

    /**
     * AJAX: Get log statistics
     *
     * @return  string  JSON response
     */
    private function ajaxGetStats(): string
    {
        $logFile = $this->getLogFile();

        $stats = [
            'file_exists'    => false,
            'file_size'      => 0,
            'file_size_human' => '0 B',
            'entry_count'    => 0,
            'request_count'  => 0,
            'events'         => [],
            'warnings'       => 0,
            'errors'         => 0,
            'potential_loops' => 0,
        ];

        if (!file_exists($logFile)) {
            return json_encode(['success' => true, 'stats' => $stats]);
        }

        $stats['file_exists'] = true;
        $stats['file_size'] = filesize($logFile);
        $stats['file_size_human'] = $this->formatBytes($stats['file_size']);

        $content = file_get_contents($logFile);
        $lines = array_filter(explode("\n", $content));
        $stats['entry_count'] = count($lines);

        $requestIds = [];
        $events = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry) {
                continue;
            }

            if (isset($entry['request_id'])) {
                $requestIds[$entry['request_id']] = true;
            }

            if (isset($entry['event'])) {
                $events[$entry['event']] = ($events[$entry['event']] ?? 0) + 1;

                if ($entry['event'] === 'onError' || $entry['event'] === 'FATAL_ERROR') {
                    $stats['errors']++;
                }
            }

            // Check for warnings/loops
            if (isset($entry['data']['redirect_detected']['analysis']['warning'])) {
                $stats['warnings']++;
            }

            if (isset($entry['data']['loop_analysis']['potential_loop']) && $entry['data']['loop_analysis']['potential_loop']) {
                $stats['potential_loops']++;
            }
        }

        $stats['request_count'] = count($requestIds);
        $stats['events'] = $events;

        return json_encode(['success' => true, 'stats' => $stats]);
    }

    /**
     * AJAX: Render the log viewer HTML
     *
     * @return  string  HTML content
     */
    private function ajaxRenderViewer(): string
    {
        $token = Session::getFormToken();
        $ajaxUrl = Uri::base() . 'index.php?option=com_ajax&plugin=routertracer&group=system&format=raw&' . $token . '=1';

        ob_start();
        include __DIR__ . '/../../tmpl/viewer.php';
        return ob_get_clean();
    }

    /**
     * Format bytes to human readable
     *
     * @param   int  $bytes  Bytes
     *
     * @return  string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
