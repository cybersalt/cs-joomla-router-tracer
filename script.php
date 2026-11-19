<?php

/**
 * @package     Cybersalt.Plugin.System.RouterTracer
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

return new class () implements InstallerScriptInterface {

    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'install' || $type === 'update') {
            $this->showPostInstallMessage($type);
            $this->removeOldPublicLogs();
        }

        return true;
    }

    /**
     * Up to 1.3.0 the log was written to <site root>/logs/, which the web server can serve.
     * Logs now go to Joomla's log folder as guarded .php files, so remove the old ones.
     * Only files that are empty or start like a Router Tracer entry are touched.
     */
    private function removeOldPublicLogs(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('routertracer'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));

        $params = json_decode((string) $db->setQuery($query)->loadResult(), true) ?: [];
        $name   = basename(str_replace('\\', '/', (string) ($params['log_file'] ?? '')));
        $name   = $name !== '' ? $name : 'router_trace.log';
        $dir    = JPATH_ROOT . '/logs';

        if (!is_dir($dir)) {
            return;
        }

        $removed = 0;
        $failed  = [];

        foreach (scandir($dir) ?: [] as $file) {
            if ($file !== $name && strpos($file, $name . '.') !== 0) {
                continue;
            }

            $path = $dir . '/' . $file;
            $head = is_file($path) ? (string) @file_get_contents($path, false, null, 0, 14) : null;

            if ($head === null || ($head !== '' && $head !== '{"timestamp":"')) {
                continue;
            }

            if (@unlink($path)) {
                $removed++;
            } else {
                $failed[] = $path;
            }
        }

        $app = Factory::getApplication();

        if ($removed) {
            $app->enqueueMessage(Text::sprintf('PLG_SYSTEM_ROUTERTRACER_POSTINSTALL_OLD_LOGS_REMOVED', $removed, $dir), 'message');
        }

        if ($failed) {
            $app->enqueueMessage(Text::sprintf('PLG_SYSTEM_ROUTERTRACER_POSTINSTALL_OLD_LOGS_FAILED', implode(', ', $failed)), 'warning');
        }
    }

    private function showPostInstallMessage(string $type): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('routertracer'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));

        $db->setQuery($query);
        $extensionId = $db->loadResult();

        if (!$extensionId) {
            return;
        }

        $url = 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $extensionId;

        $messageKey = $type === 'update'
            ? 'PLG_SYSTEM_ROUTERTRACER_POSTINSTALL_UPDATED'
            : 'PLG_SYSTEM_ROUTERTRACER_POSTINSTALL_INSTALLED';

        echo '<div class="card mb-3" style="margin: 20px 0;">'
            . '<div class="card-body">'
            . '<h3 class="card-title">' . Text::_('PLG_SYSTEM_ROUTERTRACER') . '</h3>'
            . '<p class="card-text">' . Text::_($messageKey) . '</p>'
            . '<a href="' . $url . '" class="btn btn-primary text-white">'
            . Text::_('PLG_SYSTEM_ROUTERTRACER_POSTINSTALL_OPEN')
            . '</a></div></div>';
    }
};
