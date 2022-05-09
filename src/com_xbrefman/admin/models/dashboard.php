<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.5 2nd May 2022
 * @filesource admin/models/dashboard.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Registry\Registry;

class XbrefmanModelDashboard extends JModelList {
    
    public function __construct() {
        
        parent::__construct();
    }
    
    public function getClient() {
        $result = array();
        $client = Factory::getApplication()->client;
        $class = new ReflectionClass('Joomla\Application\Web\WebClient');
        $constants = array_flip($class->getConstants());
        
        $result['browser'] = $constants[$client->browser].' '.$client->browserVersion;
        $result['platform'] = $constants[$client->platform].($client->mobile ? ' (mobile)' : '');
        $result['mobile'] = $client->mobile;
        return $result;
    }
    
    public function getArticleCounts() {
        $artcnts = array('articles'=>0, 'tags'=>0, 'links'=>0, 'text'=>0, 'pop'=>0, 'foot'=>0, 'both'=>0, 'def'=>0);
        $articles = XbrefmanHelper::findInArticles('{xbref ');
        $artcnts['articles'] = count($articles);
        if ($artcnts > 0) {            
            foreach ($articles as $art) {
                $articleText = $art['introtext'].$art['fulltext'];
                $matches = array();
                preg_match_all('!{xbref (.*?)}(.*?){/xbref}!', $articleText, $matches, PREG_SET_ORDER);
                // or should we look for tag="!0" link="!0 and text=""
                foreach ($matches as $ref) {
                    // $ref[1] contains the text {xbref text }, $ref[2] contains the content {xbref ...}content{/xbref}
                    $ref[1] .= ' '; //make sure we have a space at the end - getNameValue() needs it
                    if (XbrefmanGeneral::getNameValue('tag',$ref[1])>0) {
                        $artcnts['tags'] ++;
                    } elseif (XbrefmanGeneral::getNameValue('link',$ref[1])>0 ) {
                        $artcnts['links'] ++;
                    } elseif (XbrefmanGeneral::getNameValue('title',$ref[1])) {
                        $artcnts['text'] ++;
                    }
                    if (XbrefmanGeneral::getNameValue('disp',$ref[1])=='pop') {
                        $artcnts['pop'] ++;
                    } elseif (XbrefmanGeneral::getNameValue('disp',$ref[1])=='foot' ) {
                        $artcnts['foot'] ++;
                    } elseif (XbrefmanGeneral::getNameValue('disp',$ref[1])=='both') {
                        $artcnts['both'] ++;
                    } else {
                        //using default setting from content params
                        $artcnts['def'] ++;
                    }
                } //end foreach $ref
            } //end foreach $art
        } //endif $artcnts>0
        return $artcnts;
    }

    public function getReferenceCounts() {
        $refcnts = array('refs'=>0, 'tags'=>0, 'tagarts'=>0, 'badtags'=>0, 'badtagarts'=>array(),
            'links'=>0, 'linkarts'=>0, 'badlinks'=>0, 'badlinkarts'=>array(), 
            'text'=>0, 'textarts'=>0, 'textdupes'=>array(), 'dupetext'=>0 ); //, 'exttags'=>0, 'extlinks'=>0);
        $articles = XbrefmanHelper::findInArticles('{xbref ');
        $tagids = array();
        $tagarts = array();
        $badtagids = array();
        $linkids = array();
        $linkarts = array();
        $badlinkids = array();
        $texttits = array();
        $textarts = array();
        foreach ($articles as $art) {
            $articleText = $art['introtext'].$art['fulltext'];
            $matches = array();
            preg_match_all('!{xbref (.*?)}(.*?){/xbref}!', $articleText, $matches, PREG_SET_ORDER);
            // or should we look for tag="!0" link="!0 and text=""
            foreach ($matches as $ref) {
                // $ref[1] contains the text {xbref text }, $ref[2] contains the content {xbref ...}content{/xbref}
                $ref[1] .= ' '; //make sure we have a space at the end - getNameValue() needs it
                $thisid = XbrefmanGeneral::getNameValue('tag',$ref[1]);
                if ($thisid>0) {
                    if (XbrefmanHelper::checkId($thisid, '#__tags')) {
                        $tagids[$thisid] = $thisid;
                        $tagarts[$art['id']] = $art['title'];
                    } else {
                        $badtagids[$thisid] = $thisid;
                        $refcnts['badtagarts'][$art['id']] = $art['title'];
                    }
                } else {
                    $thisid = XbrefmanGeneral::getNameValue('link',$ref[1]);
                    if ($thisid>0) {
                        if (XbrefmanHelper::checkId($thisid, '#__weblinks')) {
                            $linkids[$thisid] = $thisid;
                            $linkarts[$art['id']] = $art['title'];
                        } else {
                            $badlinkids[$thisid] = $thisid;
                            $refcnts['badlinkarts'][$art['id']] = $art['title'];
                        }
                    } elseif (XbrefmanGeneral::getNameValue('title',$ref[1])) {
                        // just count text refs as all are unique
                        $refcnts['text'] ++;
                        $textarts[$art['id']] = $art['title'];
                        $txttit = XbrefmanGeneral::getNameValue('title',$ref[1]);
                        $texttits[] = $txttit;
                    }
                }
            } //end foreach $ref
        } //end foreach $art
            
        $refcnts['tags'] = count($tagids);
        $refcnts['tagarts'] = count($tagarts);
        $refcnts['badtags'] = count($badtagids);
        $refcnts['links'] = count($linkids);
        $refcnts['linkarts'] = count($linkarts);
        $refcnts['badlinks'] = count($badlinkids);
        $refcnts['textarts'] = count($textarts);
        
        $refcnts['dupetext'] = $refcnts['text'] - count(array_unique($texttits));
        
        // total no of distinct refs used
        $refcnts['refs'] = $refcnts['tags'] + $refcnts['links'] + $refcnts['text'];
        return $refcnts;
    }
    
    public function getSelectableTagNames() {
        return $this->selectableTags();
    }
    
    public function getSelectableLinkNames() {
        return $this->selectableTags('link');
    }
    
    public function getSelectableLinkCats() {
        $catlist = '';
        $btnplugin = PluginHelper::getPlugin('editors-xtd','xbrefsbtn');
        $btnpluginParams = new Registry($btnplugin->params);
        $catids = $btnpluginParams->get('linkcatlist','');  
        if ($catids) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $catidlist = implode(',',$catids);
                $query->clear();
                $query->select('id, title')->from('#__categories');
                $query->where('id IN ('.$catidlist.')');
                $db->setQuery($query);
                $cats = $db->loadAssocList('id','title');
                foreach ($cats as $cat) {
                    $catlist .= '<span class="label label-success">'.$cat.'</span> ';
                }
        }
        return $catlist;
    }
    
    /**
     * @name getSelectableTags()
     * @desc gets names of tags and children in selectable tags list
     * @param string $selected - 'tag' or'link'
     * @return array containing arrays of names for each parent in the list
     */
    function selectableTags($type = '') {
        $taglist = '';
//        $seltagsids = array();
//        $seltagsnames = array();
        $tagsHelper = new TagsHelper();
        $btnplugin = PluginHelper::getPlugin('editors-xtd','xbrefsbtn');
        $btnpluginParams = new Registry($btnplugin->params);
        $listedtags = $btnpluginParams->get($type.'taglist',array());
        if (count($listedtags)){
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $tagidlist = implode(',',$listedtags);
            $query->clear();
            $query->select('id, title')->from('#__tags');
            $query->where('id IN ('.$tagidlist.')');
            $db->setQuery($query);
            $tags = $db->loadAssocList('id');
            foreach ($tags as $tag) {
                $taglist .= '<span class="label label-info">'.$tag['title'].'</span> ';
                if ($btnpluginParams->get($type.'usechild',array())==1) {
                     $kids = count($tagsHelper->getTagTreeArray($tag['id']))-1 ;
                     if ($kids) {
                         $taglist .= '<i>and '.$kids.' child tags</i>';
                     }
            }
            $taglist .= '<br />';
            }
        }
        return $taglist;
    }

}
