<?php
/*******
 * @package xbRefMan Component
 * @version 0.9.2.1 23rd April 2022
 * @filesource site/views/tag/tmpl/default.php
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
use Joomla\CMS\Uri\Uri;

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

$alink = 'index.php?option=com_content&view=article&id=';
$type = $this->item->type;
$labeltype = array('tag'=>'label-info','weblink'=>'label-cyan', 'text'=>'label-mag')
?>

<div class="xbrefman">
	
	<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=reference'); ?>" method="post" name="adminForm" id="adminForm">       
		
		<div class="row-fluid">
			<div class="span12">
				<h3><?php echo Text::_('XBREFMAN_REFERENCE_TITLE'); ?>:<span class="biglabel <?php echo $labeltype[$type]; ?> xbml10">
					<?php echo $this->item->title; ?></span></h3>
				<hr />
				<p><?php echo Text::_('XBREFMAN_DISPLAY'); ?>
				<div class="xbreffooter ">
    				<div class="pull-left xbrefdisplay xb12"><b><?php echo $this->item->title;?></b> :&nbsp;</div>
    				<div class="pull-left xbrefdisplay xb12"><?php echo $this->item->description; ?>
    					<?php if ($type == 'weblink') : ?>
        					<br /><a href="<?php echo $this->item->url; ?>"
        						<?php if (!Uri::isInternal($this->item->url)) { echo ' target="_blank"';} ?>
        					><?php echo $this->item->url; ?>
         					</a>
     					<?php endif; ?>
    				</div>
    				<div class="clearfix"></div>
				</div>
			</div>
		</div>		
    	<hr />
		<p><?php echo Text::sprintf('XBREFMAN_ART_USE_REF',count($this->item->articles), $this->item->title); ?></p>
		<div class="row-fluid">
		
        	<div class="span12">
				<?php if (empty($this->item->articles)) : ?>
					<div class="alert alert-no-items">
						<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
				
        		<table class="table table-striped table-hover" style="table-layout:fixed;" id="xbRefsList">	
        		<thead>
        			<tr>
    					<th>
    						<?php echo Text::_('XBREFMAN_ARTICLE_TITLE'); ?> 
    					</th>
    					<th width="50%">
    						<?php echo Text::_('XBREFMAN_REF_CONTEXT')?>	
        				</th>
        				<?php if($this->show_cats || $this->show_tags) : ?>
        					<th class="hidden-phone">
    							<?php if ($this->show_cats) {
    							    echo HTMLHelper::_('searchtools.sort','XBREFMAN_CATEGORY','category_title',$listDirn,$listOrder );
    					       }
    					       if (($this->show_cats) && ($this->show_tags)) {
    					           echo ' &amp; ';
    					       }
    					       if($this->show_tags) {
    					           echo Text::_( 'XBREFMAN_TAGS' ); 
    					       } ?>                
        					</th>
                		<?php endif; ?>
					</tr>
        			
        		</thead>
        		<tbody>
    			<?php foreach ($this->item->articles as $i => $article) : ?>
    			<?php $alink = XbrefmanHelperRoute::getArticleRoute($article->id); 
    			 $clink = XbrefmanHelperRoute::getArticleCategoryRoute($article->catid);
    			?>
    				<tr>
    					<td>
    						<a href="<?php echo $alink; ?>"
    								title="<?php echo Text::_('XBREFMAN_LINK_ARTICLE'); ?>" >
    								<b><?php echo $this->escape($article->title); ?></b></a>    								
    					</td>
    					<td>
    						<?php foreach ($article->refs as $i=>$ref) : ?>
    						    <div class="row-fluid">
        						    <div class="span2">
        						    	<a href="<?php echo $alink.'#refid'.$ref['idx']; ?>">
        						    		<i> <?php echo Text::_('XBREFMAN_NEAR_WORD').' '.$ref['pos']; ?></i>
        						    	</a>
        						    </div>
        						    <div class="span10">
        						    	<?php echo $ref['context'].' <b>';
            						    if ($ref['type'] != 'foot') {
            						        echo XbrefmanGeneral::makePopover($ref).$ref['text'].'</span>';
            						    } else {
                						    echo $ref['text'];
        	       					    }
        	       					    if ($ref['num']) {
        	       					        echo ' <a href="'.$alink.'#ref'.$ref['num'].'"><sup>['.$ref['num'].']</sup></a>'; 
        	       					    }
            						    echo '</b>'; ?>
        						    </div>
        						</div>
    						<?php endforeach; ?>
    					</td>
    				<?php if($this->show_cats || $this->show_tags) : ?>
    					<td class="hidden-phone">
     						<?php if($this->show_cats) : ?>	
     							<p>
      							<?php if($this->show_cats>0) : ?>											
    								<a class="label label-success" href="<?php echo $clink; ?>"><?php echo $article->category_title; ?></a>
    							<?php else: ?>
    								<span class="label label-success"><?php echo $article->category_title; ?></span>
    							<?php endif; ?>
    							</p>
    						<?php endif; ?>
    						<?php if($this->show_tags) {
    							$tagLayout = new FileLayout('joomla.content.tags');
    							echo $tagLayout->render($article->tags);
    						}
        					?>
     					</td>
					<?php endif; ?>
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
