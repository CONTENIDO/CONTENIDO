/**
 * Subnavigation. Handles tab highlighting.
 * Replaces most of the rowMark.js functionality.
 *
 * @author dirk.eschler
 */
var sub = {

    /**
     * Highlights the first tab by default (div#navcontainer li#c_0 a)
     * by setting the class to 'current'. Is called in subnavigation template.
     */
	'init': function() {
	//console.log("init");
	      if(document.getElementById('c_0') != null && document.getElementById('c_0').getElementsByTagName('a'))
	      {
          document.getElementById('c_0').getElementsByTagName('a')[0].className = 'current';
        }
	},
    
	/**
	 * Highlights the active tab.
     *
	 * @param {Object} cElm Clicked a-element, resp. the tab to highlight.
     * @todo Consider new name ("highlight"?) and rename remaining instances.
	 */
	'clicked': function(cElm) {
	//console.log("clicked");
		// first unhighlight all tabs
      var ul = document.getElementById('navlist');
    	var as = ul.getElementsByTagName('a');
    	for (var i=0; i<=as.length; i++) {
            if (as[i]) {
                as[i].className = '';
            }
    	}
		// then highlight the active tab
        cElm.className = 'current';
	},
    
    /**
     * Highlights a tab by its element id. Useful for highlighting from an outer frame.
     *
	 * @param {String} id Element id of tab to highlight
     * @param {Object} frame Reference to frame hodling the subnavigation:
     *                       top.content.right.right_top (when there is a left/right frameset)
     *                       top.content.right_top       (when there is no left/right frameset)
     */
	'highlightById': function(id, frame) {
        var ul = frame.document.getElementById('navlist');
    	var as = ul.getElementsByTagName('a');
    	for (var i=0; i<=as.length; i++) {
            if (as[i]) {
                as[i].className = '';
            }
    	}
        frame.document.getElementById(id).getElementsByTagName('a')[0].className = 'current';
    },
    
	/**
	 * Unhighlights all tabs.
	 */
    /*
	'unhighlight': function() {
        var ul = this.frame.document.getElementById('navlist');
    	var as = ul.getElementsByTagName('a');
    	for (var i=0; i<=as.length; i++) {
            if (as[i]) {
                as[i].className = '';
            }
    	}
	},*/
    
    /**
     * Dummy method to avoid breakage.
     *
     * @todo Locate remaining inline calls to sub.click() and remove them
     */
    'click': function() {
    		//console.log("remove me");
        return;
    }
};
