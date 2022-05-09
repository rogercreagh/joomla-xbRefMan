<?php
/*******
 * @package xbRefMan Component
 * @version 0.8.2 8th April 2022
 * @filesource admin/views/tagrefs/view.html.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class XbrefmanViewTagrefs extends Joomla\CMS\MVC\View\HtmlView { // JViewLegacy {
    
    public function display($tpl = null) {
        
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        
        $this->searchTitle = $this->state->get('filter.search');

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
        $this->xbrefsbtn = XbrefmanGeneral::extensionStatus('xbrefsbtn','plugin','editors-xtd');
        if (!$this->xbrefsbtn) {
            $app->enqueueMessage('xbRefs-Button plugin '.XbrefmanHelper::extStatusStr($this->xbrefsbtn).'. '.Text::_('XBREFMAN_SCODES_MANUALLY'), 'Warning');
        } else {
            $this->editbtnopts = 'index.php?option=com_plugins&task=plugin.edit&extension_id='.$this->xbrefsbtn;
            $btnplugin = PluginHelper::getPlugin('editors-xtd','xbrefsbtn');
            $btnpluginParams = new Registry($btnplugin->params);
            $this->taglist = $btnpluginParams->get('taglist','');
            $this->tagparents = XbrefmanHelper::getTagsDetails($this->taglist);
            $this->children = $btnpluginParams->get('usechild','');
            $alltags = $this->taglist;
            if ($this->children) {
                $alltags = XbrefmanHelper::getTagsChildren($this->taglist);
                $this->children = count($alltags) - count($this->tagparents);
            }
            $this->seltags = XbrefmanHelper::getTagsDetails($this->taglist);
            foreach ($this->items as $item) {
                $item->seltag = false;
                if ((empty($this->taglist)) || (key_exists($item->id, array_flip($alltags)))) {
                    $item->seltag = true;
                }
            }
        }        
        
        $params = ComponentHelper::getParams('com_xbrefman');
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }
        
        $this->addToolbar();
        XbrefmanHelper::addSubmenu('tagrefs');
        $this->sidebar = JHtmlSidebar::render();
        
        parent::display($tpl);
        
        $this->setDocument();
        
    }
    
    protected function addToolbar() {
        $canDo = XbrefmanHelper::getActions();
        //	<p>Actions buttonsfor toolbar: Edit Tag, Remove ref from all articles,
        
        ToolbarHelper::title(Text::_( 'XBREFMAN_TITLE_TAGREFS' ), '' );
        
        ToolbarHelper::custom('tagrefs.addselect', 'eye','' ,Text::_('XBREFMAN_ADD_TO_SELECT'),true);

        ToolbarHelper::custom('tagrefs.tagdelsc', 'box-remove','' ,Text::_('XBREFMAN_REM_SCODES'),true);      
        
        if ($canDo->get('core.edit')) {
//            ToolbarHelper::editList('article.edit', 'Edit Article');
        }
        
//        ToolbarHelper::custom(); //spacer
        
        
        // Add a batch button
/*         if ($canDo->get('core.create') && $canDo->get('core.edit')
            && $canDo->get('core.edit.state'))
        {
            // we use a standard Joomla layout to get the html for the batch button
            $bar = Toolbar::getInstance('toolbar');
            $layout = new FileLayout('joomla.toolbar.batch');
            $batchButtonHtml = $layout->render(array('title' => Text::_('JTOOLBAR_BATCH')));
            $bar->appendButton('Custom', $batchButtonHtml, 'batch');
        }
 */        
        if ($canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_xbrefman');
        }
        ToolbarHelper::help( '', false,'https://crosborne.uk/xbrefman/doc?tmpl=component#admin-tagrefs' );
    }
    
    protected function setDocument() {
        $document = Factory::getDocument();
        $document->setTitle(strip_tags(Text::_('XBREFMAN_TITLE_TAGREFS')));
    }
        
}
