<?php
/*******
 * @package xbRefMan Component
 * @version 0.1.0 11th February 2022
 * @filesource admin/script.xbrefman.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );


use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;

class com_xbrefmanInstallerScript
{
    protected $jminver = '3.10';
    protected $jmaxver = '4.0';
    protected $extension = 'com_xbrefman';
    protected $ver = 'v0';
    protected $date = '';
    
    function preflight($type, $parent) {
        $jversion = new Version();
        $jverthis = $jversion->getShortVersion();
        if ((version_compare($jverthis, $this->jminver,'lt')) || (version_compare($jverthis, $this->jmaxver, 'ge'))) {
            throw new RuntimeException('xbRefMan requires Joomla version minimum '.$this->jminver. ' and less than '.$this->jmaxver.'. You have '.$jverthis);
        }
        $message='';
        if ($type=='update') {
            $componentXML = Installer::parseXMLInstallFile(Path::clean(JPATH_ADMINISTRATOR . '/components/com_xbrefman/xbrefman.xml'));
            $this->ver = $componentXML['version'];
            $this->date = $componentXML['creationDate'];
            $message = 'Updating xbRefMan Component from '.$componentXML['version'].' '.$componentXML['creationDate'];
            $message .= ' to '.$parent->get('manifest')->version.' '.$parent->get('manifest')->creationDate;
        }
        if ($message!='') { Factory::getApplication()->enqueueMessage($message,'');}
    }
    
    function install($parent) {
    }
    
    function uninstall($parent) {
        $componentXML = Installer::parseXMLInstallFile(Path::clean(JPATH_ADMINISTRATOR . '/components/com_xbrefman/xbrefman.xml'));
        $message = 'Uninstalling xbRefMan Component v.'.$componentXML['version'].' '.$componentXML['creationDate'];
        $message .= '<br />xbRef Shortcodes in articles have <b>not</b> been deleted. To remove them uninstallthe xbRefs-Content plugin with the option to remove shortcodes on uninstall set to YES';
        Factory::getApplication()->enqueueMessage($message,'');
    }
    
    function update($parent) {
        
        Factory::getApplication()->enqueueMessage('Please check Package xbRefsPlugins is updated to latest version');
        $message = '<br />Visit the <a href="index.php?option=com_xbrefman&view=dashboard" class="btn btn-small btn-info">';
        $message .= 'xbRefMan Dashboard</a> page for overview of status.</p>';
        $message .= '<br />For ChangeLog see <a href="http://crosborne.co.uk/xbrefman/changelog" target="_blank">
            www.crosborne.co.uk/xbrefman/changelog</a></p>';
        
        Factory::getApplication()->enqueueMessage($message,'Message');
    }
    
    function postflight($type, $parent) {
       $componentXML = Installer::parseXMLInstallFile(Path::clean(JPATH_ADMINISTRATOR . '/components/com_xbrefman/xbrefman.xml'));
       Factory::getApplication()->enqueueMessage('Please check Package xbRefsPlugins is updated to latest version');
       if ($type=='install') {
           echo '<div style="padding: 7px; margin: 0 0 8px; list-style: none; -webkit-border-radius: 4px; -moz-border-radius: 4px;
		border-radius: 4px; background-image: linear-gradient(#ffffff,#efefef); border: solid 1px #ccc;">';
           echo '<h3>xbRefMan '.$componentXML['version'].' '.$componentXML['creationDate'].'</h3>';
           echo '<p>For help and information see <a href="https://crosborne.co.uk/xbrefman/doc" target="_blank">
	            www.crosborne.co.uk/xbrefman/doc</a> or use Help button in xbRefMan Dashboard</p>';
           echo '<h4>Next steps:</h4>';
           echo '<p>IMPORTANT - <i>Review &amp; set the options</i>&nbsp;&nbsp;';
           echo '<a href="index.php?option=com_config&view=component&component=com_xbrefman" class="btn btn-small btn-info">xbRefMan Options</a>';
           echo ' <br /><i>check the defaults match your expectations and save them.</i></p>';
           echo '</div>';
       }
    }
    
}
