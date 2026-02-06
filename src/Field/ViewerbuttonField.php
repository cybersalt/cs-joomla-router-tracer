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

namespace Cybersalt\Plugin\System\RouterTracer\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/**
 * Custom field to display button links to the log viewer
 */
class ViewerbuttonField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Viewerbutton';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $token = Session::getFormToken();

        // URL to open the viewer in a new window
        $viewerUrl = Uri::base() . 'index.php?option=com_ajax&plugin=routertracer&group=system&format=raw&action=viewer&' . $token . '=1';

        // URL to download the log
        $downloadUrl = Uri::base() . 'index.php?option=com_ajax&plugin=routertracer&group=system&format=raw&action=download&' . $token . '=1';

        // URL to clear the log
        $clearUrl = Uri::base() . 'index.php?option=com_ajax&plugin=routertracer&group=system&format=raw&action=clear&' . $token . '=1';

        $html = '<div style="display: flex; gap: 10px; flex-wrap: wrap;">';

        // View Log button
        $html .= '<a href="' . $viewerUrl . '" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">';
        $html .= '<span class="icon-eye" aria-hidden="true"></span>';
        $html .= Text::_('PLG_SYSTEM_ROUTERTRACER_VIEW_LOG');
        $html .= '</a>';

        // Download Log button
        $html .= '<a href="' . $downloadUrl . '" class="btn btn-success" style="display: inline-flex; align-items: center; gap: 6px;">';
        $html .= '<span class="icon-download" aria-hidden="true"></span>';
        $html .= Text::_('PLG_SYSTEM_ROUTERTRACER_DOWNLOAD_LOG');
        $html .= '</a>';

        // Clear Log button (uses JavaScript to stay on page)
        $html .= '<button type="button" class="btn btn-danger" style="display: inline-flex; align-items: center; gap: 6px;" onclick="clearRouterTracerLog(\'' . $clearUrl . '\', \'' . Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_CONFIRM', true) . '\')">';
        $html .= '<span class="icon-trash" aria-hidden="true"></span>';
        $html .= Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_LOG');
        $html .= '</button>';

        // JavaScript for clearing log
        $html .= '<script>
        function clearRouterTracerLog(url, confirmMsg) {
            if (!confirm(confirmMsg)) return;
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert("' . Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_SUCCESS', true) . '");
                    } else {
                        alert("' . Text::_('PLG_SYSTEM_ROUTERTRACER_ERROR_PREFIX', true) . '" + (data.error || "' . Text::_('PLG_SYSTEM_ROUTERTRACER_ERROR_UNKNOWN', true) . '"));
                    }
                })
                .catch(err => alert("' . Text::_('PLG_SYSTEM_ROUTERTRACER_CLEAR_FETCH_FAILED', true) . '" + err.message));
        }
        </script>';

        $html .= '</div>';

        // Add info text
        $html .= '<div class="small text-muted" style="margin-top: 8px;">';
        $html .= Text::_('PLG_SYSTEM_ROUTERTRACER_VIEWER_INFO');
        $html .= '</div>';

        return $html;
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     */
    protected function getLabel(): string
    {
        return '<label class="form-label">' . Text::_('PLG_SYSTEM_ROUTERTRACER_LOG_VIEWER_LABEL') . '</label>';
    }
}
