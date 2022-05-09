<?php
/*******
 * @package xbRefMan Component
 * @version 0.7.7.2 5th April 2022
 * @filesource site/views/references/view.html.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

class XbrefmanViewReferences extends JViewLegacy {
    
    protected $item;
    
    public function display($tpl = null) {
        $this->items 		= $this->get('Items');
//        $this->pagination	= $this->get('Pagination');
        $this->state		= $this->get('State');
        $this->params      = $this->state->get('params');
        $this->filterForm    	= $this->get('FilterForm');
        $this->activeFilters 	= $this->get('ActiveFilters');
        $this->searchTitle = $this->state->get('filter.search');
        
 //       $cparams = ComponentHelper::getParams('com_xbmaps');
        $this->show_cats = $this->params->get('show_cats',0);
        $this->show_tags = $this->params->get('show_tags',0);
        $this->auto_close_dets = $this->params->get('auto_close_dets',1);
        
        $this->header = array();
        $this->header['showheading'] = $this->params->get('show_page_heading',0,'int');
        $this->header['heading'] = $this->params->get('page_heading','','text');
        if ($this->header['heading'] =='') {
            $this->header['heading'] = $this->params->get('page_title','','text');
        }
        $this->header['title'] = $this->params->get('list_title','','text');
        $this->header['subtitle'] = $this->params->get('list_subtitle','','text');
        $this->header['text'] = $this->params->get('list_headtext','','text');
        
        $this->search_bar = $this->params->get('search_bar','1','int');
        
        if (count($errors = $this->get('Errors'))) {
            Factory::getApplication()->enqueueMessage(implode('<br />', $errors),'error');
            return false;
        }
        //set metadata
        $document=$this->document;
        $document->setMetaData('title', Text::_('XBREFMAN_REFERENCES_LISTING').': '.$document->title);
        $metadesc = $this->params->get('menu-meta_description');
        if (!empty($metadesc)) { $document->setDescription($metadesc); }
        $metakey = $this->params->get('menu-meta_keywords');
        if (!empty($metakey)) { $document->setMetaData('keywords', $metakey);}
        $metarobots = $this->params->get('robots');
        if (!empty($metarobots)) { $document->setMetaData('robots', $metarobots);}
        $document->setMetaData('generator', $this->params->get('def_generator'));
        $metaauthor = $this->params->get('def_author');
        if (!empty($metaauthor)) { $document->setMetaData('author', $metadata['author']);}
        
        parent::display($tpl);
        
    }
}
