<?php
/*******
 * @package xbRefman Component
 * @version 1.0.0.0 10th May 2022
 * @filesource site/helpers/route.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class XbrefmanHelperRoute
{
	public static function &getItems() {
		static $items;
		
		// Get the menu items for this component.
		if (!isset($items)) {
			$component = ComponentHelper::getComponent('com_xbrefman');
			$items     = Factory::getApplication()->getMenu()->getItems('component_id', $component->id);			
			// If no items found, set to empty array.
			if (!$items) {
				$items = array();
			}
		}		
		return $items;
	}

	/**
	 * @name getFootnotesRoute
	 * @desc Get menu itemid footnotes view in default layout
	 * @param boolean $retstr if false return integer id, if true return return string with "&Itemid="
	 * @return string|int|NULL
	 */
	public static function getFootnotesRoute($retstr=false) {
		$items  = self::getItems();
		foreach ($items as $item) {
			if ((isset($item->query['view']) && $item->query['view'] === 'footnotes')
					&& ((empty($item->query['layout']) || $item->query['layout'] === 'default')) ) {
						return ($retstr)? '&Itemid='.$item->id : $item->id;
					}
		}
		return null;
	}
	
	/**
	 * @name getFootnotesLink
	 * @desc Get link to footnotes view
	 * @return string
	 */
	public static function getFootnotesLink() {
		$link = 'index.php?option=com_xbrefman';
		$items  = self::getItems();
		foreach ($items as $item) {
		    if ((isset($item->query['view']) && $item->query['view'] === 'footnotes') 
		        && ((empty($item->query['layout']) || $item->query['layout'] === 'default')) ) {
			        return $link.'&Itemid='.$item->id;              
			}
		}
		return $link.'&view=footnotes';
	}

	/**
	 * @name getReferencesRoute
	 * @desc Get menu itemid references view in default layout
	 * @param boolean $retstr if false return integer id, if true return return string with "&Itemid="
	 * @return string|int|NULL
	 */
	public static function getReferencesRoute($retstr=false) {
	    $items  = self::getItems();
	    foreach ($items as $item) {
	        if ((isset($item->query['view']) && $item->query['view'] === 'references')
	            && ((empty($item->query['layout']) || $item->query['layout'] === 'default')) ) {
	                return ($retstr)? '&Itemid='.$item->id : $item->id;
	            }
	    }
	    return null;
	}
	
	/**
	 * @name getReferencesLink
	 * @desc Get link to references view
	 * @return string
	 */
	public static function getReferencesLink() {
		$link = 'index.php?option=com_xbrefman';
		$items  = self::getItems();
		foreach ($items as $item) {
			if ((isset($item->query['view']) && $item->query['view'] === 'references')
					&& ((empty($item->query['layout']) || $item->query['layout'] === 'default')) ) {
						return $link.'&Itemid='.$item->id;
					}
		}
		return $link.'&view=references';
	}
	
	/**
	 * @name getReferenceRoute
	 * @desc returns the itemid for a menu item for track view with id  $fd, if not found returns menu id for a tracklist, if not found null
	 * @param int $fid
	 * @return int|string|NULL
	 */
	public static function getReferenceRoute($fid) {
	    $items  = self::getItems();
	    foreach ($items as $item) {
	        if (isset($item->query['view']) && $item->query['view'] === 'reference' && isset($item->query['id']) && $item->query['id'] == $fid ) {
	            return $item->id;
	        }
	    }
	    foreach ($items as $item) {
	        if (isset($item->query['view']) && $item->query['view'] === 'references' &&
	            (empty($item->query['layout']) || $item->query['layout'] === 'default')) {
	                return $item->id.'&view=reference&id='.$fid;
	            }
	    }
	    return null;
	}
	
	/**
	 * @name getReferenceLink
	 * @desc gets a complete link for a reference menu item either dedicated, or maplist menu or generic
	 * @param int $fid
	 * @return string
	 */
	public static function getReferenceLink($fid) {
		$link = 'index.php?option=com_xbrefman';
		$items  = self::getItems();
		foreach ($items as $item) {
			if (isset($item->query['view']) && $item->query['view'] === 'reference' && isset($item->query['id']) && $item->query['id'] == $fid ) {
				return $link.'&Itemid='.$item->id;
			}
		}
		foreach ($items as $item) {
			if (isset($item->query['view']) && $item->query['view'] === 'references' &&
					(empty($item->query['layout']) || $item->query['layout'] === 'default')) {
						return $link.'&Itemid='.$item->id.'&view=reference&id='.$fid;
					}
		}
		return $link.'&view=reference&id='.$fid;
	}
	
	
	public static function getArticleRoute($id) {
	    // better check if sef turned on
	    if (Factory::getApplication()->get('sef')>0){
	       //get the com_content integration settings
	        $comcontent = ComponentHelper::getComponent('com_content');
	        $artitems     = Factory::getApplication()->getMenu()->getItems('component_id', $comcontent->id);
	        if (!$artitems) {
	            return 'index.php?option=com_content&view=article&id='.$id;
	        }
	        // if there is a menu entry for the specific article we will use it as the url
	        foreach ($artitems as $item) {
	            if(($item->query['view']=='article') && ($item->query['id']==$id)) {
	                return 'index.php/'.$item->route;
	            }
	        }
	        // no direct menu entry for the article s we will use the fallback format
	        // which works whatever the com_content integration|routing settings (legacy/modern  with/without ids)
	        // first get the article alias
	        $db = Factory::getDbo();
	        $qry = $db->getQuery(true);
	        $qry->select('alias')->from('#__content')->where('id = ' . $db->quote($id));
	        $db->setQuery($qry);
	        if ($artalias = $db->loadResult()) {
	            return 'index.php/component/content/article/'.$id.'-'.$artalias;
	        }
	    }	
	    //no sef, use basic format
	    return 'index.php?option=com_content&view=article&id='.$id;
	}

	public static function getArticleCategoryRoute($id) {
	    // better check if sef turned on
	    if (Factory::getApplication()->get('sef')>0){
	        //get the com_content integration settings
	        $comcontent = ComponentHelper::getComponent('com_content');
	        $artitems     = Factory::getApplication()->getMenu()->getItems('component_id', $comcontent->id);
	        if (!$artitems) {
	            return 'index.php?option=com_content&view=category&id='.$id;
	        }
	        // if there is a menu entry for the specific article we will use it as the url
	        foreach ($artitems as $item) {
	            if(($item->query['view']=='category') && ($item->query['id']==$id)) {
	                return 'index.php/'.$item->route;
	            }
	        }
	    }
	    //no sef or no menu item, use basic format
	    return 'index.php?option=com_content&view=category&id='.$id;
	}
	
}

	        
	        //$sef_modern = $comcontent->params->get('sef_advanced'); //confusing naming - the options call it modern but the param calls it advanced
	        //$sef_noids = $comcontent->params->get('sef_ids'); //confusing name/values - the options call it sef_ids but the value 1 (true) means no ids (or do strip ids)
	        // If no menu items found, return non sef link
	        //first choice is a menu entry for this article
	        // second choice is the default item ...
	        // now it gets confusing as with modern on and noids on it expects /menualias/artalias
	        // but with modern on and ids on it expects /menualias/artid-artalias
	        // although menualias/artid will also work
	        // and with modern off it expects /menualais/artid:artalias or again /menualias/artid will work
	        // either way we need to get the alias for the article id
//	        if ($sef_noids) {
//	            return 'index.php/component/content/article/'.$id.'-'.$artalias;
//	        } else {
//	            return 'index.php/article/'.$id.'-'.$artalias;
//	        }
// 	        foreach ($artitems as $item) {
// 	            if ($artitem->home) {
// 	                return 'index.php/'.$item->alias.'/'.$id;
// 	            }
// 	        }
//             return 'index.php/'.$artitems[0]->alias.'/' . $id; //any one will do
	
/***
article routing
1. sef off - must be full url
2 sef on legacy - /[any com_content menu alias]/artid OR /[any com_content menu alias]/artid-anytext (id takes the int off the front
3 sef on modern with ids - same as legacy 
4 sef on modern no ids - /[any-com_content menu alias]/artalias
5 sef on legacy (no ids)
****/
	