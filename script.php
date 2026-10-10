<?php

/**
 * @package     TLWeb.Module
 * @subpackage  mod_prettyslider
 *
 * @copyright   Copyright (C) 2024 TLWebdesign. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Installer script for mod_prettyslider
 *
 * @since  1.0.0
 */
return new class () implements InstallerScriptInterface {
    /**
     * Minimum Joomla version required to install the module.
     *
     * @since  1.0.0
     */
    private const MINIMUM_JOOMLA = '5.4.0';

    /**
     * Minimum PHP version required to install the module.
     *
     * @since  1.0.0
     */
    private const MINIMUM_PHP = '8.1.0';

    /**
     * Function called after the extension is installed.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function install(InstallerAdapter $adapter): bool
    {
        echo Text::_('MOD_PRETTYSLIDER_INSTALLERSCRIPT_INSTALL');

        return true;
    }

    /**
     * Function called after the extension is updated.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function update(InstallerAdapter $adapter): bool
    {
        echo Text::_('MOD_PRETTYSLIDER_INSTALLERSCRIPT_UPDATE');

        return true;
    }

    /**
     * Function called after the extension is uninstalled.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        echo Text::_('MOD_PRETTYSLIDER_INSTALLERSCRIPT_UNINSTALL');

        return true;
    }

    /**
     * Function called before the extension is installed, updated or removed.
     *
     * @param   string            $type     The type of change (install, update, discover_install or uninstall)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        if (version_compare(PHP_VERSION, self::MINIMUM_PHP, '<')) {
            Log::add(Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', self::MINIMUM_PHP), Log::WARNING, 'jerror');

            return false;
        }

        if (version_compare(JVERSION, self::MINIMUM_JOOMLA, '<')) {
            Log::add(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', self::MINIMUM_JOOMLA), Log::WARNING, 'jerror');

            return false;
        }

        return true;
    }

    /**
     * Function called after the extension is installed, updated or removed.
     *
     * @param   string            $type     The type of change (install, update, discover_install or uninstall)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }
};
