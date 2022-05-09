<?php 
/*******
 * @package xbRefMan Component
 * @version 0.8.2 8th April 2022
 * @filesource site/models/reference.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Uri\Uri;

class XbrefmanModelReference extends JModelItem {
    
    protected $item;
    
    protected function populateState($ordering = null, $direction = null) {
        
        $app = Factory::getApplication('site');
        // Load state from the request.
        $key = $app->input->getString('key','');
        if (!$key) {
            $key = null;
            $type = $app->input->getString('type','');
            switch ($type) {
                case 'tag':
                    $id = $app->input->getInt('tid',0);
                    if ($id) {
                        $key = 'tag-'.$id;
                    }
                    break;
                case 'weblink':
                    $id = $app->input->getInt('wid',0);
                    if ($id) {
                        $key = 'weblink-'.$id;
                    }
                    break;
                case 'text':
                    $id = $app->input->getString('xid','');
                    if ($id) {
                        $key = 'text-'.$id; //.'-'.$app->input->getInt('artid',0);
                    }
                    //we need an article id as well
                    break;
            }           
        }
        $this->setState('reference.key', $key);
        // Load the parameters.
        $this->setState('params', $app->getParams());
        
        parent::populateState();
        
    }
 
    public function getItem($key = null) {
        
        if (!isset($this->item) || !is_null($key)) {
            $key    = (is_null($key)) ? $this->getState('reference.key') : $key;
            $id = substr($key,strpos($key,'-')+1);
            $type = substr($key,0,strpos($key,'-'));
            if (array_search($type, array('tag','weblink','text'))===false) {
                return false;
            }
            if (($type =='tag') || ($type=='weblink')) {
                $id = intval($id);
                if (!$id){ 
                    return false;
                }
            }
            switch ($type) {
                case 'tag':
                    $this->item = $this->getTagRef($id);
                    break;
                case 'weblink':
                    $this->item = $this->getWeblinkRef($id);
                    break;
                case 'text':
                    $this->item = $this->getTextRef($id);
                    break;
                default:
                    return false;
                break;
            }
        }
        return $this->item;
    }
    
    private function getTagRef($id) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('a.id','a.parent_id','a.path','a.title','a.alias','a.description','a.params')),
            array('id','parent_id','path','title','alias','description','params'));
        $query->from('#__tags AS a');
        $query->where('a.id = '.$id);
        //?only get if published
        $db->setQuery($query);
        if ($item = $db->loadObject()) {
            // Load the JSON string
            $params = new Registry;
            $params->loadString($item->params, 'JSON');
            $item->params = $params;
            
            // Merge global params with item params
            $params = clone $this->getState('params');
            $params->merge($item->params);
            $item->params = $params;
            
            $item->articles = XbrefmanHelper::getRefArticles('tag', $id);
            $item->type = 'tag';
            return $item;
        }  
        return false;
    }
     
    private function getWeblinkRef($id) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('a.id','a.catid','a.title','a.url','a.alias','a.description','a.params')),
            array('id','catid','title','alias','url','description','params'));
        $query->from('#__weblinks AS a');
        $query->where('a.id = '.$id);
        //?only get if published
        $db->setQuery($query);
        if ($item = $db->loadObject()) {
            // Load the JSON string
            $params = new Registry;
            $params->loadString($item->params, 'JSON');
            $item->params = $params;
            
            // Merge global params with item params
            $params = clone $this->getState('params');
            $params->merge($item->params);
            $item->params = $params;
            
            $item->articles = XbrefmanHelper::getRefArticles('link', $id);
            $item->type = 'weblink';
            return $item;
        }
        return false;
    }
    
    private function getTextRef($key) {
        //parse the id to get articleid and reftitle
        $item=new stdClass();
        
        $item->articles = XbrefmanHelper::getRefArticles('text', $key);
        //        Factory::getApplication()->enqueueMessage('<pre>'.print_r($item, true).'</pre>');
        $item->type = 'text';
        $item->title =$key;
        if ($item->articles) {
            $item->description=$item->articles[0]->refs[0]['desc'];
            //could test here for alternative descriptions using same title
            return $item;
        }
        $item->description='<i>'.Text::_('XBREFMAN_REF_NOT_FOUND').'</i>';
        return $item;
    }

}