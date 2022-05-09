<?php
/**
 * @package xbRefs-Package 
 * @filesource pkg_xbrefman_script.php  
 * @version 0.9.5 2nd May 2022
 * @desc install, upgrade and uninstall actions
 * @author Roger C-O
 * @copyright (C) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;

class pkg_xbrefmanInstallerScript
{
    protected $jminver = '3.10';
    protected $jmaxver = '4.0';
    protected $deleteshortcodes = false;
    
    function preflight($type, $parent)
    {
        if ($type == 'uninstall') {
//             //get the deleteshortcodes flag before it gets destroyed
             $conplugin = PluginHelper::getPlugin('content','xbrefscon');
             $conpluginParams = new Registry($conplugin->params);
             $this->deleteshortcodes = $conpluginParams->get('deleteshortcodes',0);
        } else {
            //check Joomla version
            $jversion = new Version();
            $jverthis = $jversion->getShortVersion();
            
            if (version_compare($jverthis, $this->jminver,'lt')) {
                throw new RuntimeException('xbRefs requires Joomla version '.$this->jminver. ' or higher. You have '.$jverthis);
            }
            if (version_compare($jverthis, $this->jmaxver, 'ge')){
                throw new RuntimeException('xbRefs requires Joomla version less than '.$this->jmaxver.' You have '.$jverthis);
            }                   
        }
//        Factory::getApplication()->enqueuemessage('com-preflight');
    }
    
    function install($parent) {
    }
    
    function uninstall($parent)
    {
        echo '<p>The xbRefMan Package has been uninstalled</p>';
        $found = $this->findXbrefs();
        if ($found) {
            echo '<p>The following '.count($found).' articles contained {xbref...} shortcodes, there may be more than one in each article.';
            echo '</p><ul>';
            $cnt = 0;
            foreach ($found as $a) {
                echo '<li><a href="'.Uri::root().'administrator/index.php?option=com_content&task=article.edit&id='.$a['id'].'" target="_blank">'.$a['title'].'</a></li>';
                if ($this->deleteshortcodes) {
                    if ($this->removeXbrefs($a)) $cnt++;
                }
            }
            Factory::getApplication()->enqueueMessage('{xbref...} Shortcodes deleted from '.$cnt.' articles','Warning');
            echo  '</ul><p>Clicking the links above will open the edit page for each article in a new tab to check shortcodes</p>';
            if ($this->deleteshortcodes) {
                echo '<p>If you have article versioning enabled you can use that to restore the codes if you wanted to keep them.</p>';
            } else{
                echo '<p>If you wanted to remove them you can use the links above to manually delete them, or reinstall the xbRefs-Content plugin, set the option to delete on uninstall and uninstall it again.</p>';
            }
            echo '<p>It is suggested you do not close this page until you have checked or copied the list above somewhere safe for future use.';
        } else {
            echo '<p>All articles scanned and no {xbref...} shortcodes found.';
        }
    }
    
    function update($parent)
    {        
        echo '<div style="padding: 7px; margin: 15px; list-style: none; -webkit-border-radius: 4px; -moz-border-radius: 4px;
		border-radius: 4px; background-image: linear-gradient(#fffff7,#ffffe0); border: solid 1px #830000; color:#830000;">';
        echo '<p><b>xbRefMan Package</b> updated to version <b>' . $parent->get('manifest')->version . '</b> includes:</p>';
        echo '<ul><li>xbRefMan version ' . $parent->get('manifest')->com_version . '</li>';
        echo '<li>xbRefs-Content Plugin version ' . $parent->get('manifest')->con_version . '</li>';
        echo '<li>xbRefs-Button version ' . $parent->get('manifest')->btn_version . '</li></ul>';
        echo '<p>For details see <a href="http://crosborne.co.uk/xbrefman/changelog" target="_blank">
            www.crosborne.co.uk/xbrefman/changelog</a></p>';
        echo '</div>';
    }
    
    function postflight($type, $parent)
    {
        $message = $parent->get('manifest')->name.' v'.$parent->get('manifest')->version.' has been ';
        switch ($type) {
            case 'install': 
                $message .= 'installed';
                break;
            case 'uninstall': 
                $message .= 'uninstalled'; 
                break;
            case 'update': 
                $message .= 'updated'; 
                break;
            case 'discover_install': 
                $message .= 'discovered and installed'; 
                break;
        }
        Factory::getApplication()->enqueueMessage($message);
        if (($type=='install') || ($type == 'discover_install')) {
            echo '<div style="padding: 7px; margin: 15px; list-style: none; -webkit-border-radius: 4px; -moz-border-radius: 4px;
		border-radius: 4px; background-image: linear-gradient(#fffff7,#ffffe0); border: solid 1px #830000; color:#830000;">';
            echo '<h3>xbRefMan Package installation</h3>';
            echo '<p>Package version '.$parent->get('manifest')->version.' '.$parent->get('manifest')->creationDate.'<br />';
            echo 'Extensions included: </p>';
            echo '<ul><li>xbRefMan '.$parent->get('manifest')->com_version.' Reference Manager Component</li>';
            echo '<li>xbRefs-Content Plugin version ' . $parent->get('manifest')->con_version . '</li>';
            echo '<li>xbRefs-Button Plugin version ' . $parent->get('manifest')->btn_version . '</li></ul>';
            echo '<p><i>For help and information see <a href="https://crosborne.co.uk/xbrefman/doc" target="_blank">
            www.crosborne.co.uk/refman/doc</a></i></p>';
            echo '<h4>Next steps:</h4>';
            echo '<p>IMPORTANT - <i>Review &amp; set the options</i>&nbsp;&nbsp;';
            echo '<a href="index.php?option=com_config&view=component&component=com_xbrefman" class="btn btn-small btn-info">xbRefMan Options</a>';
            echo ' and&nbsp;<a href="index.php?option=com_plugins&filter_search=xb" class="btn btn-small btn-info">Plugin Options Pages</a></p>';
            echo ' <br /><i>check the defaults match your expectations and save them and enable the plugins.</i></p>';
            echo '</div>';
        }
    }

    function findXbrefs() {
        $articles = array();       
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('id','title','introtext','fulltext')));
        $query->from($db->quoteName('#__content'));
        $query->where($db->quoteName('introtext') . ' LIKE \'%{xbref%\'');
        $query->orWhere($db->quoteName('fulltext') . ' LIKE \'%{xbref%\'');
        $db->setQuery($query);
        $articles = $db->loadAssocList('id');
        return $articles;
    }
    
    function removeXbrefs(array $article) {
        $introtext = $article['introtext'];
        //stip out hide and shows
        $introtext=preg_replace('!<span class="xbhideref" (.*?)>(.*?)</span>!', '${2}', $introtext);
        $introtext=preg_replace('!<span class="xbshowref" (.*?)>(.*?)</span>!', '${2}', $introtext);
        $introtext=preg_replace('!{xbref (.*?)}(.*?){/xbref}!', '${2}', $introtext);  
        $introtext=preg_replace('!<sup class="xbrefed">(.*?)</sup>!', '' ,$introtext);
        $introtext=preg_replace('!<cite class="xbrefed">(.*?)</cite>!', '' ,$introtext);
        $fulltext = $article['fulltext'];
        if ($fulltext) {
            $fulltext=preg_replace('!<span class="xbhideref" (.*?)>(.*?)</span>!', '${2}', $fulltext);
            $fulltext=preg_replace('!<span class="xbshowref" (.*?)>(.*?)</span>!', '${2}', $fulltext);
            $fulltext=preg_replace('!{xbref (.*?)}(.*?){/xbref}!', '${2}', $fulltext);            
            $fulltext=preg_replace('!<sup class="xbrefed">(.*?)</sup>!', '' ,$fulltext);
            $fulltext=preg_replace('!<cite class="xbrefed">(.*?)</cite>!', '' ,$fulltext);
        }
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        // Fields to update.
        $fields = array(
            $db->quoteName('introtext') . ' = ' . $db->quote($introtext),
            $db->quoteName('fulltext') . ' = ' . $db->quote($fulltext)
        );        
        // Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . ' = ' . $article['id']
        );
        
        $query->update($db->quoteName('#__content'))->set($fields)->where($conditions);
        
        $db->setQuery($query);
        try {
            $result = $db->execute();           
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error removing shortcodes in article '.$article['title']);
            return false;
        }
        return true;
    }
}
