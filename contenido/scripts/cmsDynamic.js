
function loadCmsDynamicCallback() {
    var movedElementIndex;
  /**
         * Sort the form element per drag and drop.
         * After this action must be saved the form to persistence of data.
         */
        $('div.cms_dynamic_content').sortable({
            items: "div.cms_dynamic_element",
            handle: ".cms_dynamic_content_action_move",
            start: function(event, ui) {
                movedElementIndex = ui.item.index();
            },
            stop: function(event, ui) {
                var index = ui.item.index();
                var itemClassList = ui.item.find('span.cms_dynamic_element_index_form').html();
                var parent = ui.item.parents().filter('div.cms_dynamic_content_wrap:first');	
                var toolbarId = parent.attr('id').replace('cms_dynamic_content_', 'cms_dynamic_toolbar_');	
                var form = $('div#' + toolbarId + ' form');
                var tmpItem = form.find('div.' + itemClassList).detach();
                if (index == 0) {
                    form.prepend(tmpItem);
                } else if (index > 0){
                    form.find('div.cms_dynamic_form_element').eq(index - 1).after(tmpItem);
                }
                
                if (ui.item.index() != movedElementIndex) {
                    activateLink($('#' + toolbarId), 'save');
                } ;
            }
        });
}

function loadCmsDynamic() {
	
    if ($('#cms_dynamic').length == 0) {
        $('head').append('<link rel="stylesheet" id="cms_dynamic" href="' + cmsDynamicConPath + 'styles/cms_dynamic.css" type="text/css" media="all" />');
    }

	var cmsDynActiveType = '';

	$(document).ready(function() {
        conLoadFile(sPath+'scripts/jquery/jquery-ui.js', 'loadCmsDynamicCallback();');
		
		/**
		 * Delete an element from dynamic type.
		 * After this action must be saved the form to persistence of data.
		 */
		$('.cms_dynamic_content_action_delete').click(function() {
			
			var container = $(this).parents().filter('div.cms_dynamic_element:first');
			var index = container.find('span.cms_dynamic_element_index_form').html();
			
			var parent = container.parents().filter('div.cms_dynamic_content_wrap:first');	
			
			if (typeof parent.attr('id') != 'undefined') {
				var toolbarId = parent.attr('id').replace('cms_dynamic_content_', 'cms_dynamic_toolbar_');	
				var form = $('div#' + toolbarId + ' form');
				
				form.find('div.' + index).remove();
				container.remove();
				activateLink($('#' + toolbarId), 'save');
			}
			
			
		});
		
		/**
		 * Select a cms type to add 
		 */
		$('.cms_dynamic_toolbar_header li a').click(function() {
			$('.cms_dynamic_toolbar_header li a').removeClass('cms_dynamic_active_link');
			$(this).addClass('cms_dynamic_active_link');
			cmsDynActiveType = $(this).attr('name');
			var container = $(this).parents().filter('.cms_dynamic_toolbar:first');
			container.find('div.cms_dynamic_config_element').css('display', 'none');
			container.find('div.cms_dynamic_toolbar_' + cmsDynActiveType + ':first').css('display', 'block');
			activateLink(container, 'add');
		});
		
		/**
		 * Add a selected cms type per post into dynamic type.
		 */
		$('.cms_dynamic_toolbar a.cms_dynamic_add').click(function() {
			if (cmsDynActiveType != '' && !$(this).hasClass('cms_dynamic_link_disabled')) {
				var container = $(this).parents().filter('div.cms_dynamic_toolbar:first');
				container.find('.cms_dynamic_form input[name=cms_dynamic_action]').val('add');
				container.find('.cms_dynamic_form input[name=cms_dynamic_add]').val(cmsDynActiveType);
				container.find('.cms_dynamic_form input[name=submit]').click();
			} else {
				alert(cmsDynamicTranslations.text_no_add);
				return false;
			}
			
		});
		
		/**
		 * Activate the save link on changes of template
		 */
		$('div.cms_dynamic_config_element select').change(function() {
			var container = $(this).parents().filter('div.cms_dynamic_toolbar:first');
			activateLink(container, 'save');
		});
		
		/**
		 * Save the changes
		 */
		$('.cms_dynamic_header_action .cms_dynamic_save').click(function() {
			if (!$(this).hasClass('cms_dynamic_link_disabled')) {
				var container = $(this).parents().filter('div.cms_dynamic_toolbar:first');
				container.find('.cms_dynamic_form input[name=submit]').click();
			}
		});
		
		/**
		 * Change background on mouseover
		 */
		$('.cms_dynamic_edit .cms_dynamic_element').mouseover(function() {
			$(this).css('background-color', '#F1F1F1');
		});
		
		/**
		 * Change back background on mouseout
		 */
		$('.cms_dynamic_edit .cms_dynamic_element').mouseout(function() {
			$(this).css('background-color', 'transparent');
		});
		
		/**
		 * Remove the class of disable from it for enable this action
		 * @param string type
		 */
		function activateLink(toolbar, type) {
			if (type == 'add' || type == 'all') {
				toolbar.find('.cms_dynamic_toolbar_header a.cms_dynamic_add').removeClass('cms_dynamic_link_disabled');
			}
			
			if (type == 'save' || type == 'all') {
				var saveLink = toolbar.find('.cms_dynamic_toolbar_header a.cms_dynamic_save');
				saveLink.removeClass('cms_dynamic_link_disabled');
				var src= saveLink.find('img').attr('src');
				saveLink.find('img').attr('src', src.replace('but_ok_off.gif', 'but_ok.gif'));
				
			}
			
		}
	});
}
