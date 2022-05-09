<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.3 24th April 2022
 * @filesource admin/models/tagrefs.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class XbrefmanModelTagRefs extends JModelList {
    
    protected $taglist = '';
    
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'path','a.path',
                'published','a.published' );
        }
        parent::__construct($config);
    }
    
    protected function populateState($ordering = 'title', $direction = 'asc') {
        $app = Factory::getApplication();
        
        // Adjust the context to support modal layouts.
//         if ($layout = $app->input->get('layout')) {
//             $this->context .= '.' . $layout;
//         }
        
         $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
         $this->setState('filter.search', $search);
         $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
         $this->setState('filter.published', $published);
//         $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
//         $this->setState('filter.categoryId', $categoryId);
//         $tagfilt = $this->getUserStateFromRequest($this->context . '.filter.tagfilt', 'filter_tagfilt', '');
//         $this->setState('filter.tagfilt', $tagfilt);
//         $filt = $this->getUserStateFromRequest($this->context . '.filter.taglogic', 'filter_taglogic');
//         $this->setState('filter.taglogic', $filt);
        //         $filt = $this->getUserStateFromRequest($this->context . '.filter.', 'filter_');
        //         $this->setState('filter.', $filt);
        
        $formSubmited = $app->input->post->get('form_submited');
        
        if ($formSubmited)
        {
//             $categoryId = $app->input->post->get('category_id');
//             $this->setState('filter.category_id', $categoryId);
            
//             $tagfilt = $app->input->post->get('tagfilt');
//             $this->setState('filter.tagfilt', $tagfilt);
        }
        
        // List state information.
        parent::populateState($ordering, $direction);
        
    }
    
    protected function getListQuery() {
        $db = Factory::getDbo();
        //now we can get the tag details, keeping the list of article titles and ids with the tag to save having to query again in getItmes
        $query = $db->getQuery(true);
        $query->select('a.id, a.title, a.alias AS alias, a.description, a.parent_id AS parent_id, a.lft, a.rgt, a.path AS path, 
            a.published AS published, a.note AS note, a.checked_out AS checked_out, a.checked_out_time AS checked_out_time, 
            a.created_user_id AS created_user_id');
        $query->from($db->quoteName('#__tags','a'));
        $query->where('a.id IN ('.$this->taglist.')');
        
        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('published = ' . (int) $published);
        } else if ($published === '') {
            $query->where('(published IN (0, 1))');
        }
        
        // Filter by search in title/id
        $search = $this->getState('filter.search');
        
        if (!empty($search)) {
            if (stripos($search, 'i:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 2));
            } elseif (stripos($search,':')!= 1) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('a.title LIKE ' . $search );
            }
        }
        
        // Add the list ordering clause.
        $orderCol       = $this->state->get('list.ordering', 'path');
        $orderDirn      = $this->state->get('list.direction', 'ASC');
        
        $query->order($db->escape($orderCol.' '.$orderDirn));
        
        
        return $query;
    }
    
    public function getItems() {
        $articles = $this->getArticlesWithTags();
        $tagarts = $this->getTagsFromArticles($articles);
        $this->taglist = implode(',',array_keys($tagarts));
        
        $items  = parent::getItems();
        
        foreach ($items as $item) {
            $item->articles = $tagarts[$item->id];
        }
        
        return $items;
        
    }
    
    /**
     * @name getArticlesWithTags()
     * @desc gets a list of articles (array of objects) that have tag references
     * @return mixed|void|unknown[]|mixed[]
     */
    public function getArticlesWithTags() {
        $targ = "'".'{xbref.+tag="[1-9][0-9]*'."'";
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('a.id','a.title', 'a.introtext','a.fulltext'),array('artid','arttitle','introtext','fulltext')));
//        $query->select('CONCAT(`introtext`," ",`fulltext`) AS content');
        $query->from($db->quoteName('#__content','a'));
        $query->where('CONCAT( `introtext`," ",`fulltext`) REGEXP '.$targ);
        $db->setQuery($query);
        return $db->loadObjectList('artid');
    }
    
    /**
     * @name getTagsFromArticles()
     * @desc gets details of tags used
     * @param array $articles : array of article objects with it, title & content
     * @return array[][]  $tagarts : Array indexed by tagid of articles referencing the tag 
     */
    public function getTagsFromArticles($articles) {
       //now we'll go through them getting the tag ids, together with the article id and title
        $tagids = array(); //the array of tagids
        $tagarts = array(array()); //array of article  id & titles indexed by tagid
        $matches = array();
        foreach ($articles as $article) {
            preg_match_all('!{xbref[^}]+?tag="([1-9]+[0-9]*)"(.*?)}(.*?){/xbref}!', $article->introtext.' '.$article->fulltext, $matches, PREG_SET_ORDER );
            unset($article->introtext);
            unset($article->fulltext);
            //            $article->matches = $matches;
            foreach ($matches as $match) {
                //parse $match[3] for disp=
                if (!array_search($match[1], $tagids)) {
                    $tagids[] = $match[1];
                }
                $tagarts[$match[1]][$article->artid] = $article;
            }
        } 
        unset($tagarts[0]);
        return $tagarts;
    }
    
    public function addTagSelect($ids) {
        if (!is_array($ids)) {
            $ids =explode(',',$ids);
        }
        //get existing select list
        $btnplugin = PluginHelper::getPlugin('editors-xtd','xbrefsbtn');
        if ($btnplugin) {
            $btnpluginParams = new Registry($btnplugin->params);
            $taglist = $btnpluginParams->get('taglist','');
            if (empty($taglist)) {
                return false;
            }
            $children = $btnpluginParams->get('usechild','');
        //merge with $ids
            if ($children) {
                $fulltaglist = XbrefmanHelper::getTagsChildren($taglist);
            } else {
                $fulltaglist = $taglist;
            }
            $newtaglist = array_diff($ids,$fulltaglist);
            if (count($newtaglist)) {
                if (empty($taglist)) {
                    $taglist = $newtaglist;
                } else {
                    $taglist = array_merge($taglist,$newtaglist);
                }
                $btnpluginParams->set('taglist',$taglist);
        //save back to options
                $db = Factory::getDBO();
                $query = $db->getQuery(true);
                $query->update('#__extensions AS a');
                $query->set('a.params = ' . $db->quote((string)$btnpluginParams));
                $query->where('a.element = "xbrefsbtn"');
                $db->setQuery($query);
                $res = $db->execute();
                $this->cleanCache('_system');
                return $res;
            }
        } else {
            return false;
        }
        return true;
    }

    public function tagsDeleteShortcodes($ids) {
        if (!is_array($ids)) {
            $ids =explode(',',$ids);
        }
        //get all articles with shortcodes
        $arts = $this->getArticlesWithTags();
        $targ = '';
        $cnts = array();
        foreach ($ids as $tagid) {
            $cnts[$tagid]=0;
            foreach ($arts as $article) {     
                // for both introtext and fulltext
               //remove any shortcodes for this tag
               //remove any sup fragments left
                // remove any highlight fragements left
                //and save the article
                $icnt = 0; $fcnt = 0;
                $article->introtext = preg_replace('!{xbref[^}]+?tag="'.$tagid.'".*?}(.*?){\/xbref}!', '${1}', $article->introtext, -1, $icnt); // any tagid = [1-9]\d*
                $article->introtext = preg_replace('!<sup.*?>\['.$tagid.'\]*<\/sup>!', '', $article->introtext);
                $article->introtext = preg_replace('!<span class="xbshowref".*?><\/span>!', '', $article->introtext);
                
                if ($article->fulltext!='') {
                    $article->fulltext = preg_replace('!{xbref[^}]+?tag="'.$tagid.'".*?}(.*?){\/xbref}!', '${1}', $article->fulltext, -1, $fcnt);
                    $article->fulltext = preg_replace('!<sup.*?>\['.$tagid.'\]*<\/sup>!', '', $article->fulltext);
                    $article->fulltext = preg_replace('!<span class="xbshowref".*?><\/span>!', '', $article->fulltext);
                }
                $fnd = $icnt + $fcnt;
                if ($fnd) {
                    // write back to the db
                    $db = Factory::getDBO();
                    $query = $db->getQuery(true);
                    $query->update('#__content AS a');
                    $query->set('a.introtext = ' . $db->quote($article->introtext));
                    $query->set('a.fulltext = ' . $db->quote($article->fulltext));
                    $query->where('a.id = '.$article->artid);
                    $db->setQuery($query);
                    $res = $db->execute();
                    if ($res!==false) $cnts[$tagid] += $fnd;
                }
            }          
        }
        $message = Text::_('XBREFMAN_NO_CHANGES');
        if (count($cnts)) {
            $this->cleanCache();
            $message = array_sum($cnts).' '.Text::_('XBREFMAN_SCODES_REMOVED').' '.count($cnts).' '.lcfirst(Text::_('XBREFMAN_ARTICLES'));
        }
        Factory::getApplication()->enqueueMessage($message);
        return;
    }
    
}