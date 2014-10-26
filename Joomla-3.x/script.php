<?php
/**
 * @Copyright
 * @package     AIB - Author Info Box
 * @author      Viktor Vogel {@link http://www.kubik-rubik.de}
 * @version     3-2 - 2014-01-26
 * @link        http://joomla-extensions.kubik-rubik.de/aib-author-info-box
 *
 * @license     GNU/GPL
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die('Restricted access');

class Pkg_AuthorInfoBoxInstallerScript
{
    function install($parent)
    {
        // Not needed at the moment
    }

    function uninstall($parent)
    {
        $db = JFactory::getDbo();

        // Remove table of AIB user profile plugin from the database
        $db->setQuery("DROP TABLE ".$db->quoteName('#__user_aibprofiles')."");
        $db->execute();
    }

    function update($parent)
    {
        // Not needed at the moment
    }

    function postflight($type, $parent)
    {
        $db = JFactory::getDbo();

        // Enable the content pluign
        $db->setQuery("UPDATE ".$db->quoteName('#__extensions')." SET ".$db->quoteName('enabled')." = 1 WHERE ".$db->quoteName('element')." = 'authorinfobox' AND ".$db->quoteName('type')." = 'plugin'");
        $db->execute();

        // Enable the user profile pluign
        $db->setQuery("UPDATE ".$db->quoteName('#__extensions')." SET ".$db->quoteName('enabled')." = 1 WHERE ".$db->quoteName('element')." = 'aibprofile' AND ".$db->quoteName('type')." = 'plugin'");
        $db->execute();

        // Get sure that the needed table for the user profile plugin exists - table should already be added by the plugin itself
        $db->setQuery("CREATE TABLE IF NOT EXISTS ".$db->quoteName('#__user_aibprofiles')." (".$db->quoteName('user_id')." int(11) NOT NULL, ".$db->quoteName('profile_key')." varchar(100) NOT NULL, ".$db->quoteName('profile_value')." text NOT NULL, ".$db->quoteName('ordering')." int(11) NOT NULL DEFAULT '0', UNIQUE KEY ".$db->quoteName('idx_user_id_profile_key')." (".$db->quoteName('user_id').",".$db->quoteName('profile_key').")) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $db->execute();
    }
}
