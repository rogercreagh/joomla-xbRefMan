<?php
/*******
 * @package xbRefMan Component
 * @version 0.6.3 9th March 2022
 * @filesource admin/controllers/textrefs.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;

class XbrefmanControllerTextrefs extends \Joomla\CMS\MVC\Controller\AdminController {
    
    public function getModel($name = 'Textrefs', $prefix = 'XbrefmanModel', $config = array('ignore_request' => true)) {
        $model = parent::getModel($name, $prefix, $config );
        return $model;
    }
    
    function textdelscs() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $model = $this->getModel();
        $wynik = $model->textDeleteShortcodes($pid);
        $this->setRedirect('index.php?option=com_xbrefman&view=textrefs');
    }
    
    function text2tags() {
        $jip =  Factory::getApplication()->input;
        $pid =  $jip->get('cid');
        $form = $jip->get('jform');
        $parent_tag = $form['parent_tag'];
        if (!$parent_tag) {
            $parent_tag = 1;
        }
        $model = $this->getModel();
        $wynik = $model->texts2Tags($pid,$parent_tag);
        $this->setRedirect('index.php?option=com_xbrefman&view=textrefs');
    }
    
}
    
