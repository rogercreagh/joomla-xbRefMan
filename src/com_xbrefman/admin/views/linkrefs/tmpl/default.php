<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.1 13th April 2022
 * @filesource admin/views/linkrefs/tmpl/default.php
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
$welink = 'index.php?option=com_weblinks&task=weblink.edit&id='
?>
  <script type="text/javascript">
    Joomla.submitbutton = function(task) {
        message = '';
        if (task == 'linkrefs.addselect') {
            message = 'This will add the selected tags to the xbRefs-Button tag list to facilitate re-using them in other articles';
        } else if (task == 'linkrefs.linkdelsc') {
			message = 'This will remove all references using the selected tags from all articles, the tags themselves will not be deleted'
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

<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=linkrefs'); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-sidebar-container">
		<?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container">
	<div class="pull-right span2 xbmt10">
		<p style="text-align:right;"><b>
			<?php $fnd = $this->pagination->total;
			echo $fnd .' '. Text::_(($fnd==1)?'Weblink':'Weblinks').' '.Text::_('XBREFMAN_FOUND');
            ?>
		</b></p>
	</div>
	<h3><?php echo Text::_('XBREFMAN_LINKREFS_HEADER'); ?></h3>
	<p><?php echo Text::_('XBREFMAN_LINKREFS_SUBHEADER'); ?>
	<?php if ($this->xbrefsbtn) : ?>
        <a class="btn btn-small xbml10" href="<?php echo $this->editbtnopts; ?>" target="_blank">
        	<span class="icon-out-2"></span><?php echo Text::_('XBREFMAN_BTN_OPTS'); ?></a></p>
        <p>
    	<?php if($this->linkcats) : ?>
    		<?php echo Text::_('XBREFMAN_CATS_USED'); ?>  
    		<?php echo $this->linkcats; ?>
    	<?php else : ?>
    		<?php echo Text::_('XBREFMAN_WEBLINK_NO_CAT')?>
    	<?php endif; ?>
    	</p><p>
		<?php  if($this->linktaglist) : ?>
    		<?php echo Text::_('XBREFMAN_TAGS_FILTER_USED'); ?>  
        	<?php foreach ($this->linktagparents as $tag) {
        	    echo '<span class="label label-info">'.$tag['title'].'</span>';
        	}
        	echo '&nbsp;';
        	echo ($this->children==0) ? Text::_('XBREFMAN_CHILDREN_EXCLUDED') : Text::sprintf('XBREFMAN_AND_CHILD_TAGS',$this->children);
        	?>
    	<?php else : ?>
    		<?php echo Text::_('XBREFMAN_WEBLINK_NO_TAG')?>
    	<?php endif; ?>
    	</p><p class="xbnit xb09"><?php echo Text::_('XBREFMAN_FILTERING_COMBINES')?></p>
    <?php else: ?>
    	</p>
	<?php  endif; ?>
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
					</th>
                          <?php if($this->xbrefsbtn) : ?>
					<th><span class="hasTooltip"  title="" 
							data-original-title="Green tick if the category is in xbRefs-Button filter for selectable Weblinks">
						<?php echo Text::_('XBREFMAN_SELECTABLE')?></span>
					</th>					
					<?php endif; ?>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'Category', 'category_title', $listDirn, $listOrder);						
						echo ' &amp; '.Text::_( 'XBREFMAN_TAGS' ); ?>
					</th>
					<th>
						<?php echo Text::_('XBREFMAN_URL_ABSTRACT');?>
					</th>
					<th>
						<?php echo Text::_('XBREFMAN_ARTICLES');?>
					</th>
					<th class="nowrap hidden-tablet hidden-phone" style="width:45px;">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder );?>
					</th>
				</tr>
			</thead>
			<tbody>
    			<?php
    			foreach ($this->items as $i => $item) :
    			$canEdit    = $user->authorise('core.edit', 'com_weblinks.weblink.'.$item->id);
    			$canCheckin = $user->authorise('core.manage', 'com_checkin')
    			|| $item->checked_out==$userId || $item->checked_out==0;
    			$canEditOwn = $user->authorise('core.edit.own', 'com_weblinks.weblink.'.$item->id) && $item->created_by== $userId;
    			$canChange  = $user->authorise('core.edit.state', 'com_weblinks.weblink.'.$item->id) && $canCheckin;
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
						    echo HTMLHelper::_('jgrid.checkedout', $i, Text::_('XBREFMAN_WEBLINK_OPEN_BY').': '.$couname, $item->checked_out_time, 'article.', false);
						} ?>
						<?php $labeltitle = '<span class="label label-cyan"><span class="xb12">'.$this->escape($item->title).'</span></span>'; ?>						
						<?php if (($this->weblinkscom) && ($canEdit || $canEditOwn)) : ?>
							<a href="<?php echo Route::_($welink.$item->id);?>"
								title="<?php echo Text::_('XBREFMAN_EDIT_WEBLINK'); ?>" >
								<?php echo $labeltitle; ?>
								</a> 
						<?php else : ?>
							<?php echo $labeltitle; ?>
						<?php endif; ?>
						</span>
                        <br />                        
						<?php $alias = Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias));?>
                        <span class="xbnit xb08"><?php echo $alias;?></span>
					</td>
                    <?php if($this->xbrefsbtn) : ?>
					<td>
					<?php  echo 'by cat: ';
					$tick = '<span class="far fa-check-circle" style="color:green;"></span>';
					$cross = '<span class="far fa-times-circle" style="color:red;"></span>';
					echo ($item->selcat) ? $tick : $cross;
					echo '<br />by tag: ';
					echo ($item->seltag) ? $tick : $cross;
					?>
  					<br />Result: <span class="xb12"><b> <?php echo ($item->selcat && $item->seltag)? $tick : $cross; ?></b></span>
					</td>
					<?php endif; ?>
					<td><p><span class="label label-success"><?php echo $item->category_title; ?></span></p>
						<ul class="inline">
						<?php foreach ($item->taglist as $t) : ?>
							<li><span class="label label-info">
								<?php echo $t; ?></span>
							</li>												
						<?php endforeach; ?>
						</ul>
					</td>
					<td><div  style="max-width:275px;">
						<p><a href="<?php echo $item->url; ?>" target="_blank"><?php echo $item->url; ?></a>
						<p class="xb095">
   							<span class="xbnit"><?php echo Text::_('XBREFMAN_INITIAL_CHARS'); ?>: </span>
							<?php echo XbrefmanHelper::makeSummaryText($item->description,200); ?>
                        </p>
                        </div>
					</td>
					<td>
						<?php if (count($item->articles)>2) : ?>
							<details><summary class="xbclick">Used in <?php echo count($item->articles); ?> articles</summary>
						<?php endif; ?>
    						<?php  foreach ($item->articles as $i=>$linkart) {
    						    echo '<ul>';
    						    if ($i>0) {
    						        echo '<li><a href="'.$alink.$linkart->artid.'">'.$linkart->arttitle.'</a></li>';
    						    }
    						    echo '</ul>';
    						}?>
						<?php if (count($item->articles)>2) : ?>
							</details>
						<?php endif; ?>
					</td>
					<td class="center hidden-phone">
						<?php echo $item->id; ?>
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
		