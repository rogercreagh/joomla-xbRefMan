<?php
/*******
 * @package xbRefMan Component
 * @version 0.5.1 8th March 2022
 * @filesource admin/controllers/linkrefs.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;

class XbrefmanControllerLinkRefs extends \Joomla\CMS\MVC\Controller\AdminController {
    
    public function getModel($name = 'Linkrefs', $prefix = 'XbrefmanModel', $config = array('ignore_request' => true)) {
        $model = parent::getModel($name, $prefix, $config );
        return $model;
    }
    
    function linkdelsc() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel();
        $wynik = $model->linksDeleteShortcodes($pid);
        $this->setRedirect('index.php?option=com_xbrefman&view=linkrefs');
    }
    
}
    
