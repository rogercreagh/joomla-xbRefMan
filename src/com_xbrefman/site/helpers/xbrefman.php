<?php
/*******
 * @package xbMaps Component
 * @version 0.9.3.2 24th April 2022
 * @filesource site/helpers/xbrefman.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
//use Joomla\CMS\Component\ComponentHelper;
//use Joomla\CMS\Language\Text;

class XbrefmanHelper extends ContentHelper {
	
	public static function sitePageHeader($displayData) {
		$header ='';
		if (!empty($displayData)) {
			$header = '	<div class="row-fluid"><div class="span12 xbpagehead">';
			if ($displayData['showheading']) {
				$header .= '<div class="page-header"><h1>'.$displayData['heading'].'</h1></div>';
			}
			if ($displayData['title'] != '') {
				$header .= '<h3>'.$displayData['title'].'</h3>';
				if ($displayData['subtitle']!='') {
					$header .= '<h4>'.$displayData['subtitle'].'</h4>';
				}
			}
			if ($displayData['text'] != '') {
				$header .= '<p>'.$displayData['text'].'</p>';
			}
			$header .='</div></div>';
		}
		return $header;
	}

	public static function getRefArticles(string $type, $key) {
	    //for tag and link $type and $key (id) will define the filter
	    //for text $key is alias rather than actual title (urlsafe) so need to get all articles with 
	    //text refs and then filter out the ones whose title alias doesn't match 
	    $arts = array();
	    if ($type == 'text') {
	        $filter = 'title="';
	    } else {
	        $filter = $type.'="';
	    }
	        $filter .= ($key) ? $key.'"' : '';
	    $targ = "'".'{xbref.+' .$filter. '.*}'."'";
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select($db->quoteName(array('a.id','a.title','a.alias','a.introtext','a.fulltext','a.catid','a.state',
	        'a.created_by','a.checked_out','a.checked_out_time','a.note','c.title'),
	        array('id','title','alias','introtext','fulltext','catid','published',
	            'created_by','checked_out','checked_out_time','note','category_title')));
	        $query->select("CONCAT(a.introtext,' ', a.fulltext) as content");
	        $query->from($db->quoteName('#__content').' as a');
	        $query->leftJoin($db->quoteName('#__categories').' AS c ON c.id = a.catid');
	        $query->where('CONCAT( `introtext`," ",`fulltext`) REGEXP '.$targ);
	        $query->where('(state = 1)');
	        // could add category and tag filters
	        $query->order('title ASC');
	        $db->setQuery($query);
	        $arts = $db->loadObjectList();
	        if (!is_null($arts)) {
	            $tagsHelper = new TagsHelper;
	            foreach ($arts as $art) {
	                $art->tags = $tagsHelper->getItemTags('com_content.article' , $art->id);
	                $art->refs = XbrefmanHelper::getArticleRefs($art->content,$filter);
	            }
	        }
	        return $arts;
	}
	
	public static function getArticleRefs($articleText, $filter = '',$tagfilter = '' ) {
	    $fcond = 'eq';
	    if (strpos($filter,'!=')!==false) {
	        $fcond = 'ne';
	        $filter = str_replace('!=','=',$filter);
	    }
	    $articleText = self::cleanArticleText($articleText);
	    $refs = array();
	    $matches = array();
	    $targ = '!{xbref (.*?)}(.*?){\/xbref}|{xbref-here ?(.*?)}!';
	    $weblinks_ok = XbrefmanGeneral::extensionStatus('com_weblinks','component');
	    $conplugin = PluginHelper::getPlugin('content','xbrefscon');
	    $defdisp = 'both';
	    $deftrig = 'hover';
	    $linktrig = 'click';	    
	    $refbrkt = 1;
	    $weblinktarg = 2;
	    $weblinkpos = 1;
	    //  setup variables
	    $footcnt = 0; //the count of footer references one the page - reset when output
	    $olstart = 1; // the current starting point for numbering in footer, will increase if an intermediate footer is inserted
	    $refnum = 1; // increment every time a footer reference is added
	    $idx = 0; // increment for each valid ref in sequence;
	    if ($conplugin) {
	        $conpluginParams = new Registry($conplugin->params);
	        $defdisp = $conpluginParams->get('defdisp',$defdisp);
	        $refbrkt = $conpluginParams->get('refbrkt',$refbrkt);
	        $deftrig = $conpluginParams->get('deftrig',$deftrig);
	        $linktrig = $conpluginParams->get('linktrig',$linktrig);
	        $weblinktarg = $conpluginParams->get('weblinktarg',$weblinktarg);
	        $weblinkpos = $conpluginParams->get('weblinkpos',$weblinkpos);
	    }
	    preg_match_all($targ, $articleText, $matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
	    foreach ($matches as $ref) {
	        //$ref[0][0] the whole match
	        //$ref[0][1] the starting position of the match in article text (NB with tags stripped)
	        //$ref[1][0] the variables for xbref
	        //$ref[2][0] the text enclosed by the xbref (NB with any <sup> content stripped)
	        //$ref[3][0] the variables for xbref-here
	        if (substr($ref[0][0],0,11) == '{xbref-here' ) {
	            //$ref[3] will contain and num= and head= values
	            $num = XbrefmanGeneral::getNameValue('num',$ref[3][0]);
	            // have we got any items ready to process?
	            if ($footcnt) {
	                // clear and reset footer content
	                $olstart += $footcnt;
	                $footcnt = 0;
	            }
	            // having inserted a footer area we now set the start number for the next footer area
	            if (($num>0) && ($num>$olstart)) {
	                // if num is set AND it is greater than the next start value
	                $olstart = $num;
	                $refnum = $num;
	            }
	        } else {
	            // $ref[1] contains the params {xbref params }, $ref[2] contains the content {xbref ...}content{/xbref}
	            $ref[1][0] .= ' '; //make sure we have a space at the end - getNameValue() needs it
	            $reftitle = '';
	            $desc = '';
	            $type = ''; //pop foot or both
//	            $idx = 1; //the order on the page
	            $ok = true;
	            $tagid = XbrefmanGeneral::getNameValue('tag',$ref[1][0]);
	            //if com_weblinks not available set any linkid to zero
	            $linkid = ($weblinks_ok) ? XbrefmanGeneral::getNameValue('link',$ref[1][0]) : 0;
	            // if we have a tagid well do that, elseif we have a linkid, else it must be text
	            if ($tagid > 0) {
	                $tag = XbrefmanGeneral::getTagDetails($tagid);
	                if ($tag) {
	                    $type = 'tag';
	                    $reftitle = $tag['title'];
	                    $desc = $tag['description'];
	                    // add any addtext to the end of the description
	                    $desc .= ' '.XbrefmanGeneral::getNameValue('addtext',$ref[1][0]);
	                    $idx ++;
	                } else {
	                    $ok = false;
	                    $reftitle = '';
	                }
	            } elseif ($linkid>0) {
	                $link = XbrefmanGeneral::getLinkDetails($linkid);
	                if ($link) {
	                    $type ='weblink';
	                    $reftitle = $link['title'];
	                    $targ = 'target=';
	                    switch ($weblinktarg) {
	                        case 1:
	                            $targ .= '"_blank"';
	                            break;
	                        case 2:
	                            //$host = parse_url($link['url'],PHP_URL_HOST);
	                            if (Uri::isInternal($link['url'])) {
	                                $targ = '';
	                            } else {
	                                $targ .= '"_blank"';
	                            }
	                            break;
	                        default:
	                            $targ='';
	                            break;
	                    }
	                    $append = '';
	                    switch ($weblinkpos) {
	                        case 1: // append Visit...
	                            $append = ' <a href="'.$link['url'].'" '.$targ.'><i>Visit '.$link['title'].'</i></a>';
	                            break;
	                        case 2: // append url
	                            $append = 'Url: <a href="'.$link['url'].'" '.$targ.'>'.$link['url'].'</a>';
	                            break;
	                        case 3:  //use title
	                            $reftitle = '<a href="'.$link['url'].'" '.$targ.'>'.$reftitle.'</a>';
	                            break;
	                        default:
	                            break;
	                            //                            $ref['url'] = $link['url'];
	                    }
	                    $desc = $link['description'].' '.XbrefmanGeneral::getNameValue('addtext',$ref[1][0]).' '.$append;
	                    $idx ++;
	                } else {
	                    $ok = false;
	                }
	            } else {
	                $reftitle = XbrefmanGeneral::getNameValue('title',$ref[1][0]);
	                //$textid = OutputFilter::stringURLSafe($reftitle);
	                $textid = urlencode($reftitle);
	                if ($reftitle) {
	                    $idx ++;
	                    $type = 'text';
	                    $desc = XbrefmanGeneral::getNameValue('desc',$ref[1][0]);
	                    if ($desc =='') {
	                        //try the old format - remove this after v2
	                        $desc = XbrefmanGeneral::getNameValue('desctext',$ref[1][0]);
	                    }
	                    //clean most html and quotes from text description
	                    $desc = XbrefmanGeneral::cleanText($desc,'<b><i><em><h4>',true,false);
	                } else {
	                    $ok = false;
	                }
	            }
	            if ($ok) {
	                // first some bits we need to do even if the ref doesn't match the filter
	                $thisref = array();
	                $thisref['num'] = 0;
	                $disp = XbrefmanGeneral::getNameValue('disp',$ref[1][0]);
	                if ($disp=='') {
	                    $disp = $defdisp;
	                }
	                if ($disp != 'pop') {
	                    $thisref['num'] = $refnum;
	                    $refnum ++;
	                    $footcnt ++;
	                }	                
	            }
	            if ($filter) {
                    $fok = ($fcond ==='eq') ? (strpos($ref[0][0],$filter)!==false) : (strpos($ref[0][0],$filter)===false);
	            } else {
	                $fok = true;
	            }
	            if ($ok && $fok) {

	                $thisref['idx'] = $idx; //order on page
	                $thisref['disp'] = $disp; //pop|foot|both
	                $thisref['trig'] = '';
	                $thisref['type'] = $type; //tag|link|text
	                $thisref['tagid'] = $tagid; //(($linkid) ? $linkid : ''); //if tag or link id of tag or link
	                $thisref['linkid'] = $linkid;
	                $reftitle = trim(strip_tags($reftitle));
	                $thisref['refkey'] = ($tagid) ? 'tag-'.$tagid : (($linkid) ? 'weblink-'.$linkid : 'text-'.$textid); //unique key
	                $thisref['refid'] = ($tagid) ? $tagid : (($linkid) ? $linkid : $textid); //non-unique id (tag and link might have same id)
	                
	                // we'll allow whatever html might be in the description - we need to replace <p>...</p> with ...<br />
	                $thisref['title'] = $reftitle;
	                $thisref['desc'] = $desc;
	                $thisref['url'] = ($type == 'link') ? $link['url'] : '';
	                $thisref['text'] =  preg_replace('!<sup.+?<\/sup>!','',$ref[2][0]);
	                $thisref['text'] = trim(strip_tags($thisref['text']));
	                $endcontext = $ref[0][1];
	                $pretext = substr($articleText,0,$endcontext);
	                //$pretext = strip_tags($pretext); //already done
	                $pretext =  preg_replace('!{(.*?)}!', '', $pretext);
	                $thisref['pos'] =  str_word_count($pretext);
	                if (strlen($pretext)>40) {
	                    $pretext = substr($pretext,-40);
	                    $pretext = '...'.substr($pretext,strpos($pretext,' ')+1,strlen($pretext));
	                }
	                $thisref['context'] = $pretext;
	                if ($disp != 'foot') {
	                    //get poptype
	                    $trig = XbrefmanGeneral::getNameValue('trig',$ref[1][0]);
	                    if ($trig=='') {
	                        $trig = $deftrig;
	                    }
	                    $thisref['trig'] = $trig;
	                    //if we are doing a link we might be enforcing focus/click action trigger
	                    if ($thisref['linkid']) {
	                        if ($linktrig) {
	                            $thisref['trig'] = $linktrig;
	                        }
	                    }
	                    
	                }
	                $refs[] = $thisref;
	            } //endif ok
	        } // endif not xbref-here
	    } //endforeach match
	    return $refs;
	}
	
	/** USED IN getArticleRefs(),
	 * cleanArticleText()
	 * @desc Removes xbHighlights and all htmltags from article text
	 * @param unknown $articleText
	 * @return string
	 */
	private static function cleanArticleText($articleText) {
	    $articleText = preg_replace('!<span class="xbshowref".*?>(.*?)<\/span>!', '${1}', $articleText, -1);
	    $articleText = preg_replace('!<sup(.*?)>\[?((\d*?)|N)\]?<\/sup>!', '', $articleText);
	    $articleText = strip_tags($articleText);
	    //also remove shortcodes
	    return $articleText;
	}
	
	
	
	public static function findTextRefInArticle($artid, $alias) {
	    $ref = array();
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select("CONCAT(a.introtext,' ', a.fulltext) as content");
        $query->from($db->quoteName('#__content').' as a');
        $query->where('a.id = '.$artid);
	    $db->setQuery($query);
	    $art = $db->loadObject();
	    if (!is_null($art)) {
            $matches = array();
            //we need to find the title that matches the alias (key)
            if (preg_match_all('!{xbref[^}]+?title="(.+?)"[^}]+?desc="(.+?)"!',$art->content, $matches, PREG_SET_ORDER)) {
                $i = 0;
                $found = false;
                while ((!$found) && ($i < count($matches))) {
                    if (OutputFilter::stringURLSafe($matches[$i][1]) == $alias) {
                        $found = true;
                        $ref['title'] = $matches[$i][1];
                        $ref['description'] = $matches[$i][2];
                    }
                    $i++;                    
                }
            }
	    }
	    return $ref;
	}
}
