<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.3.3 25th April 2022
 * @filesource admin/models/articles.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;

class XbrefmanModelArticles extends JModelList {
    
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'ordering','a.ordering',
                'category_title', 'c.title',
                'catid', 'a.catid', 'category_id',
                'published','a.state' );
        }
        parent::__construct($config);
    }
    
    protected function populateState($ordering = 'title', $direction = 'asc') {
        $app = Factory::getApplication();
        
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')) {
            $this->context .= '.' . $layout;
        }
        
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);
        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->setState('filter.categoryId', $categoryId);
        $tagfilt = $this->getUserStateFromRequest($this->context . '.filter.tagfilt', 'filter_tagfilt', '');
        $this->setState('filter.tagfilt', $tagfilt);
        $taglogic = $this->getUserStateFromRequest($this->context . '.filter.taglogic', 'filter_taglogic');
        $this->setState('filter.taglogic', $taglogic);
        
        $formSubmited = $app->input->post->get('form_submited');
        
        if ($formSubmited)
        {
            $categoryId = $app->input->post->get('category_id');
            $this->setState('filter.category_id', $categoryId);
            
            $tagfilt = $app->input->post->get('tagfilt');
            $this->setState('filter.tagfilt', $tagfilt);
            $this->setState('filter.tagfilt', 1);
        }
        
        // List state information.
        parent::populateState($ordering, $direction);
        
    }
    
    protected function getListQuery() {
        $targ = '{xbref ';
        
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as id, a.title as title, a.alias as alias,a.catid as catid, a.state as published, 
            a.created_by AS created_by, a.checked_out AS checked_out, a.checked_out_time AS checked_out_time, a.note AS note, 
            c.title as category_title');
        $query->select("CONCAT(a.introtext,' ', a.fulltext) as content");
        $query->from($db->quoteName('#__content').' as a');
        $query->leftJoin($db->quoteName('#__categories').' AS c ON c.id = a.catid');
        $query->where('('.$db->quoteName('a.introtext') . ' LIKE \'%'.$targ.'%\' OR '.$db->quoteName('a.fulltext') . ' LIKE \'%'.$targ.'%\' )');
        
        //filter by published state
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
            } elseif (stripos($search,'c:')===0) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search,2)), true) . '%'));
                $query->where('(a.fulltext LIKE ' . $search.' OR a.introtext LIKE '.$search.')');
            } elseif (stripos($search,':')!= 1) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(a.title LIKE ' . $search . ')');
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
				WHERE type_alias = '.$db->quote('com_content.article').')';
            $query->where('a.id NOT IN '.$subQuery);
        }
        
        
        if (!empty($tagfilt)) {
            $tagfilt = ArrayHelper::toInteger($tagfilt);
            
            if ($taglogic==2) { //exclude anything with a listed tag
                // subquery to get a virtual table of item ids to exclude
                $subQuery = '(SELECT content_item_id FROM #__contentitem_tag_map
				WHERE type_alias = '.$db->quote('com_content.article').
				' AND tag_id IN ('.implode(',',$tagfilt).'))';
                $query->where('a.id NOT IN '.$subQuery);
            } else {
                if (count($tagfilt)==1)	{ //simple version for only one tag
                    $query->join( 'INNER', $db->quoteName('#__contentitem_tag_map', 'tagmap')
                        . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id') )
                        ->where(array( $db->quoteName('tagmap.tag_id') . ' = ' . $tagfilt[0],
                            $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_content.article') )
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
                                $db->quoteName($mapname.'.type_alias') . ' = ' . $db->quote('com_content.article'))
                                );
                        }
                    } else { // match ANY listed tag
                        // make a subquery to get a virtual table to join on
                        $subQuery = $db->getQuery(true)
                        ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                        ->from($db->quoteName('#__contentitem_tag_map'))
                        ->where( array(
                            $db->quoteName('tag_id') . ' IN (' . implode(',', $tagfilt) . ')',
                            $db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'))
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
        if ($orderCol == 'a.ordering' || $orderCol == 'a.catid') {
            $orderCol = 'category_title '.$orderDirn.', a.ordering';
        }
        
        $query->order($db->escape($orderCol.' '.$orderDirn));
        
        $query->group('a.id');
        return $query;
    }
    
    public function getItems() {
        $items  = parent::getItems();
        $tagsHelper = new TagsHelper;
        foreach ($items as $i=>$item) {
            $item->tags = $tagsHelper->getItemTags('com_content.article' , $item->id);
            $item->refcnts = $this->getRefCnts($item->content);
        }       
        return $items;       
    }

    /** ONLY USED IN ADMIN ARTICLES MODEL
     * @name getRefCnts()
     * @desc parses a string returning counts of xbref shortcodes
     * @param string $articleText
     * @return array[]
     */
    private function getRefCnts($articleText) {
        $refcnts = array('tag'=>0, 'tagids'=>array(), 'tagtits'=>array(),
            'link'=>0, 'linkids'=>array(), 'linktits'=>array(),
            'text'=>0, 'texttits'=>array(),
            'foot'=>0, 'pop'=>0, 'both'=>0, 'defdisp'=>0,
            'hover'=>0, 'focus'=>0, 'click'=>0, 'deftrig'=>0,
            'badlinks'=>array(), 'badtags'=>array(), 'badpop'=>0,
            'footers'=>0 );
        $xbrefscon = XbrefmanGeneral::extensionStatus('xbrefscon','plugin','content');
        $conplugin = PluginHelper::getPlugin('content','xbrefscon');
        $defdisp = '';
        $deftrig = '';
        $linktrig = '';
        if ($conplugin) {
            $conpluginParams = new Registry($conplugin->params);
            $defdisp = $conpluginParams->get('defdisp','');
            $deftrig = $conpluginParams->get('deftrig','');
            $linktrig = $conpluginParams->get('linktrig','');
        }
        $footcnt = 0; //the count of footer references one the page - reset when output
        
        $matches = array();
        preg_match_all('!{xbref (.*?)}(.*?){/xbref}|{xbref-here ?(.*?)}!', $articleText, $matches, PREG_SET_ORDER);
        // process all the found shortcodes
        foreach ($matches as $ref) {
            //we'll do any {xbref-here's first as they may be setting a start num for subsequent refs to use
            if (substr($ref[0],0,11) == '{xbref-here' ) {
                //$ref[3] will contain and num= and head= values
                if ($footcnt) {
                    $refcnts['footers'] ++;
                    $footcnt = 0;
                }
            } else {
                // $ref[1] contains the text {xbref text }, $ref[2] contains the content {xbref ...}content{/xbref}
                $content = $ref[2];
                //strip it out any placeholders for ref link added by button
                $content = preg_replace('!<sup(.*?)</sup>!', '', $content);
                // at this point $content will only contain any text enclosed in the shortcode
                $content = trim($content);
                $ref[1] .= ' '; //make sure we have a space at the end - getNameValue() needs it
                $tagid = XbrefmanGeneral::getNameValue('tag',$ref[1]);
                $linkid = XbrefmanGeneral::getNameValue('link',$ref[1]);
                $disp = XbrefmanGeneral::getNameValue('disp',$ref[1]);
                if (($content == '') && ($disp == 'pop')) {
                    $refcnts['badpop'] ++;
                }
                $trig = XbrefmanGeneral::getNameValue('trig',$ref[1]);
                
                // if we have a tagid well do that, elseif we have a linkid, else it must be text
                $ok = true;
                if ($tagid > 0) {
                    if (XbrefmanHelper::checkId($tagid, '#__tags')) {
                        $refcnts['tag'] ++;
                        $refcnts['tagids'][] = $tagid;
                    } else {
                        $refcnts['badtags'][] = $tagid;
                        $ok = false;
                    }
                } elseif ($linkid>0) {
                    if (XbrefmanGeneral::extensionStatus('com_weblinks','component')!==false) {
                        if (XbrefmanHelper::checkId($linkid, '#__weblinks')) {
                            $ok = true;
                            $refcnts['link'] ++;
                            $refcnts['linkids'][] = $linkid;
                            $trig = ($linktrig) ? $linktrig : $trig;
                        } else {
                            $refcnts['badlinks'][] = $linkid;
                            $ok = false;
                        }
                    } else {
                        //weblinks not installed
                        $refcnts['badlinks'][] = $linkid;
                        $ok = false;
                    }
                } else {
                    $title = XbrefmanGeneral::getNameValue('title',$ref[1]);
                    if ($title) {
                        $ok = true;
                        $refcnts['text'] ++;
                        $refcnts['texttits'][] = $title;
                    } else {
                        $ok = false;
                    }
                }
                if ($ok) {
                    if ($disp != 'foot') {
                        if ($trig=='') {
                            $refcnts['deftrig'] ++;
                            $trig = $deftrig;
                        } else {
                            $refcnts[$trig] ++;
                        }
                    }
                    if ($disp=='') {
                        $disp = $defdisp;
                        $refcnts['defdisp'] ++;
                    } else {
                        $refcnts[$disp] ++;
                    }
                    if ($disp != 'pop') {
                        $footcnt ++;
                        $refcnts['foot'] ++;
                    }
                }
            } // endif else xbfer-here
        } //end foreach $matches
        if ($footcnt) {
            $refcnts['footers'] ++;
        }
        $refcnts['tagids'] = array_unique($refcnts['tagids']);
        $refcnts['tagtits'] = XbrefmanHelper::getTitles($refcnts['tagids'],'#__tags');
        $refcnts['linkids'] = array_unique($refcnts['linkids']);
        $refcnts['linktits'] = XbrefmanHelper::getTitles($refcnts['linkids'],'#__weblinks');
        $refcnts['texttits'] = array_unique($refcnts['texttits']);
        $refcnts['badtags'] = array_unique($refcnts['badtags']);
        $refcnts['badlinks'] = array_unique($refcnts['badlinks']);
        
        return $refcnts;
    }
    
    
}