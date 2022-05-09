<?php
/*******
 * @package xbRefMan Component
 * @version 0.4.4 3rd March 2022
 * @filesource admin/controllers/article.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;

class XbrefmanControllerArticle extends JControllerAdmin {
    
    public function edit() {
        $jip =  Factory::getApplication()->input;
        $id =  $jip->get('id');
        $redirectTo =('index.php?option=com_content&task=article.edit&id='.$id);
        $this->setRedirect($redirectTo );
    }
    
    public function getModel($name = 'Article', $prefix = 'XbrefmanModel', $config = array('ignore_request' => true)) {
        $model = parent::getModel($name, $prefix, $config );
        return $model;
    }
   
    public function dolight() {
        $jip =  Factory::getApplication()->input;
        $id =  $jip->get('id');
        $model = $this->getModel();
        $wynik = $model->doHighlights($id);
        $this->setRedirect('index.php?option=com_xbrefman&view=article&id='.$id);
    }
    
    public function nolight() {
        $jip =  Factory::getApplication()->input;
        $id = $jip->get('id');
        $model = $this->getModel();
        $wynik = $model->noHighlights($id);
        $this->setRedirect('index.php?option=com_xbrefman&view=article&id='.$id);
    }
    
    public function killcodes() {
        $jip =  Factory::getApplication()->input;
        $cid = $jip->get('cid');
        $model = $this->getModel();
        $wynik = $model->killShortcodes($cid);
        $this->setRedirect('index.php?option=com_xbrefman&view=article&id='.$jip->get('id'));
    }
    
    public function cancel() {
        $this->setRedirect('index.php?option=com_xbrefman&view=articles');
    }
    
    
}
