<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.3.3 25th April 2022
 * @filesource admin/helpers/xbrefman.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );


use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Helper\TagsHelper;

class XbrefmanHelper extends ContentHelper {
    
    public static function getActions($component = 'com_xbrefman', $section = 'component', $categoryid = 0) {
        
        $user 	= Factory::getUser();
        $result = new CMSObject;
        if (empty($categoryid)) {
            $assetName = $component;
            $level = $section;
        } else {
            $assetName = $component.'.category.'.(int) $categoryid;
            $level = 'category';
        }
        $actions = Access::getActionsFromFile(JPATH_ADMINISTRATOR . '/components/com_xbrefman/access.xml');
        foreach ($actions as $action) {
            $result->set($action->name, $user->authorise($action->name, $assetName));
        }
        return $result;
    }
    
    public static function addSubmenu($vName = 'dashboard') {
        $weblinksOk = XbrefmanGeneral::extensionStatus('com_weblinks','component');
        JHtmlSidebar::addEntry(
            Text::_('XBREFMAN_ICONMENU_DASHBOARD'),
            'index.php?option=com_xbrefman&view=dashboard',
            $vName == 'dashboard'
            );
        JHtmlSidebar::addEntry(
            Text::_('XBREFMAN_ICONMENU_ARTICLES'),
            'index.php?option=com_xbrefman&view=articles',
            $vName == 'articles'
            );
        JHtmlSidebar::addEntry(
            Text::_('XBREFMAN_ICONMENU_TAGREFS'),
            'index.php?option=com_xbrefman&view=tagrefs',
            $vName == 'tagrefs'
            );
        if ($weblinksOk) {
            JHtmlSidebar::addEntry(
                Text::_('XBREFMAN_ICONMENU_LINKREFS'),
                'index.php?option=com_xbrefman&view=linkrefs',
                $vName == 'linkrefs'
                );
        }
        JHtmlSidebar::addEntry(
            Text::_('XBREFMAN_ICONMENU_TEXTREFS'),
            'index.php?option=com_xbrefman&view=textrefs&layout=default',
            $vName == 'textrefs'
            );
        JHtmlSidebar::addEntry(
            Text::_('XBREFMAN_OTHER_EXTS'),
            '',
            $vName == ''
            );
        JHtmlSidebar::addEntry(
            Text::_('XBREFMAN_ICONMENU_ALLTAGS'),
            'index.php?option=com_tags',
            $vName == 'alltags'
            );
        if ($weblinksOk) {
            JHtmlSidebar::addEntry(
                Text::_('XBREFMAN_ICONMENU_ALLWEBLINKS'),
                'index.php?option=com_weblinks',
                $vName == 'allweblinks'
                );           
        }
        JHtmlSidebar::addEntry(
            Text::_('XBREFMAN_ICONMENU_OPTIONS'),
            'index.php?option=com_config&view=component&component=com_xbrefman',
            $vName == 'options'
            );
    }
    
    public static function getExtManifest($type,$element,$folder=''){
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('manifest_cache')
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type')." = ".$db->quote($type). 'AND '.$db->quoteName('element')." = ".$db->quote($element));
        if ($type == 'plugin') {
            $query->where($db->quoteName('folder')." = ".$db->quote($folder));
        }
        $db->setQuery($query);
        $res = $db->loadResult();
        return $res;
    }
    
    public static function saveArticleContent($article) {
        //TODO checkout and checkin around saving
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->update('#__content AS a');
        $query->set('a.introtext = ' . $db->quote($article->introtext));
        $query->set('a.fulltext = ' . $db->quote($article->fulltext));
        $query->where('a.id = '.$article->id);
        try {
            $db->setQuery($query);
            $res = $db->execute();
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Db execute error: '.$e->getMessage(),'Error');
            //                throw new Exception($e->getMessage(), 500, $e);
        }
        return $res;
    }
    
    public static function createTag(array $tagdata) {
        Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
        $app = Factory::getApplication();
        
        //check if alias already exists (dupe id)
        $alias = $tagdata['title'];
        $alias = ApplicationHelper::stringURLSafe($alias);        
        if (trim(str_replace('-', '', $alias)) == '') {
            $alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id')
        ->from($db->quoteName('#__tags'))
        ->where($db->quoteName('alias').' = '.$db->quote($alias));
        $db->setQuery($query);
        $id = $db->loadResult();
        if ($db->loadResult()>0){
            Factory::getApplication()->enqueueMessage('Tag with alias '.$alias.' already exists with id '.$id,'Warning');
            return $id;
        }
        
        $table = Table::getInstance('Tag', 'TagsTable', array());
                       
        // Bind data
        if (!$table->bind($tagdata)) {
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        // Check the data.
        if (!$table->check()) {
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        $table->setLocation($tagdata['parent_id'], 'last-child');      
        // Store the data.
        if (!$table->store()){
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        if (!$table->rebuildPath($table->id)) {
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        $app->enqueueMessage('New tag '.$tagdata['title'].' created with id '.$table->id);
        return $table->id;
        
    }
    
    /**TODO
     * do Highlights and noHilights need reworking so that they only operate on the passed article text
     * return modified text
     * and do not save to database. If required call save seprately after these
     * make second parameter &$cnt so it is updated with change count and cqn be used by caller
     */
    public static function doHighlights($article, $domess = true) {
        if (strpos($article->introtext.$article->fulltext,'<span class="xbshowref') !== false) {
            self::noHighlights($article,false);
        }
        //surely we need to reload the article text as has just been saved without highlights
        $icnt = 0; $fcnt = 0;
        $match = '!{xbref .*?}|{/xbref}|{xbref-here.*?}!';
        $spanon = '<span class="xbshowref" style="background-color: #fff8db;">'; //TODO replace colour with btn param
        $article->introtext = preg_replace($match, $spanon.'${0}</span>', $article->introtext, -1, $icnt);
        if ($article->fulltext!='') {
            $article->fulltext = preg_replace($match, $spanon.'${0}</span>', $article->fulltext, -1, $fcnt);
        }
        $message = 'No shortcodes to highligh';
        if (($fcnt+$icnt)>0) {
            $res = self::saveArticleContent($article);
            if (!$res) {
                $message = 'No changes saved';
                $icnt = 0; $fcnt = 0;
            } else {
                $message = ($fcnt+$icnt).' shortcodes highlighted';
            }
        }
        if ($domess) {
            Factory::getApplication()->enqueueMessage($message,'Info');
        }
        return $icnt+$fcnt;       
    }
    
    public static function noHighlights($article, $domess = true) {
        $icnt = 0; $fcnt = 0;
        if (strpos($article->introtext.$article->fulltext,'<span class="xbshowref') === false) {
            $message = 'No shortcode highlights found to remove';
        } else {
            $article->introtext = preg_replace('!<span class="xbshowref".*?>(.*?)<\/span>!', '${1}', $article->introtext, -1, $icnt);
            if ($article->fulltext!='') {
                $article->fulltext = preg_replace('!<span class="xbshowref".*?>(.*?)<\/span>!', '${1}', $article->fulltext, -1, $fcnt);
            }
            $message = 'No highlights to remove';
            if (($fcnt+$icnt)>0) {
                $res = self::saveArticleContent($article);
                if (!$res) {
                    $message = 'No changes saved';
                    $icnt = 0; $fcnt = 0;
                } else {
                    $message = ($fcnt+$icnt).' highlights removed';
                }
            }
        }
        if ($domess) {
            Factory::getApplication()->enqueueMessage($message,'Info');
        }
        return $icnt+$fcnt;
        
    }

    /**
     * @name findInArticles()
     * @desc returns an array of articles containing specified text in either intro or full text
     * @example only used in admin/models/dahboard
     * @param string $targ - the string to match
     * @return assocArray|null - assoc array keyed by id of assoc array of rows
     */
    public static function findInArticles(string $targ) {
        $articles = array();
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('id','title','introtext','fulltext')));
        $query->from($db->quoteName('#__content'));
        $query->where($db->quoteName('introtext') . ' LIKE \'%'.$targ.'%\'');
        $query->orWhere($db->quoteName('fulltext') . ' LIKE \'%'.$targ.'%\'');
        $db->setQuery($query);
        $articles = $db->loadAssocList('id');
        return $articles;
    }
    
    /** 
     * @name checkId()
     * @desc returns id or null if not found in table
     * @param unknown $id
     * @param unknown $table
     * @return mixed|void|NULL
     */
    /**/
    public static function checkId($id, $table) {
        if (self::tableExists($table)) {
            $db = Factory::getDbo();
            $query = 'SELECT id FROM '.$table.' WHERE id = '.$id;
            $db->setQuery($query);
            return $db->loadResult();
        }
        return false;
    }
    /**/
    
    /** ONLY USED IN checkId
     * @name tableExists()
     * @param unknown $tname
     * @return mixed|void|NULL
     */
    /**/
    private static function tableExists($tname) {
        $db = Factory::getDbo();
        $config = Factory::getConfig();
        $dbname = $config->get('db');
        $prefix = $db->getPrefix();
        $pname = $prefix.str_replace('#__', '', $tname);
        $db->setQuery("SHOW TABLES FROM " . $db->qn($dbname) . " WHERE " . $db->qn("Tables_in_$dbname") . " = " . $db->q($pname));
        return $db->loadResult();
    }
    /**/

    /** 
     * @name stripShortcodes()
     * @desc strips all shortcodes {} from text
     * @param unknown $articleText
     * @return mixed
     */
    public static function strip_shortcodes($articleText) {
        //to be extrended with option to leave {xbref untouched
        return  preg_replace('!{(.*?)}!', '', $articleText);
    }
    
    /** ONLY USED IN ARTICLE VIEW default
     * @name wordCount()
     * @desc given some text return count of words excluding shortcodes {} and html tags
     * @param unknown $text
     * @return mixed
     */
    public static function wordCount($text) {
        $text = preg_replace('!{(.*?)}!', '', $text);
        return str_word_count(strip_tags($text));
    }
    
    /** 
     * @name makeSummaryText
     * @desc returns a plain text version of the source trunctated at the first or last sentence within the specified length
     * @param string $source the string to make a summary from
     * @param int $len the maximum length of the summary
     * @param bool $first if true truncate at end of first sentence, else at the last sentence within the max length
     * @return string
     */
    public static function makeSummaryText(string $source, int $len=250, bool $first = true) {
        if ($len == 0 ) {$len = 100; $first = true; }
        //strip out any shortcodes
        $source =  preg_replace('!{(.*?)}!', '', $source);
        //strip any html and truncate to max length
        $source = strip_tags($source);
        $summary = HTMLHelper::_('string.truncate', $source, $len, true, false);
        //strip off ellipsis if present (we'll put it back at end)
        $hadellip = false;
        if (substr($summary,strlen($summary)-3) == '...') {
            $summary = substr($summary,0,strlen($summary)-3);
            $hadellip = true;
        }
        // get a version with '? ' and '! ' replaced by '. '
        $dotsonly = str_replace(array('! ','? '),'. ',$summary.' ');
        if ($first) {
            // look for first ". " as end of sentence
            $dot = strpos($dotsonly,'. ');
        } else {
            // look for last ". " as end of sentence
            $dot = strrpos($dotsonly,'. ');
        }
        // are we going to cut some more off?)
        if (($dot!==false) && ($dot < strlen($summary)-3)) {
            $hadellip = true;
        }
        if ($dot>3) {
            $summary = substr($summary,0, $dot+1);
        }
        if ($hadellip) {
            // put back ellipsis with a space
            $summary .= ' ...';
        }
        return $summary;
    }
    
    /** 
     * @name extStatusStr()
     * @desc returns a string reporting status of extension given ouput of extensionStatus() function above
     * @param unknown $ext
     * @return string
     */
    public static function extStatusStr($ext) {
        if ($ext === false) {
            return Text::_( 'XBREFMAN_NOT_INSTALLED' );
        } elseif ($ext === 0) {
            return Text::_( 'XBREFMAN_NOT_ENABLED' );
        }
        return Text::_( 'XBREFMAN_ENABLED' );;
    }
    
    /** 
     * @name getTitles()
     * @desc given an array of ids and a databse table returns the corresponding list of titles
     * @param array $ids
     * @param string $table
     * @param string $field
     * @return mixed|void|mixed[]|array
     */
    public static function getTitles(array $ids, string $table, $field='title') {
        if (count($ids)) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->select($db->quoteName($field));
            $query->from($db->quoteName($table));
            $idlist = implode(',',$ids);
            $query->where($db->quoteName('id').' IN ('.$idlist.')');
            $db->setQuery($query);
            return $db->loadColumn();
        }
        return array();
    }
    
    /** 
     * @name getTagsDetails()
     * @desc function to get id, title and description of tag
     * by default will only return published tags in path order
     * @param array $tagIds
     * @param string $order - column name to sort on (default no ordering)
     * @param int $pub - published state value (default 1 = published)
     * @return array
     */
    public static function getTagsDetails ( $tagids, string $order = 'path', int $pub = 1 ) {
        if (is_string($tagids)) {
            $tagids = explode(',',$tagids);
        }
        $tagsDetails = array();
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('id','title','description','path','published','level')));
        $query->from($db->quoteName('#__tags'));
        
        if (!empty($tagids)) {
            $tagids = ArrayHelper::toInteger($tagids);
            $query->where($db->quoteName('id') . ' IN (' . implode(',', $tagids) . ')');
        }
        $query->where($db->quoteName('published') .'='. $pub);
        if ($order) $query->order($order);
        $db->setQuery($query);
        $tagsDetails = $db->loadAssocList('id');
        return $tagsDetails;
    }
    
    /** 
     * @name getTagsChildren()
     * @desc given an array of tagids return array with ids of all descendents
     * @param array $tagids
     * @return array including the parent ids as well as children
     */
    public static function getTagsChildren($tagids) {
        if (is_string($tagids)) {
            $tagids = explode(',',$tagids);
        }
        $tagsHelper = new TagsHelper();
        $alltags = array();
        foreach ($tagids as $k)
        {
            $childidarray = array();
            $tagsHelper->getTagTreeArray($k, $childidarray);
            if (count($childidarray))
            {
                $alltags = array_merge($childidarray,$alltags);
            }
        }
        $alltags = array_unique($alltags);
        return $alltags;
    }
    
    
}