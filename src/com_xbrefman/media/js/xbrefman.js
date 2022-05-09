/**
 * @package xbRefMan Component
 * @version 0.3.0 20th February 2022
 * @filesource media/js/xbrefman.js 
 * @desc 
 * @author Roger C-O
 * @copyright (C) Roger Creagh-Osborne, 2022
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * 
**/
jQuery(function($) {
	"use strict";
	initTooltip();
  
	function initTooltip(event, container)
	{
		$(container || document).find('.xbpop').popover();
	}

});
