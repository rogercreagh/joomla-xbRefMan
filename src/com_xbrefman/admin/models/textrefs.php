<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.3 24th April 2022
 * @filesource admin/models/textrefs.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;

/*
 * we are going to get all articles with text refs as the core list
 * for each article we will get the text refs
 * we will filter by article category and tag
 * we will search by ???
 */
class XbrefmanModelTextrefs extends JModelList {
    
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
        //         $taglogic = $this->getUserStateFromRequest($this->context . '.filter.', 'filter_');
        //         $this->setState('filter.', $taglogic);
        
        $formSubmited = $app->input->post->get('form_submited');
        
        if ($formSubmited)
        {
            $categoryId = $app->input->post->get('category_id');
            $this->setState('filter.category_id', $categoryId);
            
            $tagfilt = $app->input->post->get('tagfilt');
            $this->setState('filter.tagfilt', $tagfilt);
        }
                
        // List state information.
        parent::populateState($ordering, $direction);
        
    }
    
    protected function getListQuery() {
        $targ = "'".'{xbref.+title="'."'";
        
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('a.id','a.title','a.alias','a.introtext','a.fulltext','a.catid','a.state',
            'a.created_by','a.checked_out','a.checked_out_time','a.note','c.title'), 
            array('id','title','alias','introtext','fulltext','catid','published',
                'created_by','checked_out','checked_out_time','note','category_title')));
        $query->select("CONCAT(a.introtext,'{{READMORE}}', a.fulltext) as content");
        $query->from($db->quoteName('#__content').' as a');
        $query->leftJoin($db->quoteName('#__categories').' AS c ON c.id = a.catid');
        $query->where('CONCAT( `introtext`," ",`fulltext`) REGEXP '.$targ);
        
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
    
    public function getForm($data = array(), $loadData = true) {        
        $form = $this->loadForm( 'com_xbrefman.textrefs', 'textrefs',
            array('control' => 'jform','load_data' => $loadData)
            );        
        if (empty($form)) {
            return false;
        }        
        $parent_tag = Factory::getApplication()->getUserState('parent_tag','1');
        $form->setValue('parent_tag',$parent_tag);
        return $form;
    }
    
    protected function loadFormData() {
        $parent_tag = Factory::getApplication()->getUserState('parent_tag');
        $data = array();
        $data['parent_tag'] = $parent_tag;
        return $data;
    }
    
       
    public function getItems() {
        $items  = parent::getItems();
        $tagsHelper = new TagsHelper;
        foreach ($items as $i=>$item) {
            $item->tags = $tagsHelper->getItemTags('com_content.article' , $item->id);
            $item->textrefs = $this->getTextRefs($item->content);
        }
        return $items;        
    }
    
    
    private function getTextRefs($content) {
        $refs = array();
        $matches = array();
        preg_match_all('!{xbref([^}]+?)title="(.+?)"(.*?)}(.*?){\/xbref}!', $content, $matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE  );
        foreach ($matches as $ref) {
            $thisref = array('title'=>'', 'desc'=>'', 'disp'=>'', 'trig'=>'', 'context'=>'', 'text'=>'', 'pos'=>0, 'all'=>'' );
            $ref['all'] = $ref[0][0];
            $ref['title'] = $ref[2][0];
            $ref['desc'] =  XbrefmanGeneral::getNameValue('desc',$ref[3][0]);
            $ref['trig'] =  XbrefmanGeneral::getNameValue('trig',$ref[3][0]);
            $ref['disp'] =  XbrefmanGeneral::getNameValue('disp',$ref[3][0]);
            $ref['text'] =  preg_replace('!<sup.+?<\/sup>!','',$ref[4][0]);
            $ref['text'] = trim(strip_tags($ref['text']));
            $endcontext = $ref[0][1];
            $pretext = substr($content,0,$endcontext);     
            $pretext = preg_replace('!<sup.+?<\/sup>!','',$pretext);
            $pretext = strip_tags($pretext);
            $pretext =  preg_replace('!{(.*?)}!', '', $pretext);
            $ref['pos'] =  str_word_count($pretext);
            if (strlen($pretext)>40) {
                $pretext = substr($pretext,-40);
                $pretext = substr($pretext,strpos($pretext,' ')+1,strlen($pretext));
            }
            $ref['context'] = $pretext;
            $ref['num'] = 0;
            $refs[] = $ref;
        }
        return $refs;
    }
    
    
    function textDeleteShortcodes($ids) {
        $articles = array();
        $cnts = 0;
        if (!is_array($ids)) {
            $ids =explode(',',$ids);
        }
        $db = Factory::getDbo();
        foreach ($ids as $id) {
            $id = explode('-',$id);
            $artid = $id[0];
            $refno = $id[1];
            if (!key_exists($artid, $articles)) {
                //get article
                $query = $db->getQuery(true);
                $query->select($db->quoteName(array('id','title','introtext','fulltext')));
                $query->from('#__content');
                $query->where('id = '.$artid);
                $db->setQuery($query);
                $articles[$artid] = $db->loadObject();
            }
            $article = $articles[$artid];
            $content = $article->introtext.'{{READMORE}}'.$article->fulltext;
            //remove all highlights
            $refs = $this->getTextRefs($content);
            $ref = $refs[$refno];
            $target = $ref['all'];
            $text = $ref['text'];
            $cnt = 0;
//            $content = preg_replace('!'.$target.'!', $text, $content,-1, $cnt);
            $content = str_replace($target,$text,$content,$cnt);
            if ($cnt) {
                //now strip empty highlights
                $content = preg_replace('!<span class="xbshowref".*?><\/span>!', '', $content);
                //or reinstate highlights if all removed
                $article->introtext = strstr($content,'{{READMORE}}',true);
                $article->fulltext = substr($content,strpos($content,'{{READMORE}}')+12);
                if (XbrefmanHelper::saveArticleContent($article)) {
                    $cnts += $cnt;
                }
            }
            //remove empty highlight code
            
        }
        $message = Text::_('XBREFMAN_NO_CHANGES');
        if ($cnts) {
            $this->cleanCache();
            $message =  $cnts.' '.Text::_('XBREFMAN_REFS_REMOVED');
        }
        Factory::getApplication()->enqueueMessage($message);
        return;        
    }
    
    
    function texts2Tags($ids, $parent_tag = 1) {
        Factory::getApplication()->setUserState('parent_tag', $parent_tag);
        
        $articles = $this->getItems();
        $cnts = 0;
        $message = '';
        if (!is_array($ids)) {
            $ids =explode(',',$ids);
        }
        foreach ($ids as $id) {
            $id = explode('-',$id);
            $artid = $id[0];
            $refno = $id[1];
            //get the refs
            $i = array_search($artid, array_column($articles, 'id'));
            if ($i !== false) {
                $article = $articles[$i];
                $ref = $article->textrefs[$refno];
                $tagdata = array(
                    'title' => $ref['title'],
                    'description' => $ref['desc'],
                    'parent_id' => $parent_tag,
                    'published' => 1
                 );
                $newid = XbrefmanHelper::createTag($tagdata);
                if ($newid>0) {
                    //replace the ref
                    $newsc = '{xbref tag="'.$newid.'" ';
                    if ($ref['disp'] != '') {
                        $newsc .= 'disp="'.$ref['disp'].'" ';
                    }
                    if ($ref['trig'] != '') {
                        $newsc .= 'trig="'.$ref['trig'].'" ';
                    }
                    $newsc .= '}'.$ref[4][0]; // use the original not the cleaned one
                    //ideally clean the <sup>[N]</sup> with the newid
                    $newsc .= '{/xbref}';
                    $target = $ref['all'];
                    $cnt = 0;
                    //            $content = preg_replace('!'.$target.'!', $text, $content,-1, $cnt);
                    $content = str_replace($target,$newsc,$article->content,$cnt);
                    if ($cnt) {
                        $article->introtext = strstr($content,'{{READMORE}}',true);
                        $article->fulltext = substr($content,strpos($content,'{{READMORE}}')+12);
                        if (XbrefmanHelper::saveArticleContent($article)) {
                            $message .= 'Text ref '.implode('-',$id).' converted to tag="'.$newid.'"<br />';
                            $cnts += $cnt;
                        }
                    }   
                }
            }
        }        
        if ($message != '') Factory::getApplication()->enqueueMessage($message,'Info');
    }
    
}
