<?php
/*******
 * @package xbRefMan Component
 * @version 1.0.0 9th May 2022
 * @filesource admin/views/dashboard/tmpl/default.php
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2021
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('script', 'media/com_xbrefman/js/xbrefman.js', array('version' => 'auto', 'relative' => false));

$alink = 'index.php?option=com_xbrefman&view=article&id=';
?>
<form action="<?php echo Route::_('index.php?option=com_xbrefman&view=dashboard'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row-fluid">
		<div id="j-sidebar-container">
			<?php echo $this->sidebar; ?>
			<hr />
            <div class="xbinfopane">
				<div class="row-fluid">
			        <?php echo HTMLHelper::_('bootstrap.startAccordion', 'slide-cpanel', array('active' => 'sysinfo')); ?>
		        	<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-cpanel', Text::_('XBREFMAN_SYSINFO'), 'sysinfo','xbaccordion'); ?>
	        			<p><b><?php echo Text::_( 'XBREFMAN_COMPONENT' ); ?></b>
							<br /><?php echo Text::_('XBREFMAN_VERSION').': '.$this->xmldata['version'].'<br/>'.
								$this->xmldata['creationDate'];?>
						</p>
						<p><b><?php echo Text::_( 'XBREFMAN_CONTENT_PLUGIN' ); ?></b>:<br /> 
							<?php echo $this->xbrefsconstatus; ?>
						</p>
						<p><b><?php echo Text::_( 'XBREFMAN_BUTTON_PLUGIN' ); ?></b>:<br />
							<?php echo $this->xbrefsbtnstatus; ?>
						</p>
						<p><b><?php echo Text::_( 'XBREFMAN_WEBLINKS_COM' ); ?></b>:<br /> 
							<?php echo $this->weblinkscomstatus; ?>
						</p>
						<p><b><?php echo Text::_( 'XBREFMAN_YOUR_CLIENT' ); ?></b>
							<br/><?php echo $this->client['platform'].'<br/>'.$this->client['browser']; ?>
						</p>
	        		<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
		        	<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-cpanel', Text::_('XBREFMAN_REGINFO'), 'reginfo','xbaccordion'); ?>
		        		 <?php $credit = XbrefmanGeneral::credit();
		        		    if ($credit=='') : ?>
		        			<p><?php echo Text::_('XBREFMAN_THANKS_REG'); ?></p>
		        		<?php else : ?>
		        			<p><b><?php echo Text::_('XBREFMAN'); ?></b> <?php echo Text::_('XBREFMAN_REG_ASK'); ?></p>
		        			 <?php echo $credit;?>
		        		<?php endif; ?>
	        		<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
	        		<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-cpanel', JText::_('XBREFMAN_ABOUT'), 'about','xbaccordion'); ?>
	        			<p><?php echo JText::_( 'XBREFMAN_ABOUT_INFO' ); ?></p>
	        			<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
	        			<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-cpanel', JText::_('XBREFMAN_LICENSE'), 'license','xbaccordion'); ?>
	        			<p><?php echo JText::_( 'XBREFMAN_LICENSE_INFO' ); ?></p>
	        			<hr />
	        			<p>
	        				<?php echo Text::_( 'XBREFMAN' ).' '.$this->xmldata['copyright']; ?>
	        			</p>
					<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
					<?php echo HTMLHelper::_('bootstrap.endAccordion'); ?>
				</div>
        	</div>		
		</div>
		<div id="j-main-container" >
			<h3><?php echo Text::_('XBREFMAN_STATUS_SUM'); ?></h3>
			<div class="row-fluid">
            	<div class="span6">
   					<div class="xbbox xbboxcyan">
						<h3 class="xbtitle">
							<span class="badge badge-success pull-right"><?php echo  $this->refcnts['refs']; ?></span> 
							<a href="index.php?option=com_xbrefman&view=articles"><?php echo Text::_('XBREFMAN_REFSUSED'); ?></a>
						</h3>
						<div class="row-striped">
     						<h4>
         						<span class="badge badge-info"><?php echo $this->refcnts['tags']; ?></span>&nbsp;
         						<?php echo Text::_('XBREFMAN_TAG_REFS'); ?>
   								<?php echo ' '.Text::_('XBREFMAN_IN').' '.$this->refcnts['tagarts'].' '.lcfirst(Text::_('XBREFMAN_ARTICLES')); ?>
    						</h4>
    						<?php if ($this->refcnts['badtags']>0) : ?>
    							<div class="row-fluid">
     								<div class="span1"></div>
    								<div class="span10">
    									<?php 
    									   echo $this->refcnts['badtags'].' '.Text::_('XBREFMAN_INVALID_TAGS_IN').' '; ?>
										<a href="#" class="xbpop xbrefpop xbfocus"  data-trigger="focus" title="Articles with bad tagrefs" 
											data-content="<?php echo '<ul>'; ?>
											<?php foreach ($this->refcnts['badtagarts'] as $id=>$art) {
											    echo '<li><a href=\''.$alink.$id.'\'>'.$art.'</a></li>';
											}?>
											<?php echo '</ul>'; ?>" > 
   									   <?php echo count($this->refcnts['badtagarts']).' '.lcfirst(Text::_('XBREFMAN_ARTICLES'));    																		    
    								    ?>
										</a>    									   
    								</div>
    							</div>
    						<?php endif; ?>
						</div>
						<div class="row-striped">
     						<h4>
         						<span class="badge badge-cyan"><?php echo $this->refcnts['links']; ?></span>&nbsp;
         						<?php echo Text::_('XBREFMAN_WEBLINK_REFS'); ?>
   								<?php echo ' '.Text::_('XBREFMAN_IN').' '.$this->refcnts['linkarts'].' '.lcfirst(Text::_('XBREFMAN_ARTICLES')); ?>
    						</h4>
   							<?php if ($this->refcnts['badlinks']>0) : ?>
     							<div class="row-fluid">
     								<div class="span1"></div>
    								<div class="span10">
        									<?php 
        									   echo $this->refcnts['badlinks'].' '.Text::_('XBREFMAN_INVALID_WEBLINKS_IN').' '; ?>
    										<a href="#" class="xbpop xbrefpop xbfocus"  data-trigger="focus" title="Articles with bad weblinks" 
    											data-content="<?php echo '<ul>'; ?>
    											<?php foreach ($this->refcnts['badlinkarts'] as $id=>$art) {
    											    echo '<li><a href=\''.$alink.$id.'\'>'.$art.'</a></li>';
    											}?>
    											<?php echo '</ul>'; ?>"
    											>
       									   <?php echo count($this->refcnts['badlinkarts']).' '.lcfirst(Text::_('XBREFMAN_ARTICLES'));    																		    
        								    ?>
    										</a>    									   
    								</div>
    							</div>
    						<?php endif; ?>
						</div>
						
						<div class="row-striped">
     						<h4>
         						<span class="badge badge-mag"><?php echo $this->refcnts['text']; ?></span>&nbsp;
         						<?php echo Text::_('XBREFMAN_TEXT_REFS'); ?>
   								<?php echo ' '.Text::_('XBREFMAN_IN').' '.$this->refcnts['textarts'].' '.lcfirst(Text::_('XBREFMAN_ARTICLES')); ?>
    						</h4>
   							<?php if ($this->refcnts['dupetext']>0) : ?>
     							<div class="row-fluid">
     								<div class="span1"></div>
    								<div class="span10">
    									<?php echo $this->refcnts['dupetext'].' '.Text::_('XBREFMAN_HAVE_DUPE_TITLES'); ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
        			</div>
        		</div>
            	<div class="span6">
   					<div class="xbbox xbboxgrn">
   						<h3 class="xbtitle">
							<span class="badge badge-success pull-right"><?php echo $this->artcnts['articles']; ?></span> 
							<a href="index.php?option=com_xbrefman&view=articles"><?php echo Text::_('XBREFMAN_ARTICLEREFS'); ?></a>
						</h3>
						<div class="row-striped">
    						<h4>
    							<span class="badge badge-success"><?php echo ($this->artcnts['tags']+$this->artcnts['links']+$this->artcnts['text']); ?></span>&nbsp;
    							<?php echo Text::_('XBREFMAN_TOTALREFS'); ?>
    						</h4>
						</div>
						<div class="row-striped">
    						<h4><?php echo Text::_('XBREFMAN_REFSOURCES'); ?></h4>
							<div class="row-fluid">
								<div class="span4">
									<span class="badge badge-info xbmr10"><?php echo $this->artcnts['tags']; ?></span>
									<?php echo Text::_('XBREFMAN_TAGS'); ?>
								</div>
								<div class="span4">
									<span class="badge badge-cyan xbmr10"><?php echo $this->artcnts['links']; ?></span>
									<?php echo Text::_('XBREFMAN_WEBLINKS'); ?>
								</div>
								<div class="span4">
									<span class="badge badge-mag xbmr10"><?php echo $this->artcnts['text']; ?></span>
									<?php echo Text::_('XBREFMAN_TEXT'); ?>
								</div>
							</div>
						</div>
						<div class="row-striped">
    						<h4><?php echo Text::_('XBREFMAN_DISPTYPES'); ?></h4>
							<div class="row-fluid">
								<div class="span6">
									<span class="badge badge-warning xbmr10"><?php echo $this->artcnts['pop']; ?></span>
									<?php echo Text::_('XBREFMAN_POPOVERS'); ?>
								</div>
								<div class="span6">
									<span class="badge badge-success xbmr10"><?php echo $this->artcnts['foot']; ?></span>
									<?php echo Text::_('XBREFMAN_FOOTNOTES'); ?>
								</div>
							</div>
						</div>
						<div class="row-striped">
							<div class="row-fluid">
								<div class="span6">
									<span class="badge badge-yellow xbmr10"><?php echo $this->artcnts['both']; ?></span>
									<?php echo Text::_('XBREFMAN_BOTH'); ?>
								</div>
								<div class="span6">
									<span class="badge badge-white xbmr10"><?php echo $this->artcnts['def']; ?></span>
									<?php echo Text::_('XBREFMAN_DEFAULT').' ('.$this->defdisp.')'; ?>
								</div>
							</div>
						</div>
        			</div>
        		</div>
    		</div>
    		<div class="row-fluid">
       			<div class="span4">
     				<div class="xbbox xbboxyell">
       					<h3><?php echo Text::_('XBREFMAN_REFMAN_OPTIONS');?></h3>
        				<dl class="xbdl">
        					<dt><?php echo Text::_('XBREFMAN_SHOW_CATS_LABEL');?></dt>
        					<dd><?php echo ($this->params->get('show_cats',0)) ? 'Show' : 'No'; ?></dd>
        					<dt><?php echo Text::_('XBREFMAN_SHOW_TAGS_LABEL');?></dt>
        					<dd><?php echo ($this->params->get('show_tags',0)) ? 'Show' : 'No'; ?></dd>
        					<dt><?php echo Text::_('XBREFMAN_SHOW_SEARCH_LBL');?></dt>
        					<dd><?php echo ($this->params->get('search_bar',0)) ? 'Show' : 'No'; ?></dd>
        					<dt><?php echo Text::_('XBREFMAN_AUTOCLOSE_LABEL');?></dt>
        					<dd><?php echo ($this->params->get('auto_close_dets',0)) ? 'Yes' : 'No'; ?></dd>
        					<dt><?php echo Text::_('XBREFMAN_EXT_ARROW_ICON');?></dt>
        					<dd><?php echo ($this->params->get('blank_icon',0)) ? 'Show' : 'No'; ?></dd>
        				</dl>
        			</div>
        		</div>
     			<div class="span4">
    				<div class="xbbox xbboxblue">
        				<h3><?php echo Text::_('XBREFMAN_BUTTON_SETTINGS'); ?></h3>
    					<?php if ($this->xbrefsbtn > 0) : ?>
            				<p><b><?php echo Text::_('XBREFMAN_SEL_TAG_REFS'); ?></b></p>
            				<p><?php echo $this->tagtags; ?></p>
            				
            				<p><b><?php echo Text::_('XBREFMAN_SEL_WEBLINK_REFS'); ?></b></p>
            				<p><?php echo $this->linktags; ?></p>
             				<p><b><?php echo Text::_('XBREFMAN_SEL_WEBLINK_CATS'); ?></b></p>
            				<?php echo $this->linkcats; ?>
            			<?php else: ?>
            				<p><i><?php echo Text::_('XBREFMAN_BUTTON_OPTS_UNAVAIL');?></i></p>
            			<?php endif; ?>
        			</div>
    			</div>
    			<div class="span4">
    				<div class="xbbox xbboxmag">
        				<h3>xbRefs-Content Settings</h3>
    					<?php if ($this->xbrefscon > 0) : ?>
            				<dl class="xbdl">
            					<dt><?php echo Text::_('XBREFMAN_DEF_DISP');?></dt>
            					<dd><?php echo $this->defdisp; ?></dd>
            					<dt><?php echo Text::_('XBREFMAN_DEF_TRIG');?></dt>
            					<dd><?php echo $this->deftrig;?></dd>
            					<dt><?php echo Text::_('XBREFMAN_REFNUM_FORMAT');?></dt>
            					<dd><?php echo $this->refnumfmt;?></dd>
            					<dt><?php echo Text::_('XBREFMAN_WEBLINK_DISP');?></dt>
            					<dd><?php echo $this->linkfmt;?></dd>
            					<dt><?php echo Text::_('XBREFMAN_WEBLINK_TARG');?></dt>
            					<dd><?php echo $this->linktarg;?></dd>
            				</dl>
            			<?php else: ?>
            				<p><i><?php echo Text::_('XBREFMAN_CONTENT_OPTS_UNAVAIL');?></i></p>
            			<?php endif; ?>
        			</div>
    			</div>

    		</div>
    	</div>
	</div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<p><?php echo XbrefmanGeneral::credit();?></p>
