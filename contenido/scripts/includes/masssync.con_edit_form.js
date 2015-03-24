
$(document).ready(function() {
	// CON-2075
    // pressing enter will implicitly submit the form by using first submit button
    // make sure we fire the article save button and not any of the sync buttons
	// https://html.spec.whatwg.org/multipage/forms.html#implicit-submission
    jQuery("form[name='art_props'] input[type='text']").keypress(function(e) {
        // enter or return buttons can submit form
        if (13 === e.which || 10 === e.which) {
        	e.preventDefault();
            jQuery("#saveart").click();

            return false;
        }
    });

    $(".massSyncCheckbox").click(function() {
        var $syncAll = $(".syncAllButton");
        var $onlineAll = $(".onlineAllButton");

        var online = true;
        var sync = true;
        var test = $(".massSyncCheckbox:checked");
        test.each(function() {
            if($(this).data("sync") == "disabled") {
                sync = false;
            }
            if($(this).data("online") == "disabled") {
                online = false;
            }
        });

        if(!online) {
            $onlineAll.prop("disabled", true);
            $onlineAll.filter("[name='onlineAll']").css("background", "url(images/online_off.gif)");
            $onlineAll.filter("[name='offlineAll']").css("background", "url(images/offline_off.gif)");
        } else {
            $onlineAll.prop("disabled", false);
            $onlineAll.filter("[name='onlineAll']").css("background", "url(images/online.gif)");
            $onlineAll.filter("[name='offlineAll']").css("background", "url(images/offline.gif)");
        }
        if(!sync) {
            $syncAll.prop("disabled", true);
            $syncAll.css("background", "url(images/but_sync_art_off.gif)");
        } else {
            $syncAll.prop("disabled", false);
            $syncAll.css("background", "url(images/but_sync_art.gif)");
        }
    });

});

