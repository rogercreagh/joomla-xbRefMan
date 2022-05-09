<?php
/*******
 * @package xbRefMan Component
 * @version 0.8.2 8th April 2022
 * @filesource admin/views/article/view.html.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class XbrefmanViewArticle extends JViewLegacy {
    
    protected $form = null;
    
    public function display($tpl = null) {
        
//        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->canDo = XbrefmanHelper::getActions('com_xbrefman', 'article', $this->item->id);
        
        $app = Factory::getApplication();
        $this->xbrefscon = XbrefmanGeneral::extensionStatus('xbrefscon','plugin','content');
        if (!$this->xbrefscon) {
            $app->enqueueMessage('xbRefs-Content plugin '.XbrefmanHelper::extStatusStr($this->xbrefscon).' Shortcodes will appear in site text.', 'Danger');
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
            $app->enqueueMessage('xbRefs-Button plugin '.XbrefmanHelper::extStatusStr($this->xbrefsbtn).'.You can still enter shortcodes manually.', 'Warning');
        }
        $this->weblinkscom = XbrefmanGeneral::extensionStatus('com_weblinks','component','');
        if (!$this->weblinkscom) {
            $app->enqueueMessage('WebLink component '.XbrefmanHelper::extStatusStr($this->weblinkscom).'. Weblink references will not display.', 'Danger');
        }
        
        $params      = $this->get('State')->get('params');
        
        if (count($errors = $this->get('Errors'))) {
            Factory::getApplication()->enqueueMessage(implode('<br />', $errors),'error');
            return false;
        }
        
        $this->addToolBar();
        
        XbrefmanHelper::addSubmenu('articles');
        $this->sidebar = JHtmlSidebar::render();
        
        parent::display($tpl);
        // Set the document
        $this->setDocument();
        
    }
    
    protected function addToolBar()
    {
        $input = Factory::getApplication()->input;
//        $input->set('hidemainmenu', true);
        $user = Factory::getUser();
        $userId = $user->get('id');
//        $checkedOut     = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
        
//        $canDo = $this->canDo;
        
         
        ToolbarHelper::title(Text::_( 'XBREFMAN_TITLE_ARTICLE' ), '' );
        
        ToolBarHelper::custom('article.edit', 'edit', '', Text::_('XBREFMAN_EDIT_ART'), false) ;
        ToolbarHelper::custom(''); //spacer
        ToolBarHelper::custom('article.dolight', 'eye', '', Text::_('XBREFMAN_HILI_ON'), false) ;
        ToolBarHelper::custom('article.nolight', 'eye-close', '', Text::_('XBREFMAN_HILI_OFF'), false) ;
        ToolBarHelper::custom('article.killcodes', 'cancel-circle', '', Text::_('XBREFMAN_REM_SCODES'), true) ;
        ToolbarHelper::custom(''); //spacer
        ToolbarHelper::cancel('article.cancel','JTOOLBAR_CLOSE');
        
        ToolbarHelper::help( '', false,'https://crosborne.uk/xbrefman/doc?tmpl=component#article' );
   }
    
    protected function setDocument()
    {
        $document = Factory::getDocument();
        $document->setTitle(strip_tags(Text::_('XBREFMAN_TITLE_ARTICLE')));
    }
    
    
    
}