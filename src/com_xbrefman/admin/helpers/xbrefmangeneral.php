<?php
/*******
 * @package xbRefMan Component
 * @version 1.0.1 4th November 2022
 * @filesource admin/helpers/xbrefmangeneral.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Version;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class XbrefmanGeneral extends ContentHelper {
    
    /** USED IN ADMIN & SITE
     * @name  extensionStatus()
     * @desc returns extension id if enabled, 0 if not enabled or false if not installed
     * @param string $element - the name of extension folder eg com_xbrefman or xbrefsbtn
     * @param string $type - component|module|plugin|template|library... required as plugin element names can be duplicated
     * @param string $folder - required if type=plugin as some packages use the same element name for all their plugins
     * @return int
     */
    public static function extensionStatus(string $element, $type, $folder='') {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('extension_id, enabled')
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type')." = ".$db->quote($type). 'AND '.$db->quoteName('element')." = ".$db->quote($element));
        if ($type) {
            $query->where($db->quoteName('type')." = ".$db->quote($type));
            if ($type == 'plugin') {
                $query->where($db->quoteName('folder')." = ".$db->quote($folder));
            }
        }
        $db->setQuery($query);
        $res = $db->loadAssoc();
        if ($res==null) return false;
        if ($res['enabled']==0) $stat = 0;
        if ($res['enabled']==1) $stat = $res['extension_id'];;
        return $stat;
    }
    
    /** USED IN ADMIN & SITE
     * @name cerdit()
     * @desc checks if a beer has been paid for
     * @return string
     */
    public static function credit() {
        if (self::penPont()) {
            return '';
        }
        $credit='<div class="xbcredit">';
        if (Factory::getApplication()->isClient('administrator')==true) {
            $xmldata = Installer::parseXMLInstallFile(JPATH_ADMINISTRATOR.'/components/com_xbrefman/xbrefman.xml');
            $credit .= '<a href="http://crosborne.uk/xbrefman" target="_blank">
                xbRefMan Component '.$xmldata['version'].' '.$xmldata['creationDate'].'</a>';
            $credit .= '<br />'.Text::_('XBREFMAN_BEER_TAG');
            $credit .= Text::_('XBREFMAN_BEER_FORM');
        } else {
            $credit .= 'xbRefMan by <a href="http://crosborne.uk/xbrefman" target="_blank">CrOsborne</a>';
        }
        $credit .= '</div>';
        return $credit;
    }
    
    /** USED IN ADMIN & SITE
     * getNameValue
     * @desc return the value from a substring name="value" in the source string.
     * value must be in quotes and no spaces between name and value. value cannot contain quotes, will only return first instance of name
     * @usedby admin/models/dashboard, linkrefs, tagrefs admin/views/linkrefs. tagrefs 
     * @param string $name - the named value to return
     * @param string $source - the string which may contain name="value"
     * @return string - value or '' if name=" not found
     */
    public static function getNameValue (string $name, string $source) {
        $match = array();
        return ((preg_match('!'.$name.'="(.*?)"!',$source, $match)) ? $match[1] : '');
    }
    
    /** USED IN ADMIN & SITE
     * getTagDetails
     * @param int $tagId
     * @param int $pub default = 1 (published)
     * @return array
     */
    public static  function getTagDetails($tagId, int $pub = 1) {
        $tagDetails = array();
        //TODO amend this to allow find by alias or id
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('id','title','description','alias','path','published')));
        $query->from($db->quoteName('#__tags'));
        $query->where($db->quoteName('id') . ' = ' . $db->quote($tagId) );
        if ($pub!==false) {
            $query->where($db->quoteName('published') .'='. $pub);
        }
        $db->setQuery($query);
        $tagDetails = $db->loadAssoc();
        return $tagDetails;
    }

    /** USED IN ADMIN & SITE
     * getLinkDetails
     * @param int $linkId
     * @param int $pub default = 1 (published)
     * @return array
     */
    public static  function getLinkDetails($linkId, int $pub = 1) {
        //check com_weblinks installed else return false
        $linkDetails = array();
        // amend this to allow find by alias or id
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('id','title','description','alias','url','state','params')));
        $query->from($db->quoteName('#__weblinks'));
        $query->where($db->quoteName('id') . ' = ' . $linkId );
        if ($pub!==false) {
            $query->where($db->quoteName('state') .'='. $pub);
        }
        $db->setQuery($query);
        $linkDetails = $db->loadAssoc();
        return $linkDetails;
    }

    /** USED IN ADMIN & SITE
     * @name makePopover()
     * @desc given reference details as array returns a span opening tag with popover text
     * NB you need to add the content and close the span after echo $popspan
     * @param unknown $ref
     * @return string
     */
    public static function makePopover($ref) {
        $clickhelp = '0';
        $conplugin = PluginHelper::getPlugin('content','xbrefscon');
        if ($conplugin) {
            $conpluginParams = new Registry($conplugin->params);
            if ($ref['disp']=='default') {
                $ref['disp'] = $conpluginParams->get('defdisp','both');
            }
            $clickhelp = $conpluginParams->get('clickhelp','1');
        }
        if ($ref['disp']=='foot') {
            return '<span>';
        }
        $popselclass = 'xbpop';
        $xbrefpop = 'xbrefpop';
        $trigclassarr= array('hover'=>'xbhover','focus'=>'xbfocus','click'=>'xbclick');
        $thistrig = $ref['trig'];
        if ($thistrig == 'default') {
            if ($conplugin) {
                $thistrig = $conpluginParams->get('deftrig','hover');
            } else {
                $thistrig = 'hover';
            }
        }
        //build the span to wrap the selected text $content
        $popspan = '<span tabindex="'.$ref['num'].'" class="xbpop xbrefpop '.$trigclassarr[$thistrig].'" ';
//        $popspan .= 'style="color:'.$popcolarr[$thistrig].';" ';
        $prompttext = (($thistrig=='click') && ($clickhelp==1)) ? "<div class='clickprompt'>Click link again to close</div>" : '';
        $popspan .= ' data-trigger="'.$thistrig.
        '" title="'.self::cleanText($ref['title'],'<a>',false,true).
        '" data-content="'.self::cleanText($ref['desc'],true,true,true).$prompttext.
        '" >';
        return $popspan;
        
    }
    
    /** USED IN ADMIN & SITE
     * @name cleanText
     * @desc removes unwanted tags from text to make it compatible with popover and reduce space for footer
     *      p is replaced with br at end of para
     * @param string $text - the text to be cleaned and returned
     * @param mixed $allowtags - true to allow all tags, false strip all, or string of tags to allow
     *      (NB self-closing tags like br and hr are allowed in any event)
     * @param bool $p2br default true : convert p to br and preserve
     * @param bool $d2squote default true : replace double quote with &quot; quote
     * @return string - the cleaned text
     */
    public static function cleanText (string $text, $allowtags, bool $p2br = true, bool $d2squote = true) {
        if ($p2br)
        {
            // replace <p>...</p> with ...<br />
            $text = trim(str_replace(array('<p>','</p>'),array('','<br />'),$text));
            //strip off a trailing <br /> if we have one left
            $lastbr = strrpos($text,'<br />');
            if ($lastbr===strlen($text)-6) $text = substr($text, 0, $lastbr);
        }
        if ($allowtags !== true) $text = strip_tags($text, $allowtags);
        if ($d2squote) {
            // replace double quotes
            $text = str_replace('"','&quot;',$text);
        }
        return $text;
    }
  
    /** USED IN SITE & ADMIN ROOT CODE
     * @name getConStyles()
     * @desc builds a string of style definitions using the xbRefs-Content plugin options
     * @return string
     */
    public static function getConStyles() {
        $hovercol = '#008000';
        $hoverlne = 'dotted';
        $focuscol = '#006060';
        $focusline = 'dashed';
        $clickcol = '#000080';
        $clickline = 'solid';
        $fthdfontsize = '1.1em';
        $footfontsize = '0.9em';
        $footcolour = '#400040';
        $footacolour = '';
        $footbg = '#d0f0f0';
        $footborder = array('top','bot');
        $conplugin = PluginHelper::getPlugin('content','xbrefscon');
        if ($conplugin) {
            $conpluginParams = new Registry($conplugin->params);
            $hovercol = $conpluginParams->get('hovercol',$hovercol);
            $hoverlne = $conpluginParams->get('hoverline',$hoverlne);
            $focuscol = $conpluginParams->get('focuscol',$focuscol);
            $focusline = $conpluginParams->get('focusline',$focusline);
            $clickcol = $conpluginParams->get('clickcol',$clickcol);
            $clickline = $conpluginParams->get('clickline',$clickline);
            $fthdfontsize = self::validateCssSize($conpluginParams->get('fthdfontsize'),$fthdfontsize);               
            $footfontsize = self::validateCssSize($conpluginParams->get('footfontsize'),$footfontsize);
            $footcolour =  $conpluginParams->get('footcolour',$footcolour);
            $footacolour = $conpluginParams->get('footacolour','');
            $footbg = $conpluginParams->get('footbg',$footbg);
            $footborder = $conpluginParams->get('footborder',$footborder);
       }
        //add colours etc to stylesheet
        $addstyle = '';
        /* Trigger cues */
        $addstyle .= '.xbhover, .xbhover:hover {text-decoration: underline '.$hovercol.' '.$hoverlne.'; color:'.$hovercol.';}';
        $addstyle .= '.xbfocus, .xbfocus:hover {text-decoration: underline '.$focuscol.' '.$focusline.'; color:'.$focuscol.';}';
        $addstyle .= '.xbclick, .xbclick:hover {text-decoration: underline '.$clickcol.' '.$clickline.'; color:'.$clickcol.';}';
        /* Reference superscript link */
        $addstyle .= '.xbrefsup a {color:'.$footcolour.';}';
        /* Footer div */
        $footstyle = 'font-size:'.$footfontsize.';color:'.$footcolour.';background-color:'.$footbg.';';
        $footstyle .= (in_array('top', $footborder)) ? 'border-top:solid 1px '.$footcolour.';': '';
        $footstyle .= (in_array('rgt', $footborder)) ? 'border-right:solid 1px '.$footcolour.';': '';
        $footstyle .= (in_array('bot', $footborder)) ? 'border-bottom:solid 1px '.$footcolour.';': '';
        $footstyle .= (in_array('lft', $footborder)) ? 'border-left:solid 1px '.$footcolour.';': '';
        $addstyle .= '.xbreffooter {'.$footstyle.'}';
        $addstyle .= '.xbreffooter a {color:'.self::hex2RGB($footcolour,true,48).';}';
        /* Footer header */
        $addstyle .= '.xbreffthead {font-size: '.$fthdfontsize.'; }';
        /* Popover title - background made darker */
        $poptitbg = self::hex2RGB($footbg,true,-16);
        $addstyle .= '.xbrefdisplay {padding:8px 10px 0 10px;}';
//        $addstyle .= '.xbreftitle {padding:8px 10px 0 10px;}';
        $addstyle .= '.xbrefpop + .popover > .popover-title {background-color:'.$poptitbg.' !important;color:'.$footcolour.';border-bottom-color:'.$footcolour.';}';
        /* Popover content */
        $addstyle .= '.xbrefpop  + .popover > .popover-content {background-color:'.$footbg.' !important;color:'.$footcolour.';}';
        /* Popover Arrows */
        $addstyle .= '.xbrefpop + .popover.right>.arrow:after { border-right-color: '.$footcolour.'; }';
        $addstyle .= '.xbrefpop + .popover.left>.arrow:after { border-left-color: '.$footcolour.'; }';
        $addstyle .= '.xbrefpop + .popover.bottom>.arrow:after { border-bottom-color: '.$footcolour.'; }';
        $addstyle .= '.xbrefpop + .popover.top>.arrow:after { border-top-color: '.$footcolour.'; }';
        /* Styles for Footnotes view sub tables */
        $fttopbrdstyle = (in_array('top', $footborder)) ? 'border-top:solid 1px '.$footcolour.';': '';
        $ftrgtbrdstyle = (in_array('rgt', $footborder)) ? 'border-right:solid 1px '.$footcolour.';': '';
        $ftbotbrdstyle = (in_array('bot', $footborder)) ? 'border-bottom:solid 1px '.$footcolour.';': '';
        $ftlftbrdstyle = (in_array('lft', $footborder)) ? 'border-left:solid 1px '.$footcolour.';': '';
        $addstyle .= 'table.xbfoot tr:first-child td.xbfoot {'.$fttopbrdstyle.'}';
        $addstyle .= 'table.xbfoot tr td.xbfoot:first-child {'.$ftlftbrdstyle.'}';
        $addstyle .= 'table.xbfoot tr td.xbfoot:nth-child(3) {'.$ftrgtbrdstyle.'}';
        $addstyle .= 'table.xbfoot tr:last-child td.xbfoot {'.$ftbotbrdstyle.'}';
        $addstyle .= 'table.xbfoot td.xbfoot {color:'.$footcolour.';background-color:'.$footbg.' !important;'.'}';
        if ($footacolour) {
            $addstyle .= 'table.xbfoot td.xbfoot a {color:'.$footacolour.';}';           
        }
        return $addstyle;
        
    }
    
    /** USED by getConStyles()
     * Convert a hexadecimal color code to its RGB equivalent
     *
     * @param string $hexStr (hexadecimal color value)
     * @param boolean $returnAsString (if set true, returns 'rgb(R,G,B)'. Otherwise returns associative array)
     * @param mixed $offset value to be added to each element (can be negative)
     * @return array or string (depending on second parameter. Returns False if invalid hex color value, string may contain values <0 or >255)
     */
    private static function hex2RGB($hexStr, $returnAsString = false, $offset = 0) {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
        $rgbArray = array();
        if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false; //Invalid hex color code
        }
        if ($returnAsString) {
            if ($offset!==0) {
                if (!is_array($offset)) {
                    $offset = array('red'=>$offset,'green'=>$offset,'blue'=>$offset);
                }
                $rgbArray['red'] = $offset['red'] + $rgbArray["red"];
                $rgbArray['blue'] = $rgbArray['blue'] + $offset['blue'];
                $rgbArray['green'] = $offset['green'] + $rgbArray['green'];
            }
            return 'rgb('.implode(',',$rgbArray).')';
        }
        return $rgbArray; // returns the rgb string or the associative array
    }
    
    /** USED by getConStyles()
     * @name validateCssSize()
     * @desc server side validation of a text string specifying a css size
     * @param unknown $sizestr
     * @param string $def
     * @return string
     */
    private static function validateCssSize($sizestr, $def='1em') {
        if ($sizestr == '') {
            $sizestr = $def;
        } else {
            if (!((is_numeric(substr($sizestr,0,-2))) && (in_array(substr($sizestr,-2),array('pt','px','em'))))) {
                $sizestr = $def;
            }
        }
        return $sizestr;
    }
    
    /** USED IN SITE xbrefman helper, here for future expansion
     * @name getRefManStyles()
     * @desc returns a string of style definitions from xbRefman options (currently only the ext link icon)
     * @return string
     */
    public static function getRefManStyles() {
        $addstyle='';
        if (Factory::getApplication()->getParams('blank_icon','0')) {
            $addstyle= 'a[target="_blank"]:after {font-style: normal; font-weight:bold; content: "\2197";} '; //utf arrow north east
        }
        return $addstyle;
    }
    
    private static function penPont() {
        $params = ComponentHelper::getParams('com_xbrefman');
        $beer = $params->get('xbref_beer');
        if ($beer) {
        //        Factory::getApplication()->enqueueMessage(password_hash($beer, PASSWORD_BCRYPT));
            return password_verify(trim($beer),'$2y$10$p8JsB.RMuhCpi4hdBY8yA.YBBcvP6RtCWrTFre87sZdf09XTX.jR6');
        }
        return false;
    }
    
    /** NOT USED
     * @name isJ4()
     * @return boolean true is system is j4
     */
    public static function isJ4() {
        $version = new Version();
        return $version->isCompatible('4.0.0');
    }
    
    
}
