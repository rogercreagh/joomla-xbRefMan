<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.2.1 23rd April 2022
 * @filesource site/views/references/tmpl/default.php
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
?>

<div class="xbrefman">
	<?php if(($this->header['showheading']) || ($this->header['title'] != '') || ($this->header['text'] != '')) {
		echo XbrefmanHelper::sitePageheader($this->header);
	} ?>
	
	<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=references'); ?>" method="post" name="adminForm" id="adminForm">       
		<?php  // Search tools bar
			if ($this->search_bar) {
				$hide = '';
				echo '<div class="row-fluid"><div class="span12">';
	            echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this,'hide'=>$hide));       
	         echo '</div></div>';
			} 
		?>
		<div class="row-fluid pagination" style="margin-bottom:10px 0;">
			<div class="pull-right">
				<p class="counter" style="text-align:right;margin-left:10px;">
					<?php // TODO need a customised pagination system as we are not using the model query to get the actual refs, just the articles which contain the refs
		//echo $this->pagination->getResultsCounter().'.&nbsp;&nbsp;'; 
					   //echo $this->pagination->getPagesCounter().'&nbsp;&nbsp;'.$this->pagination->getLimitBox().' per page'; ?>
				</p>
			</div>
			<div>
				<?php  //echo $this->pagination->getPagesLinks(); ?>
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
        		<table class="table table-hover" id="xbRefmanRefsList">	
        		<thead>
        			<tr>
    					<th>
    						<?php echo HTMLHelper::_('searchtools.sort','XBREFMAN_TITLE','title',$listDirn,$listOrder); ?> 
    					</th>	
        				<th>
        					<?php echo Text::_('XBREFMAN_DESCRIPTION'); //will include url for weblink ?>
        				</th>
					</tr>
        			
        		</thead>
        		<tbody>
    			<?php foreach ($this->items as $i => $ref) : 
    			?>
    				<tr style="background-color:#f9f9f9;">
    					<td>
    						<a href="<?php echo $reflink.'&key='.$ref['refkey']; ?>"
    								title="<?php echo Text::_('XBREFMAN_LINK_REF'); ?>" >
    							<b><?php echo $this->escape($ref['title']); ?></b></a>    								
    					</td>
    					<td>
    						<?php echo $ref['desc']; ?>
    					</td>
    				</tr>
    				<tr>
   						<td colspan="2" style="padding:2px 4px 4px 30px;">
                  			
    					<details>
    					<summary><i class="xb09">used in <?php echo count($ref['articles']); ?> articles</i> <span class="xbclick">Show details</span></summary>
    						<table width="95%" class="xb09">
    							<tbody>
    								<?php $lastart = 0; ?>
    								<?php foreach($ref['articles'] as $i=>$art ) : ?>
    								<?php $alink = XbrefmanHelperRoute::getArticleRoute($art['artid']); ?>
    									<?php if ($art['artid'] != $lastart ) : ?>
    									<tr>
    									<td></td>
    										<td><a href="<?php echo $alink; ?>"> 
    											<?php echo $art['arttitle']; ?>
    											</a><br /><i><?php echo count($art['artrefinfos']).' '.Text::_('XBREFMAN_INSTANCES'); ?></i>
    										</td>
    										<td>
    											<?php foreach ($art['artrefinfos'] as $refinfo): ?>
        											
											<?php echo '<i><a href="'.$alink.'#refid'.$refinfo['idx'].'">'.Text::_('XBREFMAN_NEAR_WORD').' '.$refinfo['pos'].'</a></i><br /> ';
        											echo $refinfo['context'].' ';
        											if ($refinfo['text']){
        											    echo XbrefmanGeneral::makePopover(array('disp'=>$refinfo['disp'],'trig'=>$refinfo['trig'],
        											        'num'=>$refinfo['num'],'title'=>$ref['title'],'desc'=>$ref['desc']));
        											    echo '<b>'.$refinfo['text'].'</b></span> ';
        											}
        											if ($refinfo['disp'] != 'pop') {
        											    echo '<a href="'.$alink.'#ref'.$refinfo['num'].'">['.$refinfo['num'].']</a>';
        											}
        											?>
        											<br />
    											<?php endforeach; ?>
    											</ul>
    										</td>
    									</tr>
    									<?php endif; ?>
    									<?php $lastart = $art['artid']; ?>
    								<?php endforeach; ?>
    								
    							</tbody>
    						</table>
    					</details>
    					<br />
    					</td>
    				</tr>
        		<?php endforeach; ?>
        		</tbody>
        		</table>

				<?php endif; ?>
			</div>
		</div>
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
