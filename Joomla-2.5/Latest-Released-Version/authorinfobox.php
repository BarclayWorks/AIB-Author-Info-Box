<?php
/**
 * @Copyright
 * @package     AIB - Author Info Box
 * @author      Viktor Vogel {@link http://www.kubik-rubik.de}
 * @version     2.5-7 - 2013-05-22
 * @link        Project Site {@link http://joomla-extensions.kubik-rubik.de/aib-author-info-box}
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

class plgContentAuthorInfobox extends JPlugin
{
    protected $_article_view;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();

        if(JFactory::getApplication()->input->getWord('view') == 'article')
        {
            $this->set('_article_view', true);
        }
        else
        {
            $this->set('_article_view', false);
        }
    }

    /**
     * Use trigger onContentPrepare instead of onContentBeforeDisplay and onContentAfterDisplay to avoid sorting problems
     * with other plugins which use this (wrong!) trigger. Actually this trigger should only be used to manipulate the output
     * and not to add data to the output! (changed since version 2.5-6)
     *
     * @param string $context
     * @param object $row
     * @param string $params
     * @param integer $page
     */
    function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        // Only execute plugin with this trigger in article view and the call is from the component com_content
        if($this->_article_view == true AND $context == 'com_content.article')
        {
            if($this->params->get('position') == 0)
            {
                $this->renderAuthorInfoBox($row, 'before');
            }
            else
            {
                $this->renderAuthorInfoBox($row, 'after');
            }
        }
    }

    /**
     * onContentBeforeDisplay is still needed because the trigger onContentPrepare does not contain all needed data
     * if it is called outside the article view (e.g. featured or blog view). This data is always transmitted with the trigger
     * onContentBeforeDisplay. This is another disadvantage to use the trigger onContentPrepare!  (changed since
     * version 2.5-6)
     *
     * @param string $context
     * @param object $row
     * @param string $params
     * @param integer $page
     */
    public function onContentBeforeDisplay($context, &$row, &$params, $page = 0)
    {
        // Do not execute plugin with this trigger in article view
        if($this->_article_view == false)
        {
            if($this->params->get('position') == 0)
            {
                $this->renderAuthorInfoBox($row, 'before');
            }
        }
    }

    /**
     * onContentAfterDisplay is still needed because the trigger onContentPrepare does not contain all needed data
     * if it is called outside the article view (e.g. blog view). This data is always transmitted with the trigger
     * onContentAfterDisplay. This is another disadvantage to use the trigger onContentPrepare.  (changed since
     * version 2.5-6)
     *
     * @param string $context
     * @param object $row
     * @param string $params
     * @param integer $page
     */
    public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
    {
        // Do not execute plugin with this trigger in article view
        if($this->_article_view == false)
        {
            if($this->params->get('position') == 1)
            {
                $this->renderAuthorInfoBox($row, 'after');
            }
        }
    }

    /**
     * Render the author info box
     *
     * @param array $row
     * @param string $position
     * @return
     */
    public function renderAuthorInfoBox(&$row, $position = 'after')
    {
        // Do not execute if article view option is activated and this view is not loading
        if($this->params->get('articleview') AND $this->_article_view == false)
        {
            return;
        }

        $exclude_article = $this->excludeArticles($row->catid);
        $exclude_author = $this->excludeAuthors($row->created_by);

        if(empty($exclude_article) AND empty($exclude_author))
        {
            $this->loadHeadData();
            $author_data = $this->getAuthorData($row->created_by);
            $html = $this->getData($author_data);

            if($position == 'after')
            {
                if($this->_article_view == true)
                {
                    $row->text .= $html;
                }
                else
                {
                    $row->introtext .= $html;
                }
            }
            else
            {
                if($this->_article_view == true)
                {
                    $row->text = $html.$row->text;
                }
                else
                {
                    $row->introtext = $html.$row->introtext;
                }
            }
        }
    }

    /**
     * Load data to the head section of the page
     */
    private function loadHeadData()
    {
        $document = JFactory::getDocument();
        $document->addStyleSheet('plugins/content/authorinfobox/authorinfobox.css', 'text/css');
    }

    /**
     * Collect all needed data from the author
     *
     * @param integer $author_id
     * @return boolean
     */
    private function getAuthorData($author_id)
    {
        $author = JFactory::getUser($author_id);

        if($this->params->get('show_website'))
        {
            $author_website = $this->getWebsite($this->params->get('show_website'), $author_id);
        }
        else
        {
            $author_website = false;
        }

        if($this->params->get('show_description'))
        {
            $author_description = $this->getDescription($this->params->get('show_description'), $author_id);
        }
        else
        {
            $author_description = false;
        }

        if($this->params->get('avatar'))
        {
            $author_image = $this->getAvatar($this->params->get('avatar'), $author_id, $author->email);
        }
        else
        {
            $author_image = false;
        }

        if($this->params->get('name') == 1)
        {
            $author_name = $author->username;
        }
        elseif($this->params->get('name') == 2)
        {
            $author_name = $author->name;
        }
        else
        {
            $author_name = false;
        }

        if($this->params->get('name_link') == 1)
        {
            $item_id = $this->getItemId('index.php?option=com_community&view=profile', 'com_community');

            $link = JRoute::_('index.php?option=com_community&view=profile&userid='.$author_id.'&Itemid='.$item_id, false);
            $author_name_link = '<a href="'.$link.'" title="'.$author_name.'">'.$author_name.'</a>';
        }
        elseif($this->params->get('name_link') == 2)
        {
            $item_id = $this->getItemId('index.php?option=com_kunena&view=user', 'com_kunena');

            $link = JRoute::_('index.php?option=com_kunena&userid='.$author_id.'&view=user&Itemid='.$item_id, false);
            $author_name_link = '<a href="'.$link.'" title="'.$author_name.'">'.$author_name.'</a>';
        }
        elseif($this->params->get('name_link') == 3)
        {
            // Author List uses own author IDs - get the ID from the authorlist table
            $db = JFactory::getDBO();
            $query = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__authorlist').' WHERE '.$db->nameQuote('userid').' = '.$author_id;
            $db->setQuery($query);
            $authorlist_id = $db->loadResult();

            $item_id = $this->getItemId('index.php?option=com_authorlist&view=author&id='.$authorlist_id, 'com_authorlist');

            $link = JRoute::_('index.php?option=com_authorlist&view=author&id='.$authorlist_id.':'.$author->username.'&Itemid='.$item_id, false);
            $author_name_link = '<a href="'.$link.'" title="'.$author_name.'">'.$author_name.'</a>';
        }
        elseif($this->params->get('name_link') == 4)
        {
            // Get the ID and Catid from the contact_details table
            $db = JFactory::getDBO();
            $query = 'SELECT * FROM '.$db->nameQuote('#__contact_details').' WHERE '.$db->nameQuote('user_id').' = '.$author_id;
            $db->setQuery($query);
            $contact_data = $db->loadAssoc();

            if(!empty($contact_data))
            {
                $item_id = $this->getItemId('index.php?option=com_contact&view=contact&id='.$contact_data['id'], 'com_contact');

                $link = JRoute::_('index.php?option=com_contact&view=contact&id='.$contact_data['id'].':'.$contact_data['alias'].'&catid='.$contact_data['catid'].'&Itemid='.$item_id, false);
                $author_name_link = '<a href="'.$link.'" title="'.$author_name.'">'.$author_name.'</a>';
            }
            else
            {
                $author_name_link = false;
            }
        }
        else
        {
            $author_name_link = false;
        }

        $author_data = array('author_name' => $author_name, 'author_name_link' => $author_name_link, 'author_email' => $author->email, 'author_image' => $author_image, 'author_description' => $author_description, 'author_website' => $author_website);

        return $author_data;
    }

    /**
     * Create the output of the info box
     *
     * @param array $author_data
     * @return string
     */
    private function getData($author_data)
    {
        $html = '<!-- Author Info Box Plugin for Joomla! 2.5 by Kubik-Rubik.de - Viktor Vogel -->';
        $html .= '<div id="author_infobox">';

        if($this->params->get('title'))
        {
            $html .= '<div class="author_infobox_title">'.$this->params->get('title').'</div>';
        }

        if(!empty($author_data['author_image']))
        {
            if($this->params->get('avatar') == 1)
            {
                $class = 'author_infobox_image';
            }
            elseif($this->params->get('avatar') == 2)
            {
                $class = 'author_infobox_image_jomsocial';
            }
            elseif($this->params->get('avatar') == 3)
            {
                $class = 'author_infobox_image_kunena';
            }
            elseif($this->params->get('avatar') == 4)
            {
                $class = 'author_infobox_image_authorlist';
            }

            $html .= '<div class="'.$class.'"><img src="'.$author_data['author_image'].'" alt="'.$author_data['author_name'].'" /></div>';
        }

        $html_author_infobox_name = $this->getDataAuthorInfoboxName($author_data);

        if(!empty($html_author_infobox_name))
        {
            $html .= '<div class="author_infobox_name">'.$html_author_infobox_name.'</div>';
        }

        if($this->params->get('title_description'))
        {
            $html .= '<div class="author_infobox_aboutme">'.$this->params->get('title_description').'</div>';
        }

        if($this->params->get('show_description') AND !empty($author_data['author_description']))
        {
            $html .= '<div class="author_infobox_description">'.$author_data['author_description'].'</div>';
        }

        $html .= '</div><br class="clear" />';

        return $html;
    }

    /**
     * Create name line in the info box
     *
     * @param type $author_data
     * @return string
     */
    private function getDataAuthorInfoboxName($author_data)
    {
        $entries = array();

        if(!empty($author_data['author_name']))
        {
            $entries[0]['title'] = JText::_('PLG_AUTHORINFOBOX_FRONTEND_AUTHOR');

            if(!empty($author_data['author_name_link']))
            {
                $entries[0]['data'] = $author_data['author_name_link'];
            }
            else
            {
                $entries[0]['data'] = $author_data['author_name'];
            }
        }

        if($this->params->get('show_website') AND !empty($author_data['author_website']))
        {
            $entries[1]['title'] = JText::_('PLG_AUTHORINFOBOX_FRONTEND_WEBSITE');
            $entries[1]['data'] = '<a href="'.$author_data['author_website'].'" title="'.JText::_('PLG_AUTHORINFOBOX_FRONTEND_WEBSITE').': '.$author_data['author_website'].'">'.$author_data['author_website'].'</a>';
        }

        if($this->params->get('show_email') AND !empty($author_data['author_email']))
        {
            $entries[2]['title'] = JText::_('PLG_AUTHORINFOBOX_FRONTEND_EMAIL');

            if($this->params->get('show_email') == 1)
            {
                $entries[2]['data'] = JHtml::_('email.cloak', $author_data['author_email']);
            }
            else
            {
                $entries[2]['data'] = $author_data['author_email'];
            }
        }

        $html = '';
        $count = 0;

        foreach($entries as $entry)
        {
            if($count == 0)
            {
                $span_class = 'bold';
            }
            else
            {
                $span_class = 'bold marginleft';
            }

            $html .= '<span class="'.$span_class.'">'.$entry['title'].':</span> '.$entry['data'];

            $count++;
        }

        return $html;
    }

    /**
     * Check whether the loaded article is excluded
     *
     * @param integer $catid
     * @return boolean
     */
    private function excludeArticles($catid)
    {
        $exclude_articles_ids = $this->params->get('exclude_articles_ids');

        if(!empty($exclude_articles_ids))
        {
            $exclude_articles_ids = array_map('trim', explode(',', $exclude_articles_ids));

            $id = JFactory::getApplication()->input->getInt('id');

            if(in_array($id, $exclude_articles_ids))
            {
                return true;
            }
        }

        $exclude_articles_itemids = $this->params->get('exclude_articles_itemids');

        if(!empty($exclude_articles_itemids))
        {
            $exclude_articles_itemids = array_map('trim', explode(',', $exclude_articles_itemids));

            $item_id = JFactory::getApplication()->input->getInt('Itemid');

            if(in_array($item_id, $exclude_articles_itemids))
            {
                return true;
            }
        }

        $exclude_articles_categories = $this->params->get('exclude_articles_categories');

        if(!empty($exclude_articles_categories))
        {
            $exclude_articles_categories = array_map('trim', explode(',', $exclude_articles_categories));

            if(in_array($catid, $exclude_articles_categories))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the author is excluded
     *
     * @param integer $id
     * @return boolean
     */
    private function excludeAuthors($id)
    {
        $exclude_authors_ids = $this->params->get('exclude_authors_ids');

        if(!empty($exclude_authors_ids))
        {
            $exclude_authors_ids = array_map('trim', explode(',', $exclude_authors_ids));

            if(in_array($id, $exclude_authors_ids))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Get website of the author
     *
     * @param integer $type
     * @param integer $author_id
     * @return string
     */
    private function getWebsite($type, $author_id)
    {
        $author_website = false;

        // Joomla! Profile Plugin
        if($type == 1)
        {
            if(JPluginHelper::isEnabled('user', 'profile'))
            {
                $author_profile = JUserHelper::getProfile($author_id);

                $author_website = $author_profile->profile['website'];
            }
        }
        else
        {
            $db = JFactory::getDBO();

            if($type == 2) // Component JomSocial
            {
                $query = 'SELECT '.$db->nameQuote('value').' FROM '.$db->nameQuote('#__community_fields_values').' WHERE '.$db->nameQuote('user_id').' = '.$author_id.' AND '.$db->nameQuote('field_id').' = (SELECT  '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__community_fields').' WHERE '.$db->nameQuote('fieldcode').' = '.$db->quote("FIELD_WEBSITE").')';
                $db->setQuery($query);
                $author_website = $db->loadResult();
            }
            elseif($type == 3) // Component Kunena
            {
                $query = 'SELECT '.$db->nameQuote('websiteurl').' FROM '.$db->nameQuote('#__kunena_users').' WHERE '.$db->nameQuote('userid').' = '.$author_id;
                $db->setQuery($query);
                $author_website = $db->loadResult();
            }
        }

        return $author_website;
    }

    /**
     * Get the description of the author
     *
     * @param integer $type
     * @param integer $author_id
     * @return string
     */
    private function getDescription($type, $author_id)
    {
        $author_description = false;

        // Joomla! Profile Plugin
        if($type == 1)
        {
            if(JPluginHelper::isEnabled('user', 'profile'))
            {
                $author_profile = JUserHelper::getProfile($author_id);

                $author_description = $author_profile->profile['aboutme'];
            }
        }
        else
        {
            $db = JFactory::getDBO();

            if($type == 2) // Component JomSocial
            {
                $query = 'SELECT '.$db->nameQuote('value').' FROM '.$db->nameQuote('#__community_fields_values').' WHERE '.$db->nameQuote('user_id').' = '.$author_id.' AND '.$db->nameQuote('field_id').' = (SELECT  '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__community_fields').' WHERE '.$db->nameQuote('fieldcode').' = '.$db->quote("FIELD_ABOUTME").')';
                $db->setQuery($query);
                $author_description = $db->loadResult();
            }
            elseif($type == 3) // Component Kunena
            {
                $query = 'SELECT '.$db->nameQuote('personalText').' FROM '.$db->nameQuote('#__kunena_users').' WHERE '.$db->nameQuote('userid').' = '.$author_id;
                $db->setQuery($query);
                $author_description = $db->loadResult();
            }
            elseif($type == 4) // Component Author List
            {
                $query = 'SELECT '.$db->nameQuote('description').' FROM '.$db->nameQuote('#__authorlist').' WHERE '.$db->nameQuote('userid').' = '.$author_id;
                $db->setQuery($query);
                $author_description = $db->loadResult();
            }
        }

        return $author_description;
    }

    /**
     * Get the avatar of the author
     *
     * @param integer $type
     * @param integer $author_id
     * @param string $email
     * @return string
     */
    private function getAvatar($type, $author_id, $email)
    {
        $author_image = false;

        // Gravatar
        if($type == 1)
        {
            $gravatar_hash = md5(strtolower(trim($email)));
            $author_image = 'http://www.gravatar.com/avatar/'.$gravatar_hash;
        }
        else
        {
            $db = JFactory::getDBO();

            if($type == 2) // Component JomSocial
            {
                $query = 'SELECT '.$db->nameQuote('thumb').' FROM '.$db->nameQuote('#__community_users').' WHERE '.$db->nameQuote('userid').' = '.$author_id;
                $db->setQuery($query);
                $author_image = $db->loadResult();
            }
            elseif($type == 3) // Component Kunena
            {
                $query = 'SELECT '.$db->nameQuote('avatar').' FROM '.$db->nameQuote('#__kunena_users').' WHERE '.$db->nameQuote('userid').' = '.$author_id;
                $db->setQuery($query);
                $author_image = $db->loadResult();

                if(!empty($author_image))
                {
                    $author_image = 'media/kunena/avatars/resized/size72/'.$author_image;
                }
            }
            elseif($type == 4) // Component Author List
            {
                $query = 'SELECT '.$db->nameQuote('image').' FROM '.$db->nameQuote('#__authorlist').' WHERE '.$db->nameQuote('userid').' = '.$author_id;
                $db->setQuery($query);
                $author_image = $db->loadResult();
            }
        }

        return $author_image;
    }

    /**
     * Get Itemid of the component to create correct links to the profiles
     *
     * @param string $link
     * @param string $component
     * @return integer
     */
    private function getItemId($link, $component)
    {
        $menu_jsite = new JSite();
        $menu = $menu_jsite->getMenu();
        $menu_item = $menu->getItems('link', $link, true);

        if(empty($menu_item))
        {
            $menu_item = $menu->getItems('component', $component, true);
        }

        $item_id = $menu_item->id;

        return $item_id;
    }

}
