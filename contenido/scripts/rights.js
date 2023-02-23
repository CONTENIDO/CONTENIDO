/**
 * CONTENIDO JavaScript rights module
 *
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */

function submitrightsform(act, actarea) {
    actarea = actarea || 0;

    if (actarea != 0 && actarea != '') {
        document.forms.rightsform.actionarea.value = actarea;
    }

    document.forms.rightsform.action.value = act;
    document.forms.rightsform.submit();
}

function setRightsForAllAreas() {
    // Fixed function to be use in ns/moz JL
    checked = document.forms["rightsform"].elements["checkall"].checked;
    var elements = document.getElementsByTagName("INPUT");

    for (i=0; i<elements.length; i++) {
        if (elements[i].type == "checkbox") {
            elements[i].checked = checked;
        }
    }
}

function setRightsForArea(key) {
    checked = document.forms["rightsform"].elements["checkall_"+key].checked;

    flag = true;

    var foo = document.forms["rightsform"].elements["checkall_"+key];

    var str = "";

    for (var p in areatree[key]) {
        str += p + "\n";
    }

    //alert(str);

    for (var key2 in areatree[key]) {
        try {
            if (flag) {
                row = foo.offsetParent.parentNode;
                cboxes = row.getElementsByTagName('INPUT');
                cboxes[0].checked = !cboxes[0].checked;
                flag = false;
            }
            // alert(areatree[key][key2]);

            document.getElementById(areatree[key][key2]).checked = checked;
        } catch(e) {
        }
    }
}

function setRightsFor(area, act, itemid) {
    // for one row
    if (itemid != "") {
        // read the checkbox
        checked = document.forms["rightsform"].elements["checkall_"+area+"_"+act+"_"+itemid].checked;
        // search the array   with area and action ids and set the checktype
        for (var act_areas in actareaids) {
            // act_areas    consits     action|area
            var act_area = act_areas.split("|");
            document.forms["rightsform"].elements["rights_list["+act_area[1]+"|"+act_area[0]+"|"+itemid+"]"].checked = checked;
        }
        // for the whole colum
    } else {
        // read the checkbox
        checked = document.forms["rightsform"].elements["checkall_"+area+"_"+act].checked;
        // search the array with the itemids and set the checktype
        for (var itemi in itemids) {
            document.forms["rightsform"].elements["rights_list["+area+"|"+act+"|"+itemi+"]"].checked = checked;
        }
    }
}

function setRightsForAll() {
    checked = document.forms["rightsform"].elements["checkall"].checked;
    for (var itemid in itemids) {
        for (var act_areas in actareaids) {
            // act_areas consists of "action|area"
            var act_area = act_areas.split("|");
            document.forms["rightsform"].elements["rights_list["+act_area[1]+"|"+act_area[0]+"|"+itemid+"]"].checked=checked;
        }
    }
}

function rightsInheritanceUp(allparentid, allcount) {
    var counter = 0;
    var parentid = allparentid;
    var flag = 1;

    while (flag != 0) {
        // search the array   with area and action ids
        for (var act_areas in actareaids) {
            // act_areas consists of "action|area"
            var act_area = act_areas.split("|");

            counter = 0;
            // if there is any element which has the same parent
            while (document.getElementById("str_"+parentid+"_"+counter+"_"+act_area[1]+"_"+act_area[0])) {
                // check it with the checktype   of allparentid and allcount
                document.getElementById("str_"+parentid+"_"+counter+"_"+act_area[1]+"_"+act_area[0]).checked=document.getElementById("str_"+allparentid+"_"+allcount+"_"+act_area[1]+"_"+act_area[0]).checked;
                // increase the counter for the next field that consits the same parent
                counter++;
            }
        }

        // if there are no more parents break
        if (parentid == 0) {
            break;
        }
        // search for the new parentid
        var wholeid = document.forms["rightsform"].elements["rights_list["+act_area[1]+"|"+act_area[0]+"|"+parentid+"]"].id;
        // wholeid consists of "str_parentid_counter_area_action"
        var ids = wholeid.split("_");
        // set the new parentid
        parentid = ids[1];
    }
}

function rightsInheritanceDown(parentid) {
    // search the array with area and action ids
    for (var act_areas in actareaids) {
        // act_areas consists of "action|area"
        var act_area = act_areas.split("|");
        // set for each action and area the checks
        helpinheritance(act_area, parentid);
    }
}

function helpinheritance(act_area, parentid) {
    var counter = 0;
    // if there is any element with ist the child of my id
    while (document.getElementById("str_"+parentid+"_"+counter+"_"+act_area[1]+"_"+act_area[0])) {
        // if there is one set the check
        document.getElementById("str_"+parentid+"_"+counter+"_"+act_area[1]+"_"+act_area[0]).checked=document.forms["rightsform"].elements["rights_list["+act_area[1]+"|"+act_area[0]+"|"+parentid+"]"].checked;

        // find out the name of this field
        var wholeid = document.getElementById("str_"+parentid+"_"+counter+"_"+act_area[1]+"_"+act_area[0]).name;
        // match the datas [area|action|itemid]
        var ids = wholeid.match(/\[+\S+\]/);
        // make string
        var tmp_ids = "" + ids;
        // cut the []
        var ids_new = tmp_ids.substr(1,tmp_ids.length-2);
        // split the string with |   the itemid is the third one
        var ids_new2 = ids_new.split("|");

        // start the new function, with the itemid as parentid
        helpinheritance(act_area,ids_new2[2]);

        // increase the counter for the next field that consits the same parent
        counter++;
    }
}

function setframeset(left, right) {
    parent.document.getElementById("FramesetContenido").cols = left+","+right;
}
