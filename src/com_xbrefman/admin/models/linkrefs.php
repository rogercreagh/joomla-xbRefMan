<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.3 24th April 2022
 * @filesource admin/models/tagrefs.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class XbrefmanModelLinkRefs extends JModelList {
    
    protected $linklist = '';
    
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'category_title', 'c.title',
                'catid', 'a.catid', 'category_id',
                'published','a.published' );
        }
        parent::__construct($config);
    }
    
    protected function populateState($ordering = 'title', $direction = 'asc') {
        $app = Factory::getApplication();
        
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);
        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->setState('filter.categoryId', $categoryId);
        $tagfilt = $this->getUserStateFromRequest($this->context . '.filter.tagfilt', 'filter_tagfilt', '');
        $this->setState('filter.tagfilt', $tagfilt);
        $taglogic = $this->getUserStateFromRequest($this->context . '.filter.taglogic', 'filter_taglogic');
        $this->setState('filter.taglogic', $taglogic);
        //         $taglogic = $this->getUserStateFromRequest($this->context . '.filter.', 'filter_');
        //         $this->setState('filter.', $taglogic);
        
        $formSubmited = $app->input->post->get('form_submited');
        
        if ($formSubmited) {
            $categoryId = $app->input->post->get('category_id');
            $this->setState('filter.category_id', $categoryId);
            
            $tagfilt = $app->input->post->get('tagfilt');
            $this->setState('filter.tagfilt', $tagfilt);
            $taglogic = $app->input->post->get('taglogic');
            $this->setState('filter.taglogic', $taglogic);
        }
        
        // List state information.
        parent::populateState($ordering, $direction);
        
    }
    
    protected function getListQuery() {
        $db = Factory::getDbo();
        //now we can get the link details, keeping the list of article titles and ids with the tag to save having to query again in getItmes
        $query = $db->getQuery(true);
        $query->select('a.id, a.title, a.alias AS alias, a.description, a.url AS url, a.catid AS catid, 
            a.state AS published, a.checked_out AS checked_out, a.checked_out_time AS checked_out_time, 
            a.created_by AS created_by, c.title as category_title');
        $query->from($db->quoteName('#__weblinks','a'));
        $query->leftJoin($db->quoteName('#__categories').' AS c ON c.id = a.catid');
        $query->where('a.id IN ('.$this->linklist.')');
        
        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('state = ' . (int) $published);
        } else if ($published === '') {
            $query->where('(state IN (0, 1))');
        }
        
        // Filter by category.
        //TODO handle multiple cats
        $app = Factory::getApplication();
        //do we have a catid request, if so we need to over-ride any filter, but save the filter to re-instate?
        $categoryId = $app->getUserStateFromRequest('catid', 'catid','');
        $app->setUserState('catid', '');
        //        $subcats=0;
        if ($categoryId=='') {
            $categoryId = $this->getState('filter.category_id');
            //        $subcats = $this->getState('filter.subcats');
        }
        if (is_numeric($categoryId)) {
            //            if ($subcats) {
            //                $query->where('a.catid IN ('.(int)$categoryId.','.self::getSubCategoriesList($categoryId).')');
            //            } else {
            $query->where($db->quoteName('a.catid') . ' = ' . (int) $categoryId);
            //            }
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
        
        //filter by tags
        $tagId = $app->getUserStateFromRequest('tagid', 'tagid','');
        $app->setUserState('tagid', '');
        if (!empty($tagId)) {
            $tagfilt = array(abs($tagId));
            $taglogic = $tagId>0 ? 0 : 2;
        } else {
            $tagfilt = $this->getState('filter.tagfilt');
            $taglogic = $this->getState('filter.taglogic');  //0=ANY 1=ALL 2= None
        }
        
        if (($taglogic === '2') && (empty($tagfilt))) {
            //if if we select tagged=excl and no tags specified then only show untagged items
            $subQuery = '(SELECT content_item_id FROM #__contentitem_tag_map
				WHERE type_alias = '.$db->quote('com_weblinks.weblink').')';
            $query->where('a.id NOT IN '.$subQuery);
        }
        
        
        if (!empty($tagfilt)) {
            $tagfilt = ArrayHelper::toInteger($tagfilt);
            
            if ($taglogic==2) { //exclude anything with a listed tag
                // subquery to get a virtual table of item ids to exclude
                $subQuery = '(SELECT content_item_id FROM #__contentitem_tag_map
				WHERE type_alias = '.$db->quote('com_weblinks.weblink').
				' AND tag_id IN ('.implode(',',$tagfilt).'))';
                $query->where('a.id NOT IN '.$subQuery);
            } else {
                if (count($tagfilt)==1)	{ //simple version for only one tag
                    $query->join( 'INNER', $db->quoteName('#__contentitem_tag_map', 'tagmap')
                        . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id') )
                        ->where(array( $db->quoteName('tagmap.tag_id') . ' = ' . $tagfilt[0],
                            $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_weblinks.weblink') )
                            );
                } else { //more than one tag
                    if ($taglogic == 1) { // match ALL listed tags
                        // iterate through the list adding a match condition for each
                        for ($i = 0; $i < count($tagfilt); $i++) {
                            $mapname = 'tagmap'.$i;
                            $query->join( 'INNER', $db->quoteName('#__contentitem_tag_map', $mapname).
                                ' ON ' . $db->quoteName($mapname.'.content_item_id') . ' = ' . $db->quoteName('a.id'));
                            $query->where( array(
                                $db->quoteName($mapname.'.tag_id') . ' = ' . $tagfilt[$i],
                                $db->quoteName($mapname.'.type_alias') . ' = ' . $db->quote('com_weblinks.weblink'))
                                );
                        }
                    } else { // match ANY listed tag
                        // make a subquery to get a virtual table to join on
                        $subQuery = $db->getQuery(true)
                        ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                        ->from($db->quoteName('#__contentitem_tag_map'))
                        ->where( array(
                            $db->quoteName('tag_id') . ' IN (' . implode(',', $tagfilt) . ')',
                            $db->quoteName('type_alias') . ' = ' . $db->quote('com_weblinks.weblink'))
                            );
                        $query->join(
                            'INNER',
                            '(' . $subQuery . ') AS ' . $db->quoteName('tagmap')
                            . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
                            );
                        
                    } //endif all/any
                } //endif one/many tag
            }
        } //if not empty tagfilt
        
        // Add the list ordering clause.
        $orderCol       = $this->state->get('list.ordering', 'title');
        $orderDirn      = $this->state->get('list.direction', 'ASC');
        
        $query->order($db->escape($orderCol.' '.$orderDirn));
        
        
        return $query;
    }
    
    public function getItems() {
        $articles = $this->getArticlesWithLinks();
        $arts = $this->getLinksFromArticles($articles);
        $this->linklist = implode(',',array_keys($arts));
        
        
        $items  = parent::getItems();
        
        $tagsHelper = new TagsHelper;
        foreach ($items as $item) {
            $item->articles = $arts[$item->id];
            $tags = $tagsHelper->getItemTags('com_weblinks.weblink' , $item->id);
            $item->taglist = array();
            foreach ($tags as $tag) {
                $item->taglist[$tag->id] = $tag->title;
            }
            $item->selcat = false;
            $item->seltag = false;
        }        
        return $items;        
    }
    
    /**
     * @name getArticlesWithLinks()
     * @desc gets a list of articles (array of objects) that have link references
     * @return mixed|void|unknown[]|mixed[]
     */
    public function getArticlesWithLinks() {
        $targ = "'".'{xbref.+link="[1-9][0-9]*'."'";
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
     * @name getLinksFromArticles()
     * @desc gets details of link used
     * @param array $articles : array of article objects with it, title & content
     * @return array[][]  $tagarts : Array indexed by tagid of articles referencing the tag 
     */
    public function getLinksFromArticles($articles) {
       //now we'll go through them getting the tag ids, together with the article id and title
        $linkids = array(); //the array of tagids
        $arts = array(array()); //array of article  id & titles indexed by tagid
        $matches = array();
        foreach ($articles as $article) {
            preg_match_all('!{xbref[^}]+?link="([1-9]+[0-9]*)"(.*?)}(.*?){/xbref}!', $article->introtext.' '.$article->fulltext, $matches, PREG_SET_ORDER );
            unset($article->introtext);
            unset($article->fulltext);
            //            $article->matches = $matches;
            foreach ($matches as $match) {
                //parse $match[3] for disp=
                if (!array_search($match[1], $linkids)) {
                    $linkids[] = $match[1];
                }
                $arts[$match[1]][$article->artid] = $article;
            }
        } 
        unset($arts[0]);
        return $arts;
    }
    
    public function addLinkSelect($ids) {
        if (!is_array($ids)) {
            $ids =explode(',',$ids);
        }
        //get existing select list
        $btnplugin = PluginHelper::getPlugin('editors-xtd','xbrefsbtn');
        if ($btnplugin) {
            $btnpluginParams = new Registry($btnplugin->params);
            $linklist = $btnpluginParams->get('linklist','');
            $children = $btnpluginParams->get('linkusechild','');
        //merge with $ids
            if ($children) {
                $fulltaglist = XbrefmanHelper::getTagsChildren($linklist);
            } else {
                $fulltaglist = $linklist;
            }
            $newtaglist = array_diff($ids,$fulltaglist);
            if (count($newtaglist)) {
                $linklist = array_merge($linklist,$newtaglist);
                $btnpluginParams->set('linklist',$linklist);
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

    public function linksDeleteShortcodes($ids) {
        if (!is_array($ids)) {
            $ids =explode(',',$ids);
        }
        //get all articles with shortcodes
        $arts = $this->getArticlesWithLinks();
        $targ = '';
        $cnts = array();
        foreach ($ids as $linkid) {
            $cnts[$linkid]=0;
            foreach ($arts as $article) {     
                $icnt = 0; $fcnt = 0;
                $article->introtext = preg_replace('!{xbref[^}]+?link="'.$linkid.'".*?}(.*?){\/xbref}!', '${1}', $article->introtext, -1, $icnt); // any tagid = [1-9]\d*
                $article->introtext = preg_replace('!<sup.*?>\['.$linkid.'\]*<\/sup>!', '', $article->introtext);
                $article->introtext = preg_replace('!<span class="xbshowref".*?><\/span>!', '', $article->introtext);
                
                if ($article->fulltext!='') {
                    $article->fulltext = preg_replace('!{xbref[^}]+?link="'.$linkid.'".*?}(.*?){\/xbref}!', '${1}', $article->fulltext, -1, $fcnt);
                    $article->fulltext = preg_replace('!<sup.*?>\['.$linkid.'\]*<\/sup>!', '', $article->fulltext);
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
                    if ($res!==false) $cnts[$linkid] += $fnd;
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