<?php
/*******
 * @package xbRefMan Component
 * @version 0.4.2 1st March 2022
 * @filesource admin/controllers/tagrefs.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;

class XbrefmanControllerTagRefs extends \Joomla\CMS\MVC\Controller\AdminController {
    
    public function getModel($name = 'Tagrefs', $prefix = 'XbrefmanModel', $config = array('ignore_request' => true)) {
        $model = parent::getModel($name, $prefix, $config );
        return $model;
    }
    
    function addselect() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel();
        $wynik = $model->addTagSelect($pid);
        $this->setRedirect('index.php?option=com_xbrefman&view=tagrefs');
    }
    
    function tagdelsc() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel();
        $wynik = $model->tagsDeleteShortcodes($pid);
        $this->setRedirect('index.php?option=com_xbrefman&view=tagrefs');
    }
    
}
    
