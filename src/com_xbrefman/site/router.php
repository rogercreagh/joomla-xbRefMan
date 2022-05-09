<?php
/*******
 * @package xbRefMan Component
 * @version 0.7.7 1st April 2022
 * @filesource site/router.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;

class XbrefmanRouter extends JComponentRouterBase {
    
    public function build(&$query)
    {
        $segments = array();
        if (isset($query['view']))
        {
            $segments[] = $query['view'];
            unset($query['view']);
        }
        if (isset($query['id']))
        {
              $segments[] = $query['id'];
                unset($query['id']);
        }
        if (isset($query['key'])) {
            $key = $query['key'];
            $db = Factory::getDbo();
            $qry = $db->getQuery(true);
            $kid = substr($key,strpos($key,'-')+1);
            $type = substr($key,0,strpos($key,'-'));
            $segments[] = $type;
            if ($type == 'tag') {
                $qry->select('alias')->from('#__tags')->where('id = '.$kid);
                $db->setQuery($qry);
                $alias = $db->loadResult();
                if ($alias) {
                    $segments[] = $alias;
                }
            } elseif ($type == 'weblink') {
                $qry->select('alias')->from('#__weblinks')->where('id = '.$kid);
                $db->setQuery($qry);
                $alias = $db->loadResult();
                if ($alias) {
                    $segments[] = $alias;
                }
            } elseif ($type == 'text') {
//                $artid = substr($key, strrpos($kid,'-')+1);
//                $alias = substr($key, 0, strrpos($kid,'-'));
                $segments[] = $kid;
//                $qry->select('alias')->from('#__content')->where('id = '.$artid);
//                $db->setQuery($qry);
//                $artalias = $db->loadResult();
//                if ($artalias) {
 //                   $segments[] = $artalias;
//                }
            }
            unset($query['key']);
        }
        return $segments;
    }
    
    public function parse(&$segments)
    {
        $vars = array();
        
        $db = Factory::getDbo();
        $qry = $db->getQuery(true);
//        $qry->select('id');
        switch($segments[0])
        {
            case 'footnotes':
                $vars['view'] = 'footnotes';
                break;
            case 'references':
                $vars['view'] = 'references';
                break;
            case 'reference':
                $vars['view'] = 'reference';
                //reference/type/alias
                $type = $segments[1];
                switch ($type) {
                    case 'tag':
                        //reference/tag/tagalias
                        $db = Factory::getDbo();
                        $qry = $db->getQuery(true);
                        $qry->select('id')->from('#__tags');
                        $qry->where('alias = ' . $db->quote($segments[2]));
                        $db->setQuery($qry);
                        $id = $db->loadResult();
                        if(!empty($id)) {
                            $vars['key'] = 'tag-'.$id;
                        } else {
                            $vars['view']='references'; //id doesn't exist so fallback to refs page
                        }
                        break;
                    case 'weblink':
                        //reference/weblink/weblinkalias
                        $db = Factory::getDbo();
                        $qry = $db->getQuery(true);
                        $qry->select('id')->from('#__weblinks');
                        $qry->where('alias = ' . $db->quote($segments[2]));
                        $db->setQuery($qry);
                        $id = $db->loadResult();
                        if(!empty($id)) {
                            $vars['key'] = 'weblink-'.$id;
                        } else {
                            $vars['view']='references'; //id doesn't exist so fallback to refs page
                        }
                        break;
                    case 'text':
//                        $db = Factory::getDbo();
//                        $qry = $db->getQuery(true);
//                        $qry->select('id')->from('#__content');
//                        $qry->where('alias = ' . $db->quote($segments[2]));
//                        $db->setQuery($qry);
//                        $id = $db->loadResult();
//                        if(!empty($id)) {
                            //reference/text/titlealias/artalias
                            $vars['key'] = 'text-'.$segments[2]; //.'-'.$id;
//                        } else {
 //                           $vars['view']='references';
//                        }
                        break;
                }
                break;
        }
        
        return $vars;
    }
    	
	public function preprocess($query)
	{
		return $query;
	}
	
}
