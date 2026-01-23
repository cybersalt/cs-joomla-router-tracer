<?php

/**
 * @package     CyberSalt.Plugin
 * @subpackage  System.RouterTracer
 *
 * @copyright   Copyright (C) 2026 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use CyberSalt\Plugin\System\RouterTracer\Extension\RouterTracer;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new RouterTracer(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'routertracer')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
