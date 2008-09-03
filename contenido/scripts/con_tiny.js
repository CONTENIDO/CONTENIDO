function updateImageFilebrowser ()
{
    if (!fb_handle.left)
    {
        return;
    }
    
    if (!fb_handle.left.left_top)
    {
        return;
    }
    
    if (!fb_handle.left.left_top.document.getElementById("selectedfile"))
    {
        return;
    }	
    
    if (fb_handle.left.left_top.document.getElementById("selectedfile").value != "")
    {
        fb_win.document.forms[0].elements[fb_fieldname].value = fb_handle.left.left_top.document.getElementById("selectedfile").value;
        
        fb_handle.close();
        window.clearInterval(fb_intervalhandle);

        if (fb_win.showPreviewImage)
        {
            fb_win.showPreviewImage(fb_win.document.forms[0].elements[fb_fieldname].value);
        }				
    }
}

function CustomfileBrowserCallBack(field_name, url, type) {
        // This is where you insert your custom filebrowser logic
        alert("Filebrowser callback: " + field_name + "," + url + "," + type);
}

function CustomURLConverter(url, node, on_save) {
        var oEd = new tinymce.Editor('contenido', '');
        url = oEd.convertURL(url, node, on_save);
        return url;
}

function CustomCleanupContent(type, value) {
        switch (type) {
                case "get_from_editor":
                case "insert_to_editor":
                        // Remove xhtml styled tags
                        value = value.replace(/[\s]*\/>/g,'>');
                        break;
        }

        return value;
}

function storeCurrentTinyContent() {
    //store last tiny changes if tiny is still open
    if (tinyMCE.getInstanceById(active_object)) {
        aEditdata[active_id] = tinyMCE.get(active_object).getContent();
    }
}

function setcontent(idartlang, act) {
    storeCurrentTinyContent();
    
    var str = '';
    for (var sId in aEditdata) {
        if (aEditdataOrig[sId] != aEditdata[sId]) {
            var data = sId.split("_");

            // data[0] is the fieldname * needed
            // data[1] is the idtype
            // data[2] is the typeid * needed
           
            // build the string which will be send
            str += buildDataEntry(idartlang , data[0] , data[2] , aEditdata[sId]);
        }
    }            

    // set the string
    document.forms.editcontent.data.value = str + document.forms.editcontent.data.value;

    // set the action string
    if ( act != 0 ) {
        document.forms.editcontent.action = act;
    }

    // submit the form
    document.forms.editcontent.submit();
}

function prepareString(aContent) {
    if ( aContent == "&nbsp;" || aContent == "" ) {
        aContent = "%$%EMPTY%$%";
    } else {
        // if there is an | in the text set a replacement chr because we use it later as isolator
        while( aContent.search(/\|/) != -1 ) {
            aContent = aContent.replace(/\|/,"%$%SEPERATOR%$%");
        }
    }

    return aContent;
}

function buildDataEntry(idartlang, type, typeid, value) {
    return idartlang +'|'+ type +'|'+ typeid +'|'+ value +'||';
}

function addDataEntry(idartlang, type, typeid, value) {
    document.forms.editcontent.data.value = (buildDataEntry(idartlang, type, typeid, prepareString(value) ) );

    setcontent(idartlang,'0');
}

function swapTiny(obj) {
    if (tinyMCE.getInstanceById(active_object)) {
        aEditdata[active_id] = tinyMCE.get(active_object).getContent();
        if (aEditdata[active_id] == '') {
            document.getElementById(active_id).style.height = '15px';
        }
        tinyMCE.execCommand('mceRemoveControl', false, active_object);
        active_id = null;
        active_object = null;
    }
    
    tinyMCE.settings = tinymceConfigs;
    active_id = obj.id;
    active_object = obj;
    tinyMCE.execCommand('mceAddControl', true, obj);
    
    document.getElementById(active_id).style.height = '';
}

function myCustomFileBrowser(field_name, url, type, win) {
    switch (type)
    {
        case "image":
            fb_handle = window.open(image_url, "filebrowser", "dialog=yes,resizable=yes");
            fb_fieldname = field_name;
            fb_win = win;
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);						
            break;	
        case "file":
            fb_handle = window.open(file_url, "filebrowser", "dialog=yes,resizable=yes");
            fb_fieldname = field_name;
            fb_win = win;
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);
            break;
        case "flash":
            fb_handle = window.open(flash_url, "filebrowser", "dialog=yes,resizable=yes"); 
            fb_fieldname = field_name; 
            fb_win = win; 
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);
            break;
        case "media":
            fb_handle = window.open(media_url, "filebrowser", "dialog=yes,resizable=yes"); 
            fb_fieldname = field_name; 
            fb_win = win; 
            fb_intervalhandle = window.setInterval("updateImageFilebrowser()", 250);
            break;
        default:
            alert(type);
            break;
    }
}