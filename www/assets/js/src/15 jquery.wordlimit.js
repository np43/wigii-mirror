/**
 *  This file is part of Wigii (R) software.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 *  
 *  
 *  This small plugin is inpired from http://stackoverflow.com/questions/10151238/jquery-plugin-character-count-for-textareas
 *  
 */
 
(function($) {

	$.fn.wordlimit = function(options){

		// default configuration properties
		var defaults = {    
			allowed: 10,
			wordcounttext : 'Word count'
		};

		var options = $.extend(defaults, options); 

		function calculate(obj, event){
			if($(obj).val()){
	            var count = $(obj).val().split(' ').length;
			} else {
				var count = 0;
			}
            var available = options.allowed - count;
            if(available < 0 && event){
            	//allow del, backspace, tab, home, end, arrows
            	if (event.which != 46 && event.which != 8 && event.which != 9
            		&& event.which != 37 && event.which != 38 && event.which != 39 && event.which != 40
            		&& event.which != 35 && event.which != 36) { 
            		event.preventDefault(); 
	            	//remove last words
	            	words = $(obj).val().split(' ');
	            	words = words.slice(0, options.allowed);
	            	$(obj).val(words.join(' ')+' ');
            	}
            }
            //if available < 0 just continue to display 0 word available...
            $(obj).parent().parent().find('.label span.counter').html('('+options.wordcounttext+' '+Math.min(count, options.allowed)+' / '+options.allowed+')');
        };

        this.each(function(e) {              
			$(this).parent().parent().find('.label').append('&nbsp;&nbsp;<span class="counter"></span>');
			calculate(this, e);
			$(this).keydown(function(e){calculate(this, e)});
			$(this).click(function(e){calculate(this, e)});
			$(this).blur(function(e){calculate(this, e)});
			$(this).change(function(e){calculate(this, e)});
		});

	};

})(jQuery);