<?php
/*******
 * @package xbRefMan Component
 * @version 0.8.2 8th April 2022
 * @filesource admin/views/textrefs/view.html.php
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

class XbrefmanViewTextrefs extends JViewLegacy {
 
    protected $form = null;
    
    public function display($tpl = null) {
        
        $this->form = $this->get('Form');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        
        $this->searchTitle = $this->state->get('filter.search');
        $this->catid = $this->state->get('catid');

        $app = Factory::getApplication();
        $this->xbrefscon = XbrefmanGeneral::extensionStatus('xbrefscon','plugin','content');
        if (!$this->xbrefscon) {
            $app->enqueueMessage('xbRefs-Content plugin '.XbrefmanHelper::extStatusStr($this->xbrefscon).' '.Text::_('XBREFMAN_SCODES_APPEAR'), 'Danger');
            $this->defdisp = 'n/a';
            $this->deftrig = 'n/a';
        } else {
            $conplugin = PluginHelper::getPlugin('content','xbrefscon');
            $conpluginParams = new Registry($conplugin->params);
            $this->defdisp = $conpluginParams->get('defdisp','');
            $this->deftrig = $conpluginParams->get('deftrig','');
        }
        $this->xbrefsbtn = XbrefmanGeneral::extensionStatus('xbrefsbtn','plugin','editors-xtd');
        if (!$this->xbrefsbtn) {
            $app->enqueueMessage('xbRefs-Button plugin '.XbrefmanHelper::extStatusStr($this->xbrefsbtn).'. '.Text::_('XBREFMAN_SCODES_MANUALLY'), 'Warning');
        }
            
        $params = ComponentHelper::getParams('com_xbrefman');
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }
                
        $this->addToolbar();
        XbrefmanHelper::addSubmenu('textrefs');
        $this->sidebar = JHtmlSidebar::render();
        
        parent::display($tpl);
        
        $this->setDocument();
        
    }
    
    protected function addToolbar() {
        $canDo = XbrefmanHelper::getActions();
        
        ToolbarHelper::title(Text::_( 'XBREFMAN_TITLE_TEXTREFS' ), '' );
        
        ToolbarHelper::custom('textrefs.textdelscs', 'box-remove','' ,Text::_('XBREFMAN_REM_SCODES'),true);
        ToolbarHelper::custom('textrefs.text2tags', 'box-remove','' ,Text::_('XBREFMAN_CONVERT_TAG'),true);
        
        if ($canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_xbrefman');
        }
        ToolbarHelper::help( '', false,'https://crosborne.uk/xbrefman/doc?tmpl=component#admin-tagrefs' );
    }
    
    protected function setDocument() {
        $document = Factory::getDocument();
        $document->setTitle(strip_tags(Text::_('XBREFMAN_TITLE_TEXTREFS')));
    }
    
    
}
