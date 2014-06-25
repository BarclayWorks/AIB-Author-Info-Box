<?php
/**
 * @Copyright
 * @package     AIB - Author Info Box
 * @author      Viktor Vogel {@link http://www.kubik-rubik.de}
 * @version     2.5-8 - 2014-01-03
 * @link        http://joomla-extensions.kubik-rubik.de/aib-author-info-box Project Site
 *
 * @license GNU/GPL
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

class PlgUserAibProfile extends JPlugin
{
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    /**
     * Gets the data of the AIB profile plugin for valid form requests
     *
     * @param string $context
     * @param JObject $data
     * @return boolean
     */
    function onContentPrepareData($context, $data)
    {
        if(!in_array($context, array('com_users.user', 'com_admin.profile', 'com_users.profile')))
        {
            return true;
        }

        if(is_object($data))
        {
            if(empty($data->aibprofile) AND $data->id > 0)
            {
                $db = JFactory::getDbo();
                $db->setQuery('SELECT profile_key, profile_value FROM #__user_aibprofiles WHERE user_id = '.(int)$data->id.' AND profile_key LIKE "aibprofile.%" ORDER BY ordering');
                $result = $db->loadRowList();

                if($db->getErrorNum())
                {
                    $this->_subject->setError($db->getErrorMsg());
                    return false;
                }

                $data->aibprofile = array();

                foreach($result as $value)
                {
                    $value_clean = str_replace('aibprofile.', '', $value[0]);
                    $data->aibprofile[$value_clean] = json_decode($value[1], true);

                    // If JSON decode was not executed correctly, set the not decoded value
                    if($data->aibprofile[$value_clean] === null)
                    {
                        $data->aibprofile[$value_clean] = $value[1];
                    }
                }
            }
        }

        return true;
    }

    /**
     * Loads the input fields in valid forms (due to security reason only backend forms are selected)
     *
     * @param JForm $form
     * @param type $data
     * @return boolean
     */
    function onContentPrepareForm($form, $data)
    {
        if(!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        if(!in_array($form->getName(), array('com_users.user', 'com_admin.profile')))
        {
            return true;
        }

        JForm::addFormPath(dirname(__FILE__).'/profiles');
        $form->loadFile('aibprofile', false);

        return true;
    }

    /**
     * Saves the input data of the AIB profile plugin
     *
     * @param array $data
     * @param boolean $isNew
     * @param boolean $result
     * @param boolean $error
     * @return boolean
     * @throws Exception
     */
    function onUserAfterSave($data, $isNew, $result, $error)
    {
        $user_id = JArrayHelper::getValue($data, 'id', 0, 'int');

        if(!empty($user_id) AND !empty($data['aibprofile']) AND $result == true)
        {
            try
            {
                $db = JFactory::getDbo();
                $db->setQuery('DELETE FROM #__user_aibprofiles WHERE user_id = '.$user_id.' AND profile_key LIKE "aibprofile.%"');

                if(!$db->query())
                {
                    throw new Exception($db->getErrorMsg());
                }

                $tuples = array();
                $count = 1;

                foreach($data['aibprofile'] as $key => $value)
                {
                    $tuples[] = '('.$user_id.', '.$db->quote('aibprofile.'.$key).', '.$db->quote(json_encode($value)).', '.$count++.')';
                }

                $db->setQuery('INSERT INTO #__user_aibprofiles VALUES '.implode(', ', $tuples));

                if(!$db->query())
                {
                    throw new Exception($db->getErrorMsg());
                }
            }
            catch(JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Removes all user profile information for the given user ID
     *
     * @param array $user
     * @param boolean $success
     * @param string $msg
     * @return boolean
     * @throws Exception
     */
    function onUserAfterDelete($user, $success, $msg)
    {
        if(empty($success))
        {
            return false;
        }

        $user_id = JArrayHelper::getValue($user, 'id', 0, 'int');

        if(!empty($user_id))
        {
            try
            {
                $db = JFactory::getDbo();
                $db->setQuery('DELETE FROM #__user_aibprofiles WHERE user_id = '.$user_id.' AND profile_key LIKE "aibprofile.%"');

                if(!$db->query())
                {
                    throw new Exception($db->getErrorMsg());
                }
            }
            catch(JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }

}
