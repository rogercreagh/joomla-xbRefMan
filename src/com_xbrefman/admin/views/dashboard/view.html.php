<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.1.1 21st April 2022
 * @filesource admin/views/dashboard/view.html.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class XbrefmanViewDashboard extends JViewLegacy {
		
	public function display($tpl = null) {
		
	    $this->params = ComponentHelper::getParams('com_xbrefman');
// get params with defaults
// get content plugin params
// get button plugin params
		
		$this->xmldata = Installer::parseXMLInstallFile(JPATH_COMPONENT_ADMINISTRATOR . '/xbrefman.xml');
		$this->client = $this->get('Client');
		
		$this->artcnts = $this->get('ArticleCounts');
		$this->refcnts = $this->get('ReferenceCounts');
		$this->tagstaglist = '';
		
		$this->xbrefscon = XbrefmanGeneral::extensionStatus('xbrefscon','plugin','content');
		$this->xbrefsconstatus = ($this->xbrefscon)? $this->extVerStr($this->xbrefscon) : XbrefmanHelper::extStatusStr($this->xbrefscon);
		$this->xbrefsbtn = XbrefmanGeneral::extensionStatus('xbrefsbtn','plugin','editors-xtd');
		$this->xbrefsbtnstatus = ($this->xbrefsbtn)? $this->extVerStr($this->xbrefsbtn) : XbrefmanHelper::extStatusStr($this->xbrefsbtn);
		$this->weblinkscom = XbrefmanGeneral::extensionStatus('com_weblinks','component','');
		$this->weblinkscomstatus = ($this->weblinkscom)? $this->extVerStr($this->weblinkscom) : XbrefmanHelper::extStatusStr($this->weblinkscom);
		
		$app = Factory::getApplication();
		if  ($this->artcnts['articles']>0) {
		    if ($this->xbrefscon === false) {
		        $app->enqueueMessage(Text::_('XBREFMAN_CONTENT_SCODE_WARNING').' '.Text::_('XBREFMAN_INSTALLED'),'danger');
		    } elseif ($this->xbrefscon === 0) {
		        $app->enqueueMessage(Text::_('XBREFMAN_CONTENT_SCODE_WARNING').' '.Text::_('XBREFMAN_ENABLED'),'Warning');
		    }
		    if ($this->artcnts['links']>0) {
		        if ($this->weblinkscom === false) {
		            $app->enqueueMessage(Text::_('XBREFMAN_WEBLINK_SCODE_WARNING').' '.Text::_('XBREFMAN_INSTALLED'),'danger');
		        } elseif ($this->weblinkscom === 0) {
		            $app->enqueueMessage(Text::_('XBREFMAN_WEBLINK_SCODE_WARNING').' '.Text::_('XBREFMAN_ENABLED'),'Warning');
    		    }		        
		    }
		    if ($this->xbrefsbtn === false) {
		        $app->enqueueMessage(Text::_('XBREFMAN_BUTTON_WARNING').' '.Text::_('XBREFMAN_INSTALLED'),'warning');
		    } elseif ($this->xbrefsbtn === 0) {
		        $app->enqueueMessage(Text::_('XBREFMAN_BUTTON_WARNING').' '.Text::_('XBREFMAN_ENABLED'),'warning');
		    }		    
		}
		$this->tagtags = '';
		if ($this->xbrefsbtn > 0){
		    $this->tagtags = $this->get('SelectableTagNames');
		    if ($this->tagtags == '') {
		        $this->tagtags = '<i>'.Text::_('XBREFMAN_ALL_TAGS_SELECT').'</i>';
		    }
		    
		    $this->linktags = $this->get('SelectableLinkNames');
		    if ($this->linktags == '') {
		        $this->linktags = '<i>'.Text::_('XBREFMAN_ALL_TAGS_SELECT').'</i>';
		    }
		    $this->linkcats = $this->get('SelectableLinkCats');
		    if ($this->linkcats == '') {
		        $this->linkcats = '<i>'.Text::_('XBREFMAN_ALL_CATS_SELEECT').'</i>';
		    }
		}
		$this->defdisp = 'n/a';
		if ($this->xbrefscon > 0) {
		    $conplugin = PluginHelper::getPlugin('content','xbrefscon');
		    $conpluginParams = new Registry($conplugin->params);
		    $this->defdisp = $conpluginParams->get('defdisp','');
		    $this->deftrig = $conpluginParams->get('deftrig','');
		    $this->refnumfmt = 'N';
		    $refbrkt = $conpluginParams->get('refbrkt','0');
		    if ($refbrkt) {
		        $this->refnumfmt = '<b>[</b> N <b>]</b>';
		    }
		    $this->clickhelp = $conpluginParams->get('clickhelp','');
		    $this->linktarg = Text::_('XBREFMAN_LINKTARG_SAME');
		    $weblinktarg = $conpluginParams->get('weblinktarg','');
		    switch ($weblinktarg) {
		        case 1:
		            $this->linktarg = Text::_('XBREFMAN_LINKTARG_NEW');
		            break;
		        case 2:
		            $this->linktarg = Text::_('XBREFMAN_LINKTARG_AUTO');
		            break;		            
		        default:
		          break;
		    }
		    $this->linkfmt = '';
		    $weblinkpos = $conpluginParams->get('weblinkpos','');		
		    switch ($weblinkpos) {
		        case 1:
		            $this->linkfmt = '"<span class="xbclick">'.Text::_('XBREFMAN_VISIT_LINK').'</span>" '.Text::_('XBREFMAN_ADD_AFTER_DESC');
		            break;
		        case 2:
		            $this->linkfmt = '"<span class="xbclick">'.Text::_('XBREFMAN_FULL_LINK_URL').'</span>" '.Text::_('XBREFMAN_ADD_AFTER_DESC');
		            break;
		        case 1:
		            $this->linkfmt = '"<span class="xbclick">'.Text::_('XBREFMAN_LINK_TITLE').'</span>" '.Text::_('XBREFMAN_AS_THE_LINK');
		            break;		            
		        default:
		        break;
		    }
		    
		}
				
		// Check for errors.
 		if (count($errors = $this->get('Errors'))) {
 		    throw new Exception(implode("\n", $errors), 500);
		}
		
	   $this->addToolbar();
		XbrefmanHelper::addSubmenu('dashboard');
		$this->sidebar = JHtmlSidebar::render();
		
		parent::display($tpl);
		
		$this->setDocument();
	}
	
	protected function extVerStr($ext) {
	    $statusstr = '';
	    if ($ext === false) {
	        $statusstr = Text::_( 'XBREFMAN_NOT_INSTALLED' );
	    } elseif ($ext === 0) {
	        $statusstr = Text::_( 'XBREFMAN_NOT_ENABLED' );
	    } else {
	        $db = Factory::getDBO();
	        $query = $db->getQuery(true);
	        $query->select('manifest_cache')
	        ->from($db->quoteName('#__extensions'))
	        ->where($db->quoteName('extension_id')." = ".$db->quote($ext));
	        $db->setQuery($query);
	        $man = $db->loadResult();
	        if ($man) {
	            $man = json_decode($man);
	            $statusstr = Text::_('XBREFMAN_VERSION').': '.$man->version;
	            $statusstr .= '<br />'.$man->creationDate;
	        } else {
	            $statusstr .= 'problem with manifest';
	        }
	    }
	    return $statusstr;
	}
	

	protected function addToolbar() {
		$canDo = XbrefmanHelper::getActions();
		
		ToolbarHelper::title(Text::_( 'XBREFMAN_TITLE_CPANEL' ), '' );
		
		if ($canDo->get('core.admin')) {
		    ToolbarHelper::link('index.php?option=com_plugins&task=plugin.edit&extension_id='.$this->xbrefsbtn, 'xbRefsButton Options','cog');
		    ToolbarHelper::link('index.php?option=com_plugins&task=plugin.edit&extension_id='.$this->xbrefscon, 'xbRefsContent Options','cog');
		    ToolbarHelper::preferences('com_xbrefman');
		}
		ToolbarHelper::help( '', false,'https://crosborne.uk/xbrefman/doc?tmpl=component#admin' );
	}
	
	protected function setDocument() {
		$document = Factory::getDocument();
		$document->setTitle(strip_tags(Text::_('XBREFMAN_TITLE_CPANEL')));
	}
	
}