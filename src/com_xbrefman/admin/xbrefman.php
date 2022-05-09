<?php
/*******
 * @package xbRefMan Component
 * @version 0.3.1 20th February 2022
 * @filesource admin/xbrefman.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

if (!Factory::getUser()->authorise('core.manage', 'com_xbrefman')) {
    Factory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'),'warning');
    return false;
}

//add the component, and fontawesome css
$document = Factory::getDocument();
$cssFile = Uri::root(true)."/media/com_xbrefman/css/xbrefman.css";
$document->addStyleSheet($cssFile);
$cssFile = "https://use.fontawesome.com/releases/v5.8.1/css/all.css\" integrity=\"sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf\" crossorigin=\"anonymous";
$document->addStyleSheet($cssFile);
//$document->addScript(Uri::root(true).'/media/com_xbrefman/js/xbrefman.js');

JLoader::register('XbrefmanHelper', JPATH_ADMINISTRATOR . '/components/com_xbrefman/helpers/xbrefman.php');
JLoader::register('XbrefmanGeneral', JPATH_ADMINISTRATOR . '/components/com_xbrefman/helpers/xbrefmangeneral.php');

$addstyle = XbrefmanGeneral::getConStyles();
$document = Factory::getDocument();
$document->addStyleDeclaration($addstyle);

// Get an instance of the controller prefixed
$controller = JControllerLegacy::getInstance('Xbrefman');

// Perform the Request task and Execute request task
$controller->execute(Factory::getApplication()->input->get('task'));

// Redirect if set by the controller
$controller->redirect();

