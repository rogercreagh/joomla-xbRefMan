<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.1.1 21st April 2022
 * @filesource admin/views/article/tmpl/default.php
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

$item = $this->item;
$aelink = 'index.php?option=com_content&task=article.edit&id=';
$editlink = array ('tag'=>'index.php?option=com_tags&task=tag.edit&id=',
    'link'=>'index.php?option=com_weblinks&task=weblink.edit&id=',
    'text'=>'index.php?option=com_content&task=article.edit&id='.$item->id.'&x',
    'error'=>'');
$labtype = array('tag'=>'label-info', 'link'=>'label-mag', 'text'=>'label-cyan', 'error'=>'label-important');

?>
<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=article&id='.$item->id); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-sidebar-container">
		<?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container">
		<h3><?php echo Text::_('XBREFMAN_SCODES_IN').' "'.$item->title.'" '; ?>
			&nbsp;<span class="xb08 xbnit"><a href="<?php echo Route::_($aelink.$item->id); ?>" title="Edit article">
				<?php echo Text::_('XBREFMAN_CLICK_EDIT_ART'); ?> <span class="fas fa-edit"></span></span></a>
		</h3>

		<p><?php echo Text::_('XBREFMAN_ARTICLE_HAS').' '.XbrefmanHelper::wordCount($item->content).' '.Text::_('XBREFMAN_WORDS'); ?>.<p>

		<?php if (empty($item->refs)) :?>
			<p><?php echo Text::_('XBREFMAN_NO_VALID_REFS'); ?></p>
		<?php else : ?>
			<p><?php echo count($item->refs).' '.Text::_('XBREFMAN_REFS_FOUND'); ?></p>
			<table class="table table-striped table-hover" id="xbrefmanItem">
				<thead>
					<tr>
						<th class="hidden-phone center" style="width:25px;">
        					<?php echo HTMLHelper::_('grid.checkall'); ?>
        				</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_INDEX'); ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_SOURCE'); ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_TITLE'); ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_DESCRIPTION').' <span class="xbnit xb09">'.Text::_('XBREFMAN_HTML_STRIPPED').'</span>'; ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_WEBLINK_URL'); ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_CONTEXT').' <span class="xbnit xb09">'.Text::_('XBREFMAN_AT_WORD').'</span>'; ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_TEXT'); ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_DISPLAY'); ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_TRIGGER'); ?>
            			</th>
        		    	<th>
        		    		<?php echo Text::_('XBREFMAN_REFNO'); ?>
            			</th>
            		</tr>
            	</thead>
				<tbody>
					<?php foreach ($item->refs as $i=>$ref) : ?>
						<?php $refid = '';
						if ($ref['tagid']>0) {
						    $refid = $ref['tagid'];
						} elseif ($ref['linkid']>0) {
						    $refid = $ref['linkid']; 
						}
						 ?>
						<tr class="row<?php echo ($i % 2); ?>"
						<?php if ($ref['source']=='error') { echo 'style="color:red;"'; } ?>
                         >
							<td class="center hidden-phone">
            					<?php echo HTMLHelper::_('grid.id', $i, $ref['idx']); ?>
            				</td>
            				<td>
            					<?php echo $ref['idx']; ?>
            				</td>
            				<td>
            					<?php echo $ref['source'].' '.$refid; ?>
            				</td>
            				<td>
            					<a href="<?php echo $editlink[$ref['source']].$refid; ?>">
            						<span class="label <?php echo $labtype[$ref['source']];?>">  
            						<?php echo $ref['title']; ?></span>
            					</a>
            				</td>
            				<td>
            					<?php echo strip_tags($ref['desc']); ?>
            				</td>
            				<td>
            					<?php echo $ref['url']; ?>
            				</td>
            				<td>
            					<?php echo $ref['context'].' ('.$ref['wordpos'].')'; ?>
            				</td>
            				<td><?php if ($ref['trig']) {
            				    echo XbrefmanGeneral::makePopover($ref);
            				    echo $ref['text'].'</span>'; } ?>
            				</td>
            				<td>
            					<?php echo $ref['disp']; ?>
            				</td>
            				<td>
            					<?php echo $ref['trig']; ?>
            				</td>
            				<td>
            					<?php echo ($ref['num']>0)? $ref['num'] :''; ?>
            				</td>
                       </tr> 
					<?php endforeach; ?>
    	
				</tbody>
			</table>
		<?php endif; ?>
		<?php if (!($item->fareas) ) : ?>
			<p><?php echo Text::_('XBREFMAN_NO_FAREAS'); ?></p>
		<?php else : ?>
			<?php foreach ($item->fareas as $farea) {
                echo $farea;			    
			} ?>
		<?php endif; ?>
		
		<h4><?php echo Text::_('XBREFMAN_ARTEXT_RAW'); ?></h4>
        <div class="xbbox xbboxwht xbboxscroll400">
        	<h3><?php echo $item->title; ?></h3>
        	<pre><code><?php echo $item->introtext.' '.$item->fulltext; ?></code></pre>
        	<input type="hidden" name="task" value="" />
        	<input type="hidden" name="boxchecked" value="0" />
        	<?php echo HTMLHelper::_('form.token'); ?>
    	</div>
	</div>
</form>
<div class="clearfix"></div>
<p><?php echo XbrefmanGeneral::credit();?></p>


