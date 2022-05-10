<?php
/*******
 * @package xbRefMan Component
 * @version 1.0.0 10th May 2022
 * @filesource site/views/footnotes/tmpl/default.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleTags', null, array('placeholder_text_multiple' => JText::_('JOPTION_SELECT_TAG')));
HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::_('script', 'media/com_xbrefman/js/xbrefman.js', array('version' => 'auto', 'relative' => false));

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape(strtolower($this->state->get('list.direction')));
if (!$listOrder) {
    $listOrder='title';
    $listDirn = 'ascending';
}
require_once JPATH_COMPONENT.'/helpers/route.php';

$reflink = 'index.php?option=com_xbrefman&view=reference';
//$alink = 'index.php?option=com_content&view=article&id=';
$clink = 'index.php?option=com_content&view=category&tmpl=default&id=';

?>

<div class="xbrefman">
	<?php if(($this->header['showheading']) || ($this->header['title'] != '') || ($this->header['text'] != '')) {
		echo XbrefmanHelper::sitePageheader($this->header);
	} ?>
	
	<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=footnotes'); ?>" method="post" name="adminForm" id="adminForm">       
		<?php  // Search tools bar
			if ($this->search_bar) {
				$hide = '';
//				if ((!$this->show_cats) || ($this->hide_catsch)) { $hide .= 'filter_category_id, filter_subcats,';}
//				if ((!$this->show_tags) || $this->hide_tagsch) { $hide .= 'filter_tagfilt,filter_taglogic,';}
				echo '<div class="row-fluid"><div class="span12">';
	            echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this,'hide'=>$hide));       
	         echo '</div></div>';
			} 
		?>
		<div class="row-fluid pagination" style="margin-bottom:10px 0;">
			<div class="pull-right">
				<p class="counter" style="text-align:right;margin-left:10px;">
					<?php echo $this->pagination->getResultsCounter().'.&nbsp;&nbsp;'; 
					echo $this->pagination->getPagesCounter().'&nbsp;&nbsp;'.$this->pagination->getLimitBox().' '.Text::_('XBREFMAN_PER_PAGE'); ?>
				</p>
			</div>
			<div>
				<?php  echo $this->pagination->getPagesLinks(); ?>
            	<?php //echo 'sorted by '.$orderNames[$listOrder].' '.$listDirn ; ?>
			</div>
		</div>
		<div class="row-fluid">
        	<div class="span12">
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-no-items">
						<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
        		<table class="table table-striped table-hover" style="table-layout:fixed;" id="xbrefmanFootnotes">	
        		<thead>
        			<tr>
    					<th>
    						<?php echo HTMLHelper::_('searchtools.sort','XBREFMAN_ARTICLE','title',$listDirn,$listOrder); ?> 
    					</th>	
        				<th><?php echo Text::_('XBREFMAN_FOOTNOTES'); ?>
        				</th>
    						<th class="hidden-phone">
        				<?php if($this->show_cats || $this->show_tags) : ?>
    							<?php if ($this->show_cats) {
                                    echo Text::_('XBREFMAN_CATEGORY');
    					       }
    					       if (($this->show_cats) && ($this->show_tags)) {
    					           echo ' &amp; ';
    					       }
    					       if($this->show_tags) {
    					           echo Text::_( 'XBREFMAN_TAGS' ); 
    					       } ?>                
                		<?php endif; ?>
    						</th>
					</tr>
        			
        		</thead>
        		<tbody>
    			<?php foreach ($this->items as $i => $item) : 
    			?>
    			<?php if (count($item->footnotes)>0) : ?>
    				<?php $alink = XbrefmanHelperRoute::getArticleRoute($item->id); 
    				    $clink = XbrefmanHelperRoute::getArticleCategoryRoute($item->catid);
    				?>
    				<tr>
    					<td>
    						<a href="<?php echo $alink; ?>"
    								title="<?php echo Text::_('XBREFMAN_LINK_ARTICLE'); ?>" >
    								<b><?php echo $this->escape($item->title); ?></b></a>    								
    				</td>
    				<td class="xbnit xb095">
    					<?php echo count($item->footnotes); ?>
    					 footnotes 
    				</td>
    					<td class="hidden-phone">
    				<?php if($this->show_cats || $this->show_tags) : ?>
     						<?php if($this->show_cats) : ?>	
     							<p>
     							<?php if($this->show_cats>0) : ?>											
    								<a class="label label-success" href="<?php echo $clink; ?>"><?php echo $item->category_title; ?></a>
    							<?php else: ?>
    								<span class="label label-success"><?php echo $item->category_title; ?></span>
    							<?php endif; ?>
    							</p>
    						<?php endif; ?>
    						<?php if($this->show_tags) {
    							$tagLayout = new FileLayout('joomla.content.tags');
        						echo $tagLayout->render($item->tags);
    						}
        					?>
					<?php endif; ?>
    					</td>
    				</tr>
    				<tr>
    					<td colspan="3" style="padding:2px 4px 4px 30px;">
     					<details>
    					<summary><span class="xbclick"><?php echo Text::_('XBREFMAN_SHOW_DETS'); ?></span></summary>
    						<table width="95%" class="xbfoot xb09" style="border-collapse:separate;">
    							<tbody>
    								<?php foreach ($item->footnotes as $i=>$ref) : ?>
    								<?php if ($ref['type'] != 'pop'): ?>
    								<tr>
                    					<td class="xbfoot" style="padding-left:20px;">
                    						<?php echo '<a href="'.$alink.'#ref'.$ref['num'].'">['.$ref['num'].'] </a>'; ?> 
                    					</td>
                    					<td class="xbfoot" style="padding-left:10px;">
                    						<?php if ($ref['type'] != 'text') : ?>
    											<a href="<?php echo Route::_($reflink.'&key='.$ref['refkey']); ?>"
    												title="<?php echo Text::_('XBREFMAN_LINK_REF'); ?>" >
    										<?php endif; ?>
                        						<b><?php echo str_replace(' ','&nbsp;',$ref['title']); ?></b>
                         					<?php if ($ref['type'] != 'text') : ?></a><?php endif; ?>                            					                 					
                    					</td>
                    					<td class="xbfoot"><?php echo $ref['desc']; ?></td>
                    					<td style="width:33%;">
											<?php echo '<i><a href="'.$alink.'#refid'.$ref['idx'].'">'.Text::_('XBREFMAN_NEAR_WORD').' '.$ref['pos'].'</a></i><br /> ';
											echo $ref['context'].' ';
											if ($ref['text']){
											    echo XbrefmanGeneral::makePopover(array('disp'=>$ref['disp'],'trig'=>$ref['trig'],
											        'num'=>$ref['num'],'title'=>$ref['title'],'desc'=>$ref['desc']));
											    echo '<b>'.$ref['text'].'</b></span> ';
											}											
											?>
                        				</td>   	
                    				</tr>
                    				<?php endif; ?>							    
    								<?php endforeach; ?>
    							</tbody>
    						</table>
    					</details>
    					<br />
    					</td>
    				</tr>
    				<?php endif; //item has footnotes ?>
        		<?php endforeach; //item  ?>
        		</tbody>
        		</table>

				<?php endif; ?>
			</div>
		</div>
				<?php echo HTMLHelper::_('form.token'); ?>
	</form>
    <div class="clearfix"></div>
    <p><?php echo XbrefmanGeneral::credit();?></p>
</div>
<?php if ($this->auto_close_dets) : ?>
<script>
	jQuery("details").click(function(event) {
  		jQuery("details").not(this).removeAttr("open");
	});
</script>
<?php endif; ?>
