<?php
/*******
 * @package xbRefMan Component
 * @version 0.7.4 26th March 2022
 * @filesource site/views/weblink/view.html.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

class XbrefmanViewReference extends JViewLegacy {
    
    protected $item;
    
    public function display($tpl = null) {
        $this->item 		= $this->get('Item');
//         $this->pagination	= $this->get('Pagination');
        $this->state		= $this->get('State');
        $this->params      = $this->state->get('params');
//         $this->filterForm    	= $this->get('FilterForm');
//         $this->activeFilters 	= $this->get('ActiveFilters');
//         $this->searchTitle = $this->state->get('filter.search');
        
        $this->show_cats = $this->params->get('show_cats',0);
        $this->show_tags = $this->params->get('show_tags',0);
        $this->header = array();
        $this->header['showheading'] = $this->params->get('show_page_heading',0,'int');
        $this->header['heading'] = $this->params->get('page_heading','','text');
        if ($this->header['heading'] =='') {
            $this->header['heading'] = $this->params->get('page_title','','text');
        }
        $this->header['title'] = $this->params->get('list_title','','text');
        $this->header['subtitle'] = $this->params->get('list_subtitle','','text');
        $this->header['text'] = $this->params->get('list_headtext','','text');
        
//         $this->search_bar = $this->params->get('search_bar','1','int');
//         $this->hide_catsch = $this->params->get('menu_category_id',0)>0 ? true : false;
//         $this->hide_tagsch = (!empty($this->params->get('menu_tag',''))) ? true : false;
        
        if (count($errors = $this->get('Errors'))) {
            Factory::getApplication()->enqueueMessage(implode('<br />', $errors),'Error');
            return false;
        }
        //set metadata
        $document=$this->document;
        $document->setMetaData('title', Text::_('XBREFMAN_TAG_LISTING').': '.$document->title);
        $metadesc = $this->params->get('menu-meta_description','');
        if (!empty($metadesc)) { $document->setDescription($metadesc); }
        $metakey = $this->params->get('menu-meta_keywords','');
        if (!empty($metakey)) { $document->setMetaData('keywords', $metakey);}
        $metarobots = $this->params->get('robots','');
        if (!empty($metarobots)) { $document->setMetaData('robots', $metarobots);}
        $document->setMetaData('generator', $this->params->get('def_generator',''));
        $metaauthor = $this->params->get('def_author','');
        if (!empty($metaauthor)) { $document->setMetaData('author', $metadata['author']);}
        
        parent::display($tpl);
        
    }
}
