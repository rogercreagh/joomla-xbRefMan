<?php 
/*******
 * @package xbRefMan Component
 * @version 0.7.7 1st April 2022
 * @filesource site/models/footnotes.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Uri\Uri;

class XbrefmanModelFootnotes extends JModelList {
    
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array ('title', 'a.title',
                'catid', 'a.catid', 'category_id',
                'category_title' );
        }
        parent::__construct($config);
        
    }
    
    protected function populateState($ordering = null, $direction = null) {
        
        $app = Factory::getApplication('site');
        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);
        
        $categoryId = $app->getUserStateFromRequest('catid', 'catid','');
        $app->setUserState('catid', '');
        $this->setState('categoryId',$categoryId);
        $tagId = $app->getUserStateFromRequest('tagid', 'tagid','');
        $app->setUserState('tagid', '');
        $this->setState('tagId',$tagId);
        
        parent::populateState($ordering, $direction);
        
        //pagination limit
        $limit = $this->getUserStateFromRequest($this->context.'.limit', 'limit', 25 );
        $this->setState('limit', $limit);
        $this->setState('list.limit', $limit);
        $limitstart = $app->getUserStateFromRequest('limitstart', 'limitstart', $app->get('start'));
        $this->setState('list.start', $limitstart);
        
    }
    
    protected function getListQuery() {
        //this will just get all articles with xbrefs. loading of footnotes is done in getItems()
        $targ = '{xbref ';
        
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as id, a.title as title, a.alias as alias,a.catid as catid, a.state as published,
            a.created_by AS created_by, a.checked_out AS checked_out, a.checked_out_time AS checked_out_time, a.note AS note,
            c.title as category_title');
        $query->select("CONCAT(a.introtext,' ', a.fulltext) as content");
        $query->from($db->quoteName('#__content').' as a');
        $query->leftJoin($db->quoteName('#__categories').' AS c ON c.id = a.catid');
        $query->where('(CONCAT( `introtext`," ",`fulltext`)) LIKE \'%'.$targ.'%\' ');
        
        //only show published articles
            $query->where('state = 1');
        
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
        
        //filter by article tag - we are only filtering on the request (menu setting) not user filter
        $tagId = $app->getUserStateFromRequest('tagid', 'tagid','');
        $app->setUserState('tagid', '');
        if (!empty($tagId)) {
            $tagfilt = array(abs($tagId));
            $taglogic = $tagId>0 ? 0 : 2;
//        } else {
//            $tagfilt = $this->getState('filter.tagfilt');
//$taglogic = $this->getState('filter.taglogic');  //0=ANY 1=ALL 2= None
        }
        
//         if (($taglogic === '2') && (empty($tagfilt))) {
//             //if if we select tagged=excl and no tags specified then only show untagged items
//             $subQuery = '(SELECT content_item_id FROM #__contentitem_tag_map
// 				WHERE type_alias = '.$db->quote('com_content.article').')';
//             $query->where('a.id NOT IN '.$subQuery);
//         }
        
        
        if (!empty($tagfilt)) {
            $tagfilt = ArrayHelper::toInteger($tagfilt);
            
            if ($taglogic==2) { //exclude anything with a listed tag
//                 // subquery to get a virtual table of item ids to exclude
//                 $subQuery = '(SELECT content_item_id FROM #__contentitem_tag_map
// 				WHERE type_alias = '.$db->quote('com_content.article').
// 				' AND tag_id IN ('.implode(',',$tagfilt).'))';
//                 $query->where('a.id NOT IN '.$subQuery);
            } else {
                if (count($tagfilt)==1)	{ //simple version for only one tag
                    $query->join( 'INNER', $db->quoteName('#__contentitem_tag_map', 'tagmap')
                        . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id') )
                        ->where(array( $db->quoteName('tagmap.tag_id') . ' = ' . $tagfilt[0],
                            $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_content.article') )
                            );
//                } else { //more than one tag
//                     if ($taglogic == 1) { // match ALL listed tags
//                         // iterate through the list adding a match condition for each
//                         for ($i = 0; $i < count($tagfilt); $i++) {
//                             $mapname = 'tagmap'.$i;
//                             $query->join( 'INNER', $db->quoteName('#__contentitem_tag_map', $mapname).
//                                 ' ON ' . $db->quoteName($mapname.'.content_item_id') . ' = ' . $db->quoteName('a.id'));
//                             $query->where( array(
//                                 $db->quoteName($mapname.'.tag_id') . ' = ' . $tagfilt[$i],
//                                 $db->quoteName($mapname.'.type_alias') . ' = ' . $db->quote('com_content.article'))
//                                 );
//                         }
//                     } else { // match ANY listed tag
//                         // make a subquery to get a virtual table to join on
//                         $subQuery = $db->getQuery(true)
//                         ->select('DISTINCT ' . $db->quoteName('content_item_id'))
//                         ->from($db->quoteName('#__contentitem_tag_map'))
//                         ->where( array(
//                             $db->quoteName('tag_id') . ' IN (' . implode(',', $tagfilt) . ')',
//                             $db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'))
//                             );
//                         $query->join(
//                             'INNER',
//                             '(' . $subQuery . ') AS ' . $db->quoteName('tagmap')
//                             . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
//                             );
                        
 //                   } //endif all/any
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
            $item->footnotes = XbrefmanHelper::getArticleRefs($item->content, 'disp!="pop"');
        }
        // ideally need to exclude items with no footnotes here - currently done in view
        return $items;
    }
    
}
