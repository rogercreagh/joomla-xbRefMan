<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.2.1 22nd April 2022
 * @filesource admin/views/textrefs/tmpl/default.php
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

HTMLHelper::_('script', 'media/com_xbrefman/js/xbrefman.js', array('version' => 'auto', 'relative' => false));

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
$aedit = 'index.php?option=com_content&task=article.edit&id=';
?>
  <script type="text/javascript">
    Joomla.submitbutton = function(task) {
        message = '';
        if (task == 'textrefs.textdelscs') {
            message = 'This will remove the selected Text Refs from the selected articles';
        } else if (task == 'textrefs.text2tags') {
			message = 'This create new tags from the selected Text Refs and change the shortcode to a Tag Ref'
        }
        if (message != '') {
            if (confirm(message)) {
                Joomla.submitform(task);
            } else {
                return false;
            }
        }
    }
</script>

<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=textrefs'); ?>" method="post" name="adminForm" id="adminForm">
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
	<h3><?php echo Text::_('XBREFMAN_TEXTREFS_HEADER'); ?></h3>
    	<p><?php echo Text::_('XBREFMAN_TEXTREFS_SUBHEADER'); ?></p>
    	<p class="form-horizontal"><?php echo $this->form->renderField('parent_tag'); ?></p>
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
					<th class="nowrap center" style="width:55px">
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort','Article','title',$listDirn,$listOrder); ?> 
					</th>	
					<th>
						<?php Text::_('XBREFMAN_WORD_CNT_OPEN'); ?>
					</th>				
					<th>
						<?php echo Text::_('XBREFMAN_CAT_TAGS');?>
					</th>
					<th class="nowrap hidden-tablet hidden-phone" style="width:45px;">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder );?>
					</th>
				<tr>
			</thead>
			<tbody>
    			<?php foreach ($this->items as $i => $item) : 
        			$canEdit    = $user->authorise('core.edit', 'com_content.article.'.$item->id);
        			$canCheckin = $user->authorise('core.manage', 'com_checkin')
        			|| $item->checked_out==$userId || $item->checked_out==0;
        			$canEditOwn = $user->authorise('core.edit.own', 'com_content.article.'.$item->id) && $item->created_by == $userId;
        			$canChange  = $user->authorise('core.edit.state', 'com_content.article.'.$item->id) && $canCheckin;
        			//$words = str_word_count(strip_tags($item->content));
        			$words = str_word_count(XbrefmanHelper::strip_shortcodes(strip_tags($item->content)));
        			?>
    				<tr>
    					<td>
						<div class="btn-group">
							<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'article.', false, 'cb'); ?>
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
    							<a href="<?php echo Route::_($alink.$item->id); ?>"
    								title="<?php echo Text::_('XBREFMAN_ART_DETAILS'); ?>" >
    								<b><?php echo $this->escape($item->title); ?></b></a>    								
    						<?php if ($canEdit || $canEditOwn) : ?>
    							<a href="<?php echo Route::_($aedit.$item->id); ?>" title="Edit article"><span class="fas fa-edit"></span></a>
    						<?php endif; ?>
                            <br />                        
    						<?php $alias = Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias));?>
                            <span class="xbnit xb08"><?php echo $alias;?></span></p>
						</td>
    					<td><p><?php echo $words; ?> words 
        						<span class="xb095">
           							<span class="xbnit"><?php echo Text::_('XBREFMAN_INITIAL_CHARS'); ?>: </span>
        							<?php echo XbrefmanHelper::makeSummaryText($item->content,200); ?>
                                </span>
    					</td>
    					<td><span class="label label-success"><?php echo $item->category_title; ?></span></td>
    					<td><?php echo $item->id; ?></td>
    				</tr>
    				<tr>
    					<td></td>
    					<td colspan="4">
    						<table width="95%">
    							<thead>
    								<tr>
                    					<th class="hidden-phone center" style="width:25px;">
                    						<?php echo HTMLHelper::_('grid.checkall'); ?>
                    					</th>
    									<th><?php echo Text::_('XBREFMAN_TITLE'); ?></th>
    									<th><?php echo Text::_('XBREFMAN_DESCRIPTION'); ?></th>
    									<th><span title="<?php echo Text::_('XBREFMAN_APPROX_WORD_COUNT'); ?>">
    									    <?php echo Text::_('XBREFMAN_AT_WORD'); ?></span></th>
    									 <th><?php echo Text::_('XBREFMAN_DISPLAY'); ?></th>
    									        <th><?php echo Text::_('XBREFMAN_CONTEXT'); ?></th>   									
    								</tr>
    							</thead>
    							<tbody>
    								<?php foreach ($item->textrefs as $i=>$ref) : 
    								if ($ref['disp'] == '') $ref['disp'] = 'default';
    								if ($ref['trig'] == '') $ref['trig'] = 'default';
    								?>
    								<tr>
                    					<td class="center hidden-phone">
                    						<?php echo HTMLHelper::_('grid.id', $i, $item->id.'-'.$i); ?>
                    					</td>
                    					<td><span class="label label-mag"><?php echo $ref['title']; ?></span></td>
                    					<td><?php echo $ref['desc']; ?></td>
                    					<td><?php echo $ref['pos'].' / '.$words; ?></td>
                    					<td><?php echo $ref['disp']; ?></td>   								    
                    					<td><?php echo $ref['context'].' ';
                    					if (!$ref['text']) {
                    					    echo '<i>['.Text::_('XBREFMAN_EMPTY').']</i>';
                    					} else {
                    					    echo XbrefmanGeneral::makePopover($ref).'<b>'.$ref['text'].'</b></span>'; 
                    					}?></td>   	
                    				<tr>							    
    								<?php endforeach; ?>
    							</tbody>
    						</table>
    					</td>
    				</tr>
    			
    			<?php endforeach; ?>				
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
		