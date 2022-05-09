<?php 
/*******
 * @package xbRefMan Component
 * @version 0.9.2.1 23rd April 2022
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
/**
 * This gets a list of all references with the articles that use them
 * Details of each reference including a list of articles using it
 * Sort by reftitle (default)
 * Search by reftitle, refdesc
 * Filter by refdisp, reftagparent, artcat, arttag, 
 * refdisp filter will be pop+both|foot+both showing all refs where at least one article uses disp type
 * reftagparent will show only tagrefs which are child of selected tag, linkrefs which are tagged with a child of selected tag
 * for articles list we need id, title, catid, cattitle, content, tags
 * 
 */
class XbrefmanModelReferences extends JModelList {
    
    
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array ('title', 'a.title',
                'artcatid', 'a.catid', 'artcat_id',
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
        $app->setUserState('artcatid', '');
        $this->setState('categoryId',$categoryId);
        $tagId = $app->getUserStateFromRequest('tagid', 'tagid','');
        $app->setUserState('tagid', '');
        $this->setState('tagId',$tagId);
        
        $refType = $app->getUserStateFromRequest('reftype', 'reftype','');
        $app->setUserState('refType', '');
        $this->setState('reftype',$refType);
        $refType = $app->getUserStateFromRequest('refdisp', 'refdisp','');
        $app->setUserState('refType', '');
        $this->setState('reftype',$refType);
        $refType = $app->getUserStateFromRequest('reftag', 'reftag','');
        $app->setUserState('refType', '');
        $this->setState('reftype',$refType);
        
        parent::populateState($ordering, $direction);
        
        //pagination limit
        $limit = $this->getUserStateFromRequest($this->context.'.limit', 'limit', 25 );
        $this->setState('limit', $limit);
        $this->setState('refs.limit', $limit);
        $limitstart = $app->getUserStateFromRequest('limitstart', 'limitstart', $app->get('start'));
        $this->setState('refs.start', $limitstart);
        
    }
    
    protected function getListQuery() {
        //this will just get all articles with xbrefs. loading of refs is done in getItems()
        $targ = '{xbref ';
        
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as artid, a.title as arttitle, a.catid as artcatid, c.title as category_title');
        $query->select("CONCAT(a.introtext,' ', a.fulltext) as artcontent");
        $query->from($db->quoteName('#__content').' as a');
        $query->leftJoin($db->quoteName('#__categories').' AS c ON c.id = a.catid');
        $query->where('(CONCAT( `introtext`," ",`fulltext`)) LIKE \'%'.$targ.'%\' ');
        
        //only show published articles
            $query->where('state = 1');
//         $query->group('a.id');
        return $query;
        
        
    }
    
    public function getItems() {
        //get search and filter params
        $search = strtolower($this->getState('filter.search'));
        $tonly = false;
        if (substr($search,0,2)=='t:') {
            $search = substr($search,2);
            $tonly = true;
        }
        
        $refs = array(); //entries keyed by refkey
        //get a list of articles with references with filtering by artcat arttag
        $items  = parent::getItems();
        //get all the refs in each article, they will be ordered by article title - not what we want
        $tagsHelper = new TagsHelper;
        $app = Factory::getApplication();
        foreach ($items as $i=>$art) {
//           $item->tags = $tagsHelper->getItemTags('com_content.article' , $item->id);
            //get all the refs for each article
            $filter = '';
            $refdisp = $app->getUserStateFromRequest('refdisp', 'refdisp','');
            $app->setUserState('refdisp', '');
            if ($refdisp=='') {
                $refdisp = $this->getState('filter.refdisp','any');
            }
            if ($refdisp != 'any') {
                $filter = 'disp="'.$refdisp.'"';
            }
            
            $tagfilter = '';
            //check for filter by type & tag
            
            $artrefs = XbrefmanHelper::getArticleRefs($art->artcontent, $filter, $tagfilter);
            //now apply pagniantion
            //...
            //get start and limit
            //drop unwanted items from artrefs
        //merge into $refs adding to articles array if already exists
            foreach ($artrefs as $artref) {
                //filter by search reftitle/desc
                $schinstr = $artref['title'];
                if (!$tonly) $schinstr .= $artref['desc'];
                if (($search=='') || (substr_count(strtolower($schinstr), $search)>0)) {
                    //...
                    $artrefinfo = array ('idx'=>$artref['idx'],'disp'=>$artref['disp'], 'trig'=>$artref['trig'], 
                        'context'=>$artref['context'], 'pos'=>$artref['pos'], 'text'=>$artref['text'],'num'=>$artref['num']);
                    $refarticle = array('artid'=>$art->artid,'arttitle'=>$art->arttitle,'artrefinfos'=>array()); //, 'artrefinfo'=>$artrefinfo);
                    
                    if (array_key_exists($artref['refkey'], $refs)) {
                    // we've already got this ref in $refs so check if this is a new artref
                        if (array_key_exists($art->artid,$refs[$artref['refkey']]['articles'])) {
                            // new artrefinfo to add to $refs[refkey][articles[artid]]
                            $refs[$artref['refkey']]['articles'][$art->artid]['artrefinfos'][] = $artrefinfo;
                        } else {
                            //new article to add to refs[refkey]
                            $refs[$artref['refkey']]['articles'][$art->artid] = $refarticle;
                            $refs[$artref['refkey']]['articles'][$art->artid]['artrefinfos'][] = $artrefinfo;
                        } //endif artref exists
                    } else {
                        //this is a new ref to be added complete to refs
                        $newref = array();
                        $newref['title'] = $artref['title'];
                        $newref['desc'] = $artref['desc'];
                        $newref['url'] = $artref['url'];
                        $newref['reftype'] = $artref['type'];
                        $refkey = $artref['refkey'];
                        if ($artref['type']=='text') {
                            $refkey .= '-'.$art->artid; //appending article id onto end of text title
                        }
                        $newref['refkey'] = $refkey; 
                        $newref['refid'] = $artref['refid'];
                        $newref['articles'] = array();
                        $newref['articles'][$art->artid] = $refarticle;
                        $newref['articles'][$art->artid]['artrefinfos'][] = $artrefinfo;
                        $refs[$artref['refkey']] = $newref;
                    } //endif ref exists                       
                } //endif filter
            } //endforeach artref
        } //endforeach item (article) 
        // now we need to do ordering - always by title but can be asc or desc
        global $orderDirn;
        $orderDirn = $this->state->get('list.direction', 'ASC');
        uasort($refs, function($a, $b) { 
            global $orderDirn; 
            if ($orderDirn=='DESC') { 
                return strtolower($b['title']) <=> strtolower($a['title']); 
            } 
            return  strtolower($a['title']) <=> strtolower($b['title']);
        } );
        return (object) $refs;
    }
    
}
