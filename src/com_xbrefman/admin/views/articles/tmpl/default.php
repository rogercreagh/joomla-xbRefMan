<?php
/*******
 * @package xbRefMan Component
* @version 0.9.2.1 22nd April 2022
 * @filesource admin/views/articles/tmpl/default.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleTags', null, array('placeholder_text_multiple' => Text::_('JOPTION_SELECT_TAG')));
HTMLHelper::_('formbehavior.chosen', 'select');

$user = Factory::getUser();
$userId  = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape(strtolower($this->state->get('list.direction')));
if (!$listOrder) {
    $listOrder='title';
    $listDirn = 'ascending';
}
$orderNames = array('title'=>Text::_('XBREFMAN_TITLE'), 
    'id'=>'id','publish_up'=>Text::_('XBREFMAN_PUBLISH_DATE'),
    'category_title'=>Text::_('XBREFMAN_CATEGORY'),
    'published'=>Text::_('XBREFMAN_PUBLISHED_STATE'),'a.ordering'=>Text::_('XBREFMAN_ARTICLE_ORDER'));

$alink = 'index.php?option=com_xbrefman&view=article&id=';
?>
<script type="text/javascript">
    Joomla.submitbutton = function(task) {
        message = '';
		if (task == 'articles.remlinksc') {
			message = 'This will remove all weblink references using from the selected articles'
        }
        if (message != '') {
            if (confirm(message)) {
                Joomla.submitform(task);
            } else {
                return false;
            }
        } else {
            Joomla.submitform(task);
        }
    }
</script>
<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=articles'); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-sidebar-container">
		<?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container">
	<div class="pull-right span2 xbmt10">
		<p style="text-align:right;"><b>
			<?php $fnd = $this->pagination->total;
			echo $fnd .' '. Text::_(($fnd==1)?'Article':'Articles').' '.Text::_('XBREFMAN_FOUND');
            ?>
		</b></p>
	</div>
	<h3><?php echo Text::_('XBREFMAN_ARTICLES_HEADER'); ?></h3>
	<div class="clearfix"></div>
	<?php
        // Search tools bar
        echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
    ?>
	<div class="clearfix"></div>
	
	<?php $search = $this->searchTitle; ?>
	<?php if ($search) : ?>
		<?php echo '<p>'.Text::_('XBREFMAN_SEARCHED_FOR').' <b>'; ?>
		<?php if (stripos($search, 'i:') === 0) {
                echo trim(substr($search, 2)).'</b> '.Text::_('XBREFMAN_AS_ID');
            } elseif (stripos($search, 'c:') === 0) {
                echo trim(substr($search, 2)).'</b> '.Text::_('XBREFMAN_IN_CONTENT');
            } else {
				echo trim($search).'</b> '.Text::_('XBREFMAN_IN_TITLE');
			}
			echo '</p>';
        ?>	
	<?php endif; ?> 
	<div class="pagination">
		<?php  echo $this->pagination->getPagesLinks(); ?>
		<br />
	    <?php echo 'sorted by '.$orderNames[$listOrder].' '.$listDirn ; ?>
	</div>

	<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>	
		<table class="table table-striped table-hover" id="xbrefmanList">	
			<thead>
				<tr>
					<th class="hidden-phone center" style="width:25px;">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th class="nowrap center" style="width:55px">
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort','XBREFMAN_TITLE','title',$listDirn,$listOrder); ?> 
						<span class="xbnit xb09"><?php echo Text::_('XBREFMAN_CLICK_DETAILS'); ?></span>
					</th>					
					<th>
						<?php echo Text::_('XBREFMAN_WORD_CNT_OPEN');?>
					</th>
					<th>
						<?php echo Text::_('XBREFMAN_REFERENCES');?>
					</th>
					<th>
						<?php echo Text::_('XBREFMAN_DISPLAY');?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort','XBREFMAN_CATS','category_title',$listDirn,$listOrder); ?> 
						 &amp; 
						<?php echo Text::_('XBREFMAN_TAGS');?>
					</th>
					<th class="nowrap hidden-tablet hidden-phone" style="width:45px;">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder );?>
					</th>
				</tr>
			</thead>
			<tbody>
    			<?php
    			foreach ($this->items as $i => $item) :
                    $canEdit    = $user->authorise('core.edit', 'com_content.article.'.$item->id);
                    $canCheckin = $user->authorise('core.manage', 'com_checkin') 
                        || $item->checked_out==$userId || $item->checked_out==0;
                    $canEditOwn = $user->authorise('core.edit.own', 'com_content.article.'.$item->id) && $item->created_by == $userId;
                    $canChange  = $user->authorise('core.edit.state', 'com_content.article.'.$item->id) && $canCheckin;
                    
                    $taglist = '';
                    if ($item->refcnts['tag']>0) {
                        foreach ($item->refcnts['tagtits'] as $tit) {
                            $taglist .= '<span class="label label-info">'.$tit.'</span> ';
                        }
                    }
                    $linklist = '';
                    if ($item->refcnts['link']>0) {
                        foreach ($item->refcnts['linktits'] as $tit) {
                            $linklist .= '<span class="label label-cyan">'.$tit.'</span> ';
                        }
                    }
                    $textlist = '';
                    if ($item->refcnts['text']>0) {
                        foreach ($item->refcnts['texttits'] as $tit) {
                            $textlist .= '<span class="label label-mag">'.$tit.'</span> ';
                        }
                    }
                    $words = str_word_count(XbrefmanHelper::strip_shortcodes(strip_tags($item->content)));
                    ?>
				<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">	
					<td class="center hidden-phone">
						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
					</td>
					<td class="center">
						<div class="btn-group">
							<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'article.', $canChange, 'cb'); ?>
							<?php if ($item->note!=""){ ?>
								<span class="btn btn-micro active hasTooltip" title="" data-original-title="<?php echo '<b>'.Text::_( 'XBREFMAN_NOTE' ) .'</b>: '. htmlentities($item->note); ?>">
									<i class="icon- xbinfo"></i>
								</span>
							<?php } else {?>
								<span class="btn btn-micro inactive" style="visibility:hidden;" title=""><i class="icon-info"></i></span>
							<?php } ?>
						</div>
					</td>
					<td>
						<p class="xbtitlelist">
						<?php if ($item->checked_out) {
						    $couname = Factory::getUser($item->checked_out)->username;
						    echo HTMLHelper::_('jgrid.checkedout', $i, Text::_('XBREFMAN_ART_OPEN_BY').': '.$couname, $item->checked_out_time, 'article.', false);
						} ?>
						<?php if ($canEdit || $canEditOwn) : ?>
							<a href="<?php echo Route::_($alink.$item->id);?>"
								title="<?php echo Text::_('XBREFMAN_REFS_DETAILS'); ?>" >
								<b><?php echo $this->escape($item->title); ?></b></a> 
						<?php else : ?>
							<?php echo $this->escape($item->title); ?>
						<?php endif; ?>
                        <br />                        
						<?php $alias = Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias));?>
                        <span class="xbnit xb08"><?php echo $alias;?></span>
					</td>
					<td><div  style="max-width:275px;">
						<p class="xb095">
   							<span class="xbnit"><?php echo $words.' '.Text::_('XBREFMAN_WORDS_SENTENCE'); ?>: </span>
							<?php echo XbrefmanHelper::makeSummaryText($item->content,200); ?>
                        </p>
                        </div>
					</td>
					<td>
						<?php  /*         $refcnts = array('tag'=>0, 'tagids'=>array(), 'link'=>0, 'linkids'=>array(), 'badids'=>'', 'text'=>0, 
                'foot'=>0, 'pop'=>0, 'both'=>0, 'badpop'=>0, 'defdisp'=>0, 
                'hover'=>0, 'focus'=>0, 'click'=>0, 'deftrig'=>0, 
                'footers'=>0 );*/
						$totrefs = $item->refcnts['tag'] + $item->refcnts['link'] + $item->refcnts['text'];
						?>
						<p><i><b><?php echo ($totrefs==0)?  Text::_('XBREFMAN_NO_VALID_REFS') : 
                            $totrefs.' '.Text::_('XBREFMAN_VALID_REFS'); ?></b></i></p>
						<?php if ($item->refcnts['tag']>0) : ?>
    						<p><span class="badge badge-info"><?php echo $item->refcnts['tag'].'</span> '.Text::_('XBREFMAN_TAG_REFS_USE');?>
    							&nbsp;&nbsp;<?php echo $taglist; ?>
    						</p>						
						<?php endif; ?>
						<?php if ($item->refcnts['link']>0) : ?>
    						<p><span class="badge badge-cyan"><?php echo $item->refcnts['link'].'</span> '.Text::_('XBREFMAN_LINK_REFS_USE');?>
    							&nbsp;&nbsp;<?php echo $linklist; ?>
    						</p>
						<?php endif; ?>
						<?php if ($item->refcnts['text']>0) : ?>
    						<p><span class="badge badge-mag"><?php echo $item->refcnts['text'].'</span> '.Text::_('XBREFMAN_TEXT_REFS_USE');?>
    							&nbsp;&nbsp;<?php echo $textlist; ?>
    						</p>						
						<?php endif; ?>
						<div>
							<?php if ((count($item->refcnts['badtags']) > 0) || (count($item->refcnts['badlinks']) > 0) || ($item->refcnts['badpop'] > 0)) :?>
								<p style="color:red;"><?php echo Text::_('XBREFMAN_ERRS_FOUND').'<br />'; ?>:
								<?php if ($item->refcnts['badpop'] > 0) echo $item->refcnts['badpop'].' '.Text::_('XBREFMAN_POPOVER_ERRORS').'<br />'; ?>
								<?php if (count($item->refcnts['badtags']) > 0 ) { 
								    echo Text::_('XBREFMAN_INVALID_TAGS').': ';
								    foreach ($item->refcnts['badtags'] as $id) { echo $id.', ';}
								    echo '<br />';
								}
                                ?>	
								<?php if (count($item->refcnts['badlinks']) > 0 ){
								    echo Text::_('XBREFMAN_INVALID_WEBLINKS').': ';
								    foreach ($item->refcnts['badlinks'] as $id) { echo $id.', ';}
								    echo '<br />';
								}
                                ?>	
								</p>
							<?php endif; ?>
						</div>
					</td>
					<td>
						<div class="xb09">
    						<p><i><?php echo Text::_('XBREFMAN_DISPLAY_TYPES'); ?></i>
    						<br /><?php 
    						echo ($item->refcnts['pop']>0)? Text::_('XBREFMAN_POP').': '.$item->refcnts['pop'].', ':'';
    						echo ($item->refcnts['foot']>0)? Text::_('XBREFMAN_FOOT').': '. $item->refcnts['foot'].', ':'';
    						echo ($item->refcnts['both']>0) ? Text::_('XBREFMAN_BOTH').': '.$item->refcnts['both'].', ':'';
    						echo ($item->refcnts['defdisp']>0)? Text::_('XBREFMAN_DEFAULT').' ('.$this->defdisp.'): '.$item->refcnts['defdisp']:'';
    						  ?> 
    						</p>
    						<?php if ($item->refcnts['hover']+$item->refcnts['focus']+$item->refcnts['click']+$item->refcnts['deftrig']) : ?>
        						<p><i><?php echo Text::_('XBREFMAN_TRIGGER_TYPES'); ?></i>
        						<br /><?php 
        						echo ($item->refcnts['hover']>0) ? Text::_('XBREFMAN_HOVER').': '.$item->refcnts['hover'].', ':'';
        						echo ($item->refcnts['focus']>0) ? Text::_('XBREFMAN_FOCUS').': '.$item->refcnts['focus'].', ':'';
        						echo ($item->refcnts['click']>0) ? Text::_('XBREFMAN_CLICK').': '.$item->refcnts['click'].', ':'';
        						echo ($item->refcnts['deftrig']>0) ? Text::_('XBREFMAN_DEFAULT').' ('.$this->deftrig.'): '.$item->refcnts['deftrig']:'';
        						?>
        						</p>
        					<?php endif; ?>
    						<p><i><?php if ($item->refcnts['footers']==0) { 
        						    echo Text::_('XBREFMAN_NO_FAREA'); 
        						} elseif ($item->refcnts['footers']==1) { 
        						    echo Text::_('XBREFMAN_ONE_FAREA'); 
        						} else {
        						    echo $item->refcnts['footers'].' '.Text::_('XBREFMAN_MANY_FAREAS'); 
        						} ?>
    						</i></p>					
						</div>
					</td>
					<td>
						<p><a class="label label-success" href="<?php echo $item->catid; ?>" 
    							title="">
    								<?php echo $item->category_title; ?>
    							</a>
						</p>						
						<ul class="inline">
						<?php foreach ($item->tags as $t) : ?>
							<li><a href="<?php $t->id; ?>" class="label label-info">
								<?php echo $t->title; ?></a>
							</li>												
						<?php endforeach; ?>
						</ul>						    											
					</td>
					<td class="center hidden-phone">
						<?php echo $item->id; ?>
					</td>
				</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
<div class="clearfix"></div>
<p><?php echo XbrefmanGeneral::credit();?></p>
