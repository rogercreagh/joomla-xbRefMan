<?php
/*******
 * @package xbRefMan Component
 * @version 0.1.0 11th February 2022
 * @filesource admin/controller.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

class XbrefmanController extends JControllerLegacy {
    
    protected $default_view = 'dashboard';
    
    public function display ($cachable = false, $urlparms = false){
        
        return parent::display();
    }
}

