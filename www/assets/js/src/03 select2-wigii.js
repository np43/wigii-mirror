/**
 *  This file is part of Wigii.
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
 */

/**
 * Customized select2 minimumInputLength decorator which passes through defaultOptions and blocks or not ajax calls.
 * Created by Medair(CWE) on 19.02.2018
 */
$.fn.select2.amd.define('select2/data/wigiiMinimumInputLength',[	
], function () {
  function WigiiMinimumInputLength (decorated, $e, options) {
    this.minimumInputLength = options.get('minimumInputLength');
    this.selectId = options.get('selectId');
    decorated.call(this, $e, options);
  }

  WigiiMinimumInputLength.prototype.query = function (decorated, params, callback) {
    params.term = params.term || '';
    params.term = params.term.trim();
    var selectedValue = $('#'+this.selectId).val();
    
    // shows user invite to enter a search term
    if (params.term.length == 0 && !selectedValue) {
      this.trigger('results:message', {
        message: 'inputTooShort',
        args: {
          minimum: this.minimumInputLength,
          input: params.term,
          params: params
        }
      });
      return;
    }
    // if search term length is smaller than minimum required, blocks ajax and only shows default options
    else if (params.term.length < this.minimumInputLength) {
      params.blockAjax=true;
    }   
    // fetches default options present in DOM
    params.defaultOptions = [];
    $('#'+this.selectId+' option').each(function(i){
    	var opt = $(this);
    	params.defaultOptions.push({'id':opt.attr('value'),'text':opt.text()});
    });
    decorated.call(this, params, callback);
  };

  return WigiiMinimumInputLength;
});


/**
 * Customized select2 ajax adapter to handle default options and conditional ajax calls.
 * Based on the select2/ajax DataAdapter.
 * Created by Medair(CWE) on 19.02.2018 
 */
$.fn.select2.amd.define('select2/data/wigiiAjaxAdapter',[
  'select2/data/array',
  'select2/utils',
  'select2/data/wigiiMinimumInputLength',
], function (ArrayAdapter, Utils, MinimumInputLength) {
  function WigiiAjaxAdapter ($element, options) {
    this.ajaxOptions = this._applyDefaults(options.get('ajax'));
    
    if (this.ajaxOptions.processResults != null) {
      this.processResults = this.ajaxOptions.processResults;
    }

    WigiiAjaxAdapter.__super__.constructor.call(this, $element, options);
  }

  Utils.Extend(WigiiAjaxAdapter, ArrayAdapter);

  WigiiAjaxAdapter.prototype._applyDefaults = function (options) {
    var defaults = {
      data: function (params) {
        return $.extend({}, params, {
          q: params.term
        });
      },
      transport: function (params, success, failure) {
        var $request = $.ajax(params);

        $request.then(success);
        $request.fail(failure);

        return $request;
      }
    };

    return $.extend({}, defaults, options, true);
  };

  WigiiAjaxAdapter.prototype.processResults = function (results) {
    return results;
  };

  WigiiAjaxAdapter.prototype.query = function (params, callback) {
    var matches = [];
    var self = this;

    if(params.blockAjax) {
    	// filters defaultOptions with search term
    	if(params.defaultOptions) {
    		if(params.term != '') {
    			var ucTerm = params.term.toUpperCase();
	    		for(var i=0;i<params.defaultOptions.length;i++) {	    			
	    			var opt = params.defaultOptions[i];
	    			// keeps empty value
	    			if(opt.id == '') matches.push(opt);
	    			// else filters with search term
	    			else if(opt.id.toUpperCase().indexOf(ucTerm) > -1 || opt.text.toUpperCase().indexOf(ucTerm) > -1) matches.push(opt);
	    		}
    		}
    		else matches = params.defaultOptions;
    	}
    	callback({'results':matches});
    	return;
    }
    
    if (this._request != null) {
      // JSONP requests cannot always be aborted
      if ($.isFunction(this._request.abort)) {
        this._request.abort();
      }

      this._request = null;
    }

    var options = $.extend({
      type: 'GET'
    }, this.ajaxOptions);

    if (typeof options.url === 'function') {
      options.url = options.url.call(this.$element, params);
    }

    if (typeof options.data === 'function') {
      options.data = options.data.call(this.$element, params);
    }

    function request () {
    	var $request = options.transport(options, function (data) {
        var results = self.processResults(data, params);

        // Extends results with default options
        if(params.defaultOptions) {
        	var ucTerm = '';
        	if(params.term != '') ucTerm = params.term.toUpperCase();
        	var matching = results.results;
        	var matchingIndex = {};
        	// Rebuilds results array
        	results.results = [];
        	// First puts default options
    		for(var i=0;i<params.defaultOptions.length;i++) {
    			var opt = params.defaultOptions[i];
    			// keeps empty value
    			// and filters with search term
    			if(opt.id == '' || opt.id.toUpperCase().indexOf(ucTerm) > -1 || opt.text.toUpperCase().indexOf(ucTerm) > -1) {
    				results.results.push(opt);
    				matchingIndex[opt.id] = true;
    			}
    		}
    		// then, filters duplicates and builds result
    		for(var i=0;i<matching.length;i++) {
    			var opt = matching[i];
    			if(!matchingIndex[opt.id]) {
    				results.results.push(opt);
    				matchingIndex[opt.id] = true;
    			}
    		}
        }       
        
        if (self.options.get('debug') && window.console && console.error) {
          // Check to make sure that the response included a `results` key.
          if (!results || !results.results || !$.isArray(results.results)) {
            console.error(
              'Select2: The AJAX results did not return an array in the ' +
              '`results` key of the response.'
            );
          }
        }

        callback(results);
      }, function () {
        // Attempt to detect if a request was aborted
        // Only works if the transport exposes a status property
        if ($request.status && $request.status === '0') {
          return;
        }

        self.trigger('results:message', {
          message: 'errorLoading'
        });
      });

      self._request = $request;
    }

    if (this.ajaxOptions.delay && params.term != null) {
      if (this._queryTimeout) {
        window.clearTimeout(this._queryTimeout);
      }

      this._queryTimeout = window.setTimeout(request, this.ajaxOptions.delay);
    } else {
      request();
    }
  };
  // Decorates with mininum input length
  return Utils.Decorate(WigiiAjaxAdapter,MinimumInputLength);
});
