<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.3.1 24th April 2022
 * @filesource site/xbrefman.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$document = Factory::getDocument();
$cssFile = Uri::root(true)."/media/com_xbrefman/css/xbrefman.css";
$document->addStyleSheet($cssFile);
// $cssFile = "https://use.fontawesome.com/releases/v5.8.1/css/all.css\" integrity=\"sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf\" crossorigin=\"anonymous";
// $document->addStyleSheet($cssFile);

JLoader::register('XbrefmanGeneral', JPATH_ADMINISTRATOR . '/components/com_xbrefman/helpers/xbrefmangeneral.php');
JLoader::register('XbrefmanHelper', JPATH_COMPONENT. '/helpers/xbrefman.php');

$addstyle = XbrefmanGeneral::getConStyles();
$document->addStyleDeclaration($addstyle);
if ($addstyle = XbrefmanGeneral::getRefManStyles()) $document->addStyleDeclaration($addstyle);

// Get an instance of the controller prefixed
$controller = JControllerLegacy::getInstance('Xbrefman');

// Perform the Request task and Execute request task
$controller->execute(Factory::getApplication()->input->get('task'));

// Redirect if set by the controller
$controller->redirect();


