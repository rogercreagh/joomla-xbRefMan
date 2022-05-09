<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.1.1 19th April 2022
 * @filesource admin/views/linkrefs/view.html.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class XbrefmanViewLinkrefs extends  Joomla\CMS\MVC\View\HtmlView {
    
    public function display($tpl = null) {
        
        $this->weblinkscom = XbrefmanGeneral::extensionStatus('com_weblinks','component','');   
        if ($this->weblinkscom === false) {
            $app = Factory::getApplication();
            $app->redirect('index.php?option=com_xbrefman&view=dashboard');
            return false;
        }
            
        $this->items = $this->get('Items');
        
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        
        $this->searchTitle = $this->state->get('filter.search');
        $this->catid = $this->state->get('catid');

        $app = Factory::getApplication();
        $this->xbrefscon = XbrefmanGeneral::extensionStatus('xbrefscon','plugin','content');
        if ((!$this->xbrefscon) && (!empty($this->items))) {
            $app->enqueueMessage('xbRefs-Content plugin '.XbrefmanHelper::extStatusStr($this->xbrefscon).' '.Text::_('XBREFMAN_SCODES_APPEAR'), 'Danger');
            $this->defdisp = 'n/a';
            $this->deftrig = 'n/a';
        } else {
            $conplugin = PluginHelper::getPlugin('content','xbrefscon');
            $conpluginParams = new Registry($conplugin->params);
            $this->defdisp = $conpluginParams->get('defdisp','');
            $this->deftrig = $conpluginParams->get('deftrig','');
        }
        if (!empty($this->items)) {
            if ($this->weblinkscom === false) {
                $app->enqueueMessage(Text::_('XBREFMAN_WEBLINK_NOT_INSTALLED'), 'Warning');
            } elseif ($this->weblinkscom == 0) {
                $app->enqueueMessage(Text::_('XBREFMAN_WEBLINK_NOT_ENABLED'), 'Warning');
            }
        }
        $this->xbrefsbtn = XbrefmanGeneral::extensionStatus('xbrefsbtn','plugin','editors-xtd');
        if (!$this->xbrefsbtn) {
            $app->enqueueMessage('xbRefs-Button plugin '.XbrefmanHelper::extStatusStr($this->xbrefsbtn).'. '.Text::_('XBREFMAN_SCODES_MANUALLY'), 'Warning');
        } else {
            $this->editbtnopts = 'index.php?option=com_plugins&task=plugin.edit&extension_id='.$this->xbrefsbtn;
            $btnplugin = PluginHelper::getPlugin('editors-xtd','xbrefsbtn');
            $btnpluginParams = new Registry($btnplugin->params);
            $this->linktaglist = $btnpluginParams->get('linktaglist','');
            $this->linktagparents = XbrefmanHelper::getTagsDetails($this->linktaglist);
            $this->children = $btnpluginParams->get('linkusechild','');
            $alltags = $this->linktaglist;
            if ($this->children) {
                $alltags = XbrefmanHelper::getTagsChildren($this->linktaglist);
                $this->children = count($alltags) - count($this->linktagparents);
            }
            $this->seltags = XbrefmanHelper::getTagsDetails($this->linktaglist);
            
            if ($this->weblinkscom) {
                $this->linkcats = '';
                $this->linkcatarr = $btnpluginParams->get('linkcatlist','');
                if ($this->linkcatarr) {
                    $cattitlesarr = XbrefmanHelper::getTitles($this->linkcatarr,'#__categories');
                    foreach ($cattitlesarr as $cat) {
                        $this->linkcats .= '<span class="label label-success">'.$cat.'</span> ';                        
                    }
                }
                foreach ($this->items as $item) {
                    if ((empty($this->linkcatarr)) || (key_exists($item->catid,$cattitlesarr))) {
                        $item->selcat = true;
                    }
                    if ((empty($this->linktaglist)) || (array_intersect($alltags, array_flip($item->taglist)))) {
                        $item->seltag = true;
                     }                   
                }
                
            }
        }        
        
        
        $params = ComponentHelper::getParams('com_xbrefman');
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }
       
        if($this->xbrefsbtn) {       
            foreach ($this->items as $item) {
                if ($this->linkcatarr) {
                    $item->catsel = (key_exists($item->id, array_flip($this->linkcatarr)));
                }
                if ($this->linktaglist) {
                    $item->tagsel = (array_intersect($item->taglist, $this->linktaglist));
                }
            }
        }
        $this->addToolbar();
        XbrefmanHelper::addSubmenu('linkrefs');
        $this->sidebar = JHtmlSidebar::render();
        
        parent::display($tpl);
        
        $this->setDocument();
        
    }
    
    protected function addToolbar() {
        $canDo = XbrefmanHelper::getActions();
        
        ToolbarHelper::title(Text::_( 'XBREFMAN_TITLE_LINKREFS' ), '' );
        
//        ToolbarHelper::custom('linkrefs.addselect', 'eye','' ,'Add to Select',true);
        
        ToolbarHelper::custom('linkrefs.linkdelsc', 'box-remove','' ,Text::_('XBREFMAN_REM_FROM_ARTS'),true);
        
        if ($canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_xbrefman');
        }
        ToolbarHelper::help( '', false,'https://crosborne.uk/xbrefman/doc?tmpl=component#admin-linkrefs' );
    }
    
    protected function setDocument() {
        $document = Factory::getDocument();
        $document->setTitle(strip_tags(Text::_('XBREFMAN_TITLE_LINKREFS')));
    }
    
    
}
