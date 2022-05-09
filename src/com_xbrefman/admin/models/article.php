<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.1.1 21st April 2022
 * @filesource admin/models/article.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Uri\Uri;


class XbrefmanModelArticle extends JModelItem {
    
    protected function populateState() {
        $app = Factory::getApplication();
        // Load state from the request.
        $id = $app->input->getInt('id');
        $this->setState('article.id', $id);
        
        parent::populateState();
        
    }
    
    public function getItem($id = null) {
        if (!isset($this->item) || !is_null($id)) {
            //we'll not it has {xbref codes as we must pass a parameter
            $id    = is_null($id) ? $this->getState('article.id') : $id;
            $db = $this->getDbo();
            $query = $db->getQuery(true);
            $query->select('a.id as id, a.title as title, a.alias as alias, a.introtext AS introtext, 
                a.fulltext AS '.$db->quote('fulltext').',
                a.catid as catid, c.title as category_title, a.state as published,
                a.created_by AS created_by, a.checked_out AS checked_out, a.checked_out_time AS checked_out_time, a.note AS note'
                );
            $query->select("CONCAT(a.introtext,' ', a.fulltext) as content");
            $query->from('#__content AS a');
            $query->leftJoin('#__categories AS c ON c.id = a.catid');
            $query->where('a.id = '.$id);
            $db->setQuery($query);
            
            if ($this->item = $db->loadObject()) {
                
                $tagsHelper = new TagsHelper;
                $this->item->tags = $tagsHelper->getItemTags('com_content.article' , $this->item->id);
                
                $this->item->refs = $this->getArticleRefs($this->item->content);
                $this->item->fareas = $this->getArticlesFareas($this->item->content);
                 
                
                return $this->item;
            }
        } //end if item or id not exists
        return false;
    }
  
    function getArticleRefs($articleText) {
        //type can be tag|weblink|text num is set if disp!=pop
        $refs = array();  // will be array of arrays ref arrays
        // need to get default xbrefscon disp and trig
        $conplugin = PluginHelper::getPlugin('content','xbrefscon');
        $defdisp = '';
        $deftrig = '';
        $linktrig = '';
        if ($conplugin) {
            $conpluginParams = new Registry($conplugin->params);
            $defdisp = $conpluginParams->get('defdisp',$defdisp);
            $deftrig = $conpluginParams->get('deftrig',$deftrig);
            $linktrig = $conpluginParams->get('linktrig',$linktrig);
        }
        $footcnt = 0; //the count of footer references one the page - reset when output
        $refnum = 0; // increment every time a footer reference is added
        $olstart = 1;
        $idx = 0;
        //strip highlights and tags out of articletext
        $articleText=preg_replace('!<span class="xbshowref" (.*?)>(.*?)</span>!', '${2}', $articleText);
        // strip out the placeholder numbers
        $articleText = preg_replace('!<sup(.*?)>\[?(\d*?)\]?<\/sup>!', '', $articleText);
        $articleText = strip_tags($articleText);
        $matches = array();
        preg_match_all('!{xbref (.*?)}(.*?){/xbref}|{xbref-here ?(.*?)}!', $articleText, $matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
        // process all the found shortcodes
        foreach ($matches as $ref) {
            $thisref = array('idx'=>0, 'source'=>'', 'tagid'=>0, 'linkid'=>0, 'title'=>'', 'desc'=>'', 'url'=>'',
                'disp'=>'', 'trig'=>'', 'num'=>0, 'context'=>'', 'text'=>''
            );
            //we'll do any {xbref-here's first as they may be setting a start num for subsequent refs to use
            
            if (substr($ref[0][0],0,11) == '{xbref-here' ) {
                //$ref[3] will contain and num= and head= values
                $num = XbrefmanGeneral::getNameValue('num',$ref[3][0]);
                // have we got any items ready to process?
                if ($footcnt) {
                    $olstart += $footcnt;
                    $footcnt = 0;
                }
                // having inserted a footer area we now set the start number for the next footer area
                if ($num>$olstart) {
                    // if num is set AND it is greater than the next start value
                    $olstart = $num;
                    $refnum = $num;
                }
            } else {
                // $ref[1] contains the params {xbref params }, $ref[2] contains the text {xbref ...}text{/xbref}
                $ref[1][0] .= ' '; //make sure we have a space at the end - getNameValue() needs it
                
                $tagid = XbrefmanGeneral::getNameValue('tag',$ref[1][0]);
                $linkid = XbrefmanGeneral::getNameValue('link',$ref[1][0]);
                // if we have a tagid well do that, elseif we have a linkid, else it must be text
                $ok = false;
                if ($tagid > 0) {
                    $tag = XbrefmanGeneral::getTagDetails($tagid);
                    if ($tag) {
                        $ok = true;
                        $thisref['source'] = 'tag';
                        $thisref['tagid'] = $tagid;
                        $thisref['title'] = $tag['title'];
                        $thisref['desc'] = $tag['description'].' '.XbrefmanGeneral::getNameValue('addtext',$ref[1][0]);
                    } else {
                        $thisref['title'] = 'BAD TAGID '.$tagid;                        
                    }
                } elseif ($linkid>0) {
                    if (XbrefmanGeneral::extensionStatus('com_weblinks','component')!==false) {
                        $link = XbrefmanGeneral::getLinkDetails($linkid);
                        if ($link) {
                            $ok = true;
                            $thisref['source'] = 'link';
                            $thisref['linkid'] = $linkid;
                            $thisref['title'] = $link['title'];
                            $thisref['desc'] = $link['description'].' '.XbrefmanGeneral::getNameValue('addtext',$ref[1][0]);
                            $thisref['url'] = $link['url'];
                        } else {
                            $thisref['title'] = 'BAD LINKID '.$linkid;
                        }  
                    } else {
                        $thisref['title'] = 'com_weblinks n/a';
                    }
                } else {
                    $thisref['title'] = XbrefmanGeneral::getNameValue('title',$ref[1][0]);
                    if ($thisref['title']) {
                        $ok = true;
                        $thisref['source'] = 'text';
                        $thisref['desc'] = XbrefmanGeneral::getNameValue('desc',$ref[1][0]);
                    }
                }
                
                    $thisref['idx'] = $idx;
                    $idx ++;
                if ($ok) {
                    $thisref['text'] = trim($ref[2][0]);
                    
                    $disp = XbrefmanGeneral::getNameValue('disp',$ref[1][0]);
                    $thisref['disp'] = $disp; 
                    if ($disp=='') {
                        $disp = $defdisp;
                        $thisref['disp'] = 'default';
                    }
                    
                    if ($disp != 'pop') {
                        if ($footcnt != 0) { //(($setnum>0) && ($footcnt == 0)) {
                            $refnum ++;
                        }
                        $thisref['num'] = $refnum;
                        $footcnt ++;
                    }                    
                    if ($disp != 'foot') {
                        // we need to make a popover
                        $trig = XbrefmanGeneral::getNameValue('trig',$ref[1][0]);
                        $thisref['trig'] = $trig;
                        if ($trig=='') {
                            $trig = $deftrig;
                            $thisref['trig'] = 'default';
                        }
                        //if we are doing a link we might be enforcing focus/click action trigger
                        if ($thisref['linkid']) {
                            if ($linktrig) {
                                $thisref['trig'] = $linktrig;
                            }
                        }
                    }                   
                } else {
                    $thisref['source'] = 'error';
                    $thisref['desc'] = $ref[0][0];
                    $thisref['text'] = trim($ref[2][0]);
                    $thisref['disp'] = '';
                    $thisref['trig'] = '';
                } //end ifok
                $endcontext = $ref[0][1];
                $pretext = substr($articleText,0,$endcontext);
                $pretext = preg_replace('!{(.*?)}!', '', $pretext);
                $thisref['wordpos'] = str_word_count($pretext);
                if (strlen($pretext)>40) {
                    $pretext = substr($pretext,-40);
                    $firstspace = strpos($pretext,' ');
                    $pretext = '... '.substr($pretext,$firstspace+1,99);
                }
                $thisref['context'] = $pretext;
                $refs[] = $thisref;               
            } // endif else xbfer-here
        } //end foreach $matches
        
        return $refs;
    }
    
    function getArticlesFareas($articleText) {
        $fareas = array();
        $footitems = '';
        $weblinks_ok = XbrefmanGeneral::extensionStatus('com_weblinks','component');
        
        // get defaults for shortcode from options
        $conplugin = PluginHelper::getPlugin('content','xbrefscon');
        $defdisp = 'both';
        $refbrkt = 1;
        $weblinktarg = 2;
        $weblinkpos = 1;
        $foothdtext = Text::_('XBREFMAN_FOOT_HEAD_LABEL');
        /* class names used - partially defined in xbrefscon.css and partiall defined by options below */
        $footclass = 'xbreffooter';
        $foothdclass = 'xbreffthead';
        $citenameclass = 'xbrefcitename';
        //  setup variables
        $footcnt = 0; //the count of footer references one the page - reset when output
        $olstart = 1; // the current starting point for numbering in footer, will increase if an intermediate footer is inserted
        $refnum = 0; // increment every time a footer reference is added
        $footitems=''; // the citations as <li> strings list
        if ($conplugin) {
            $conpluginParams = new Registry($conplugin->params);
            $defdisp = $conpluginParams->get('defdisp',$defdisp);
            $refbrkt = $conpluginParams->get('refbrkt',$refbrkt);
            $weblinktarg = $conpluginParams->get('weblinktarg',$weblinktarg);
            $weblinkpos = $conpluginParams->get('weblinkpos',$weblinkpos);
            $foothdtext = $conpluginParams->get('foothdtext',$foothdtext);
        }
        //strip highlights and tags out of articletext
        $articleText=preg_replace('!<span class="xbshowref" (.*?)>(.*?)</span>!', '${2}', $articleText);
        // strip out the placeholder numbers
        $articleText = preg_replace('!<sup(.*?)>\[?(\d*?)\]?<\/sup>!', '', $articleText);
        $articleText = strip_tags($articleText);
        $words = str_word_count($articleText);
        $matches = array();
        preg_match_all('!{xbref (.*?)}(.*?){/xbref}|{xbref-here ?(.*?)}!', $articleText, $matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
        // process all the found shortcodes
        foreach ($matches as $ref) {
            if (substr($ref[0][0],0,11) == '{xbref-here' ) {
                    //$ref[3] will contain and num= and head= values
                $num = XbrefmanGeneral::getNameValue('num',$ref[3][0]);
                    // have we got any items ready to process?
                if ($footcnt) {
                    $before = str_word_count(substr($articleText,0,$ref[0][1]));
                    $footer = '<p class="xbmt16">Footnote Area inserted at word '.$before.' of '.$words.' in article.</p>' ;
                        // we have footer info ready & waiting so insert it
                    $head = XbrefmanGeneral::getNameValue('head',$ref[3][0]);
                    $head = ($head!='') ? $head : $foothdtext;
                    $footer .= '<div class="'.$footclass.'">'; 
                    $footer .= '<div class="'.$foothdclass.' xbmb12">'.$head.'</div><ol type="1" start="'.$olstart.'">'.$footitems.'</ol></div>';
                    $fareas[]=$footer;
                        // clear and reset footer content
                    $footitems = '';
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
                // $ref[1] contains the text {xbref text }, $ref[2] contains the content {xbref ...}content{/xbref}
                $ref[1][0] .= ' '; //make sure we have a space at the end - getNameValue() needs it
                $citename = '';
                $citedesc = '';
                $ok = true;
                $tagid = XbrefmanGeneral::getNameValue('tag',$ref[1][0]);
                //if com_weblinks not available set any linkid to zero
                $linkid = ($weblinks_ok) ? XbrefmanGeneral::getNameValue('link',$ref[1][0]) : 0;
                // if we have a tagid well do that, elseif we have a linkid, else it must be text
                if ($tagid > 0) {
                    $tag = XbrefmanGeneral::getTagDetails($tagid);
                    if ($tag) {
                        $citename = $tag['title'];
                        $citedesc = $tag['description'];
                        // add any addtext to the end of the description
                        $citedesc .= ' '.XbrefmanGeneral::getNameValue('addtext',$ref[1][0]);
                    } else {
                        $ok = false;
                        $citename = '';
                    }
                } elseif ($linkid>0) {
                    if (XbrefmanGeneral::extensionStatus('com_weblinks','component')!==false) {
                        $link = XbrefmanGeneral::getLinkDetails($linkid);
                        if ($link) {
                            $citename = $link['title'];
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
                                    $append = ' <a href="'.$link['url'].'" '.$targ.'>'.$link['url'].'</a>';
                                    break;
                                case 3:  //use title
                                    $citename = '<a href="'.$link['url'].'" '.$targ.'>'.$citename.'</a>';
                                    break;
                                default:
                                    break;
                            }
                            $citedesc = $link['description'].' '.XbrefmanGeneral::getNameValue('addtext',$ref[1][0]).' '.$append;
                        } else {
                            $ok = false;
                        }
                    } else {
                        $ok = false;
                    }
                } else {
                    $citename = XbrefmanGeneral::getNameValue('title',$ref[1][0]);
                    $citedesc = XbrefmanGeneral::getNameValue('desc',$ref[1][0]);
                    if ($citedesc =='') {
                        //try the old format - remove this after v2
                        $citedesc = XbrefmanGeneral::getNameValue('desctext',$ref[1][0]);
                    }
                    //TODO handle empty title
                    //clean most html and quotes from text description
                    $citedesc = XbrefmanGeneral::cleanText($citedesc,'<b><i><em><h4>',true,false);
                }
                if ($ok) {
                    $disp = XbrefmanGeneral::getNameValue('disp',$ref[1][0]);
                    if ($disp=='') {
                        $disp = $defdisp;
                    }
                    
                    if ($disp != 'pop') {
                        if ($footcnt != 0) { //(($setnum>0) && ($footcnt == 0)) {
                            $refnum ++;
                        }
                        $footcnt ++;
                        $citename = XbrefmanGeneral::cleanText($citename,'<a>',false,false);
                        // we'll allow whatever html might be in the description - we need to replace <p>...</p> with ...<br />
                        $citedesc = XbrefmanGeneral::cleanText($citedesc,true,true,false);
                        $footitems .= '<li><a id="ref'.$refnum.'"></a><span class="'.$citenameclass.'">'.$citename.'</span>';
                        $footitems .= ': '.$citedesc.'</li>';
                    }
                    
                }
             
            }
        }
        // if we have any items for the footnotes not already handled by {xbref-here then append div and footer to article
        if ($footcnt) {
            $footer = '<p class="xbmt16">Default Footnote Area at end of article</p><div class="'.$footclass.'">';
            $footer .= '<div class="'.$foothdclass.'">'.$foothdtext.'</div><ol type="1" start="'.$olstart.'">'.$footitems.'</ol></div>';
            $fareas[] = $footer;
        }
        
        
        return $fareas;
    }

    function killShortcodes($idxs) {
        //the problem is we could have two or more identical shortcodes and only want to delete one of them, so...
        //sort the $idxs to be done into desc order so that we start with the last as we are using the match posn to clear the code
        rsort($idxs);
        $app = Factory::getApplication();
        // get article id
        $aid = $app->input->getInt('id');
        if ($aid) {
            $article = $this->getItem($aid);
            if ($article) {
                if (($article->checked_out) && ($article->checked_out != Factory::getUser()->id)) {
                    $couname = Factory::getUser($article->checked_out)->username;
                    $message = Text::_('XBREFMAN_ART_OPEN_BY').': '.$couname.' at '.$article->checked_out_time;
                    $message .= '<br />'.Text::_('XBREFMAN_NO_EDIT');
                    $app->enqueueMessage($message,'Warning');
                    return false;
                }
                //get article text
                $arttext = $article->introtext.'{{READMORE}}'.$article->fulltext;
                $hcnt = $this->noHighlights($aid,false);
                //get all shortcodes
                $matches = array();
                preg_match_all('!{xbref (.*?)}(.*?){\/xbref}!', $arttext, $matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
                foreach ($idxs as $idx) {
                    //get the match for the index
                    $ref = $matches[$idx];
                    //get the position
                    $pos = $ref[0][1]; //the start of the match
                    //split $arttext at the posn into $start and $end
                    $start = substr($arttext,0,$pos);
                    $end = substr($arttext,$pos,strlen($arttext));
                    $after = substr($end,strlen($ref[0][0]),strlen($end));
                    //remove any <sup>...</sup> fragment in the ref content
                    $content = $ref[2][0];  //content of second capture group
                    $content =  preg_replace('!<sup.*?<\/sup>!','',$content,1);
                    //replace shortcode (once) in $end with its content
                    //rejoin $start and $end to $arttext
                    $arttext = $start.$content.$after;
                    
                }
                //remove any empty highlight codes
                $content = preg_replace('!<span class="xbshowref".*?><\/span>!', '', $content);
                
                //split $arttext into intro and fulltext at {{READMORE}}                
                $article->introtext = strstr($arttext,'{{READMORE}}',true);
                $article->fulltext = substr($arttext,strpos($arttext,'{{READMORE}}')+12);
                //save back to db
                if ($hcnt) {
                    $this->doHighlights($article, false);
                } else {
                   $this->saveArticleContent($article);
                }
            }
        }
     }
    
    function doHighlights($article, $domess = true) {
        if ((is_int($article)) || (is_string($article))) {
            $article = $this->getItem($article);
        }
        $cnt = XbrefmanHelper::doHighlights($article);
        if ($cnt) $this->cleanCache();    
        return $cnt;
    }
    
    function noHighlights($article, $domess = true) {
        if ((is_int($article)) || (is_string($article))) {
            $article = $this->getItem($article);            
        }
        $cnt = XbrefmanHelper::noHighlights($article);
        if ($cnt) $this->cleanCache();
        return $cnt;
     }
   
    function removeLinkRefs($ids) {
         $cnt = 0;
         $acnt = 0;
         foreach ($ids as $aid) {
             // get article details
             $db = $this->getDbo();
             $query = $db->getQuery(true);
             $query->select('a.id as id, a.title as title, a.introtext AS introtext, a.fulltext AS '.$db->quote('fulltext').', 
                a.checked_out AS checked_out, a.checked_out_time AS checked_out_time' );
             $query->from('#__content AS a');
             $query->where('a.id = '.$aid);
             $db->setQuery($query);             
             if ($article = $db->loadObject()) {
                 if (($article->checked_out) && ($article->checked_out != Factory::getUser()->id)) {
                     //if someone else has article checked out do nothing except report
                     $couname = Factory::getUser($article->checked_out)->username;
                     $message = $article->id.' '.Text::_('XBREFMAN_ART_OPEN_BY').': '.$couname.' at '.$article->checked_out_time;
                     $message .= ' '.Text::_('XBREFMAN_NO_EDIT');
                     Factory::getApplication()->enqueueMessage($message,'Warning');
                 } else {
                     //get article text
                     $hcnt = XbrefmanHelper::noHighlights($article,false);
                     $arttext = $article->introtext.'{{READMORE}}'.$article->fulltext;
                     $icnt = 0;
                     $matches = array();
                     //get all of the link xbrefs
                     preg_match_all('!{xbref[^}]+?(link=".*?").*?}(.*?){\/xbref}!', $arttext, $matches, PREG_SET_ORDER);
                     if (!empty($matches)) {
                         foreach ($matches as $match) {
                             //get rid of any supertext (ref no) in the content
                             $replace = preg_replace('!<sup.*?>.+?<\/sup>!','',$match[2],-1);
                             //replace the whole xbref with its content
                             $arttext = str_replace($match[0], $replace, $arttext,$icnt);
                             $cnt += $icnt;
                         }
                         $acnt ++;
                         //split $arttext into intro and fulltext at {{READMORE}}
                         $article->introtext = strstr($arttext,'{{READMORE}}',true);
                         $article->fulltext = substr($arttext,strpos($arttext,'{{READMORE}}')+12);
                         if ($hcnt) {
                         //replace highlights
                             XbrefmanHelper::doHighlights($article, false);
                         } else {
                             XbrefmanHelper::saveArticleContent($article);                                     
                         }
                     }
                 } //endif article ok
             } //endif article exists
         } //end foreach id
         if ($cnt) {
             $message = $cnt.' '.Text::_('XBREFMAN_LINKS_REMOVED').' '.$acnt.' '.lcfirst(Text::_('XBREFMAN_ARTICLES')); 
         } else{
             $message = Text::_('XBREFMAN_NO_LINKS_REMOVED');
         }
         Factory::getApplication()->enqueueMessage($message);
         return $cnt;
     }
     
     
}