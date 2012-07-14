<?php
/**
 * @Copyright
 * @package     AIB - Author Info Box
 * @author      Viktor Vogel {@link http://www.kubik-rubik.de}
 * @version     2.5-2
 * @date        Created on 04-Jun-2012
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
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    public function onContentBeforeDisplay($context, &$row, &$params, $page = 0)
    {
        if($this->params->get('position') == 0)
        {
            if($this->params->get('articleview'))
            {
                if(JRequest::getWord('view') != 'article')
                {
                    return;
                }
            }

            $exclude_article = $this->excludeArticles($row->catid);

            if($exclude_article != true)
            {
                $this->loadHeadData();
                $author_data = $this->getAuthorData($row->created_by);
                $html = $this->getData($author_data);

                return $html;
            }
        }
    }

    public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
    {
        if($this->params->get('position') == 1)
        {
            if($this->params->get('articleview'))
            {
                if(JRequest::getWord('view') != 'article')
                {
                    return;
                }
            }

            $exclude_article = $this->excludeArticles($row->catid);

            if($exclude_article != true)
            {
                $this->loadHeadData();
                $author_data = $this->getAuthorData($row->created_by);
                $html = $this->getData($author_data);

                return $html;
            }
        }
    }

    private function loadHeadData()
    {
        $document = JFactory::getDocument();
        $document->addStyleSheet('plugins/content/authorinfobox/authorinfobox.css', 'text/css');
    }

    private function getAuthorData($author_id)
    {
        $author = JFactory::getUser($author_id);

        if(JPluginHelper::isEnabled('user', 'profile'))
        {
            $author_profile = JUserHelper::getProfile($author_id);

            $author_description = $author_profile->profile['aboutme'];
            $author_website = $author_profile->profile['website'];
        }
        else
        {
            $author_description = false;
            $author_website = false;
        }

        if($this->params->get('gravatar'))
        {
            $gravatar_hash = md5(strtolower(trim($author->email)));
            $author_image = 'http://www.gravatar.com/avatar/'.$gravatar_hash;
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

        $author_data = array('author_name' => $author_name, 'author_email' => $author->email, 'author_image' => $author_image, 'author_description' => $author_description, 'author_website' => $author_website);

        return $author_data;
    }

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
            $html .= '<div class="author_infobox_image"><img src="'.$author_data['author_image'].'" alt="'.$author_data['author_name'].'" /></div>';
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

    private function getDataAuthorInfoboxName($author_data)
    {
        $entries = array();

        if(!empty($author_data['author_name']))
        {
            $entries[0]['title'] = JText::_('PLG_AUTHORINFOBOX_FRONTEND_AUTHOR');
            $entries[0]['data'] = $author_data['author_name'];
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

    private function excludeArticles($catid)
    {
        $exclude_articles_ids = $this->params->get('exclude_articles_ids');

        if(!empty($exclude_articles_ids))
        {
            $exclude_articles_ids = array_map('trim', explode(',', $exclude_articles_ids));

            $id = JRequest::getInt('id');

            foreach($exclude_articles_ids as $value)
            {
                if($value == $id)
                {
                    return true;
                }
            }
        }

        $exclude_articles_itemids = $this->params->get('exclude_articles_itemids');

        if(!empty($exclude_articles_itemids))
        {
            $exclude_articles_itemids = array_map('trim', explode(',', $exclude_articles_itemids));

            $item_id = JRequest::getInt('Itemid');

            foreach($exclude_articles_itemids as $value)
            {
                if($value == $item_id)
                {
                    return true;
                }
            }
        }

        $exclude_articles_categories = $this->params->get('exclude_articles_categories');

        if(!empty($exclude_articles_categories))
        {
            $exclude_articles_categories = array_map('trim', explode(',', $exclude_articles_categories));

            foreach($exclude_articles_categories as $value)
            {
                if($value == $catid)
                {
                    return true;
                }
            }
        }

        return false;
    }
}
