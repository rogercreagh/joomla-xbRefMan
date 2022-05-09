<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.1.1 21st April 2022
 * @filesource admin/controllers/articles.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;

class XbrefmanControllerArticles extends JControllerAdmin {
    
    public function getModel($name = 'Article', $prefix = 'XbrefmanModel', $config = array('ignore_request' => true)) {
        $model = parent::getModel($name, $prefix, $config );
        return $model;
    }
 
    public function edit() {
        $jip =  Factory::getApplication()->input;
        $cid =  $jip->get('cid');
        $redirectTo =('index.php?option=com_content&task=article.edit&id='.$cid[0]);
        $this->setRedirect($redirectTo );
    }
    
    public function remlinksc() {
        $jip =  Factory::getApplication()->input;
        $cid =  $jip->get('cid');
        $model = $this->getModel();
        $wynik = $model->removeLinkRefs($cid);
        $this->setRedirect('index.php?option=com_xbrefman&view=articles');
    }
    
    
}
