<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Message box for errors and / or confirms
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend sripts
 * @version    1.0.3
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.7
 * 
 * {@internal 
 *   created  2003-05-08
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-07-02, Frederic Schneider, include security_class
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// CONTENIDO startup process
include_once ('../includes/startup.php');

header("Content-Type: text/javascript");

cRegistry::bootstrap(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
cRegistry::shutdown();
?>


function resizeDialogToContent()
{
  // resize window so there are no scrollbars visible
  var dw = window.innerWidth;
  while (isNaN(dw))
  {
    dw = dw.substr(0,dw.length-1);
  }
  difw = dw - this.document.body.clientWidth;
  window.dialogWidth = this.document.body.scrollWidth+difw+'px';

  var dh = window.innerHeight;
  while (isNaN(dh))
  {
    dh = dh.substr(0,dh.length-1);
  }
  difh = dh - this.document.body.clientHeight;
  window.dialogHeight = this.document.body.scrollHeight+difh+'px';
}


try {

/**
 * OK and CANCEL buttons
 */
button = new Array();
button['confirm']   = '<a href="javascript:msgConfirm()" title="<?php echo i18n("Confirm"); ?>"><img src="images/but_ok.gif" border="0"></a>';
button['cancel']    = '<a href="javascript:msgCancel()" title="<?php echo i18n("Cancel"); ?>"><img src="images/but_cancel.gif" border="0"></a>';
button['ok']        = '<a href="javascript:msgCancel()" title="<?php echo i18n("Close window"); ?>"><img src="images/but_ok.gif" border="0"></a>';
button['warn']      = '<img src="images/but_warn.gif">';



/**
 * Default HTML Template for the
 * messageBox class
 */
script  = '';
script += '            window.onclose = msgCancel;';
script += '            function msgConfirm(){ {CALLBACK} msgCancel() }';
//defaultTemplate += '            function msgCancel(){ window.close(); }';
script += 'function msgCancel() {';
script += '    var displayFrame = null;';
script += '    if (top.content.frames["right"]) {';
script += '        displayFrame = top.content.frames["right"].frames["right_bottom"].document;';
script += '    } else if (top.content.frames["right_bottom"]) {';
script += '        displayFrame = top.content.frames["right_bottom"].document;';
script += '    }';
script += '    ';
script += '    if (displayFrame && displayFrame.getElementById("message_box")) {';
script += '        var box = displayFrame.getElementById("message_box");';
script += '        displayFrame.getElementsByTagName("body")[0].removeChild(box);';
script += '    }';
script += '}';

defaultTemplate  = '';
defaultTemplate += '    <table height="{HEIGHT}" width="{WIDTH}" cellspacing="0" cellpadding="4" border="0">';
defaultTemplate += '        <tr valign="middle">';
defaultTemplate += '            <td>{IMAGE}</td>';
defaultTemplate += '            <td class="message_box_head">{HEADLINE}</td>';
defaultTemplate += '        </tr>';
defaultTemplate += '        <tr height="100%" valign="top">';
defaultTemplate += '            <td></td>';
defaultTemplate += '            <td class="message_box_text">{MESSAGE}</td>';
defaultTemplate += '        </tr>';
defaultTemplate += '        <tr>';
defaultTemplate += '            <td></td>';
defaultTemplate += '            <td align="right">{CANCEL}&nbsp;&nbsp;&nbsp;&nbsp;{CONFIRM}</td>';
defaultTemplate += '        </tr>';
defaultTemplate += '    </table>';

var bMsie = (document.all) ? true : false;

/**
 * Class to display errors and notifications
 *
 * @param headline string The headline of the message
 * @param message srint The message text
 * @param htmlTemplate
 *
 *

 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @version 0.9
 */
function messageBox(headline, message, htmlTemplate, width, height) {

    /* The error message
       headline */
    this.headline = headline || "";

    /* The error message /
       notification */
    this.message = message || "";

    /* HTML Template for
       the message  */
    this.html = htmlTemplate || defaultTemplate;
    this.script = script;

    /* Width of the popup,
       defaults to '350' */
    this.width = width || 350;

    /* Height of the popup,
       defaults to '170' */
    this.height = height || 170;

    /* Status of the popup,
       true  => popup open
       false => popup closed */
    this.status = false;

    /* Reference to the pop-up
       window. */
    this.winRef = false;

    this.actionFrameName = window.name;
}

/**
 * Displays a notification
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
messageBox.prototype.notify = function(head, msg) {
    /* Some required variables */
    var template    = this.html;
    var script      = this.script;
    var msg         = msg || this.message;
    var head        = head || this.headline;

    /* X and Y position where the
       pop-up is centered */
    var x = parseInt( (screen.availWidth / 2) - (this.width / 2) );
    var y = parseInt( (screen.availHeight / 2) - (this.height / 2) );

    /* Replace placeholder with
       the contents  */
    template = template.replace(/{HEADLINE}/,   head);
    template = template.replace(/{MESSAGE}/,    msg);
    template = template.replace(/{IMAGE}/,      button['warn']);
    script = script.replace(/{CALLBACK}/,   "");
    template = template.replace(/{CANCEL}/,     "");
    template = template.replace(/{CONFIRM}/,    button['ok']);
    template = template.replace(/{WIDTH}/,      this.width);
    template = template.replace(/{HEIGHT}/,     this.height);

    displayFrame = null;

    if (top.content.frames["right"]) {
        displayFrame = top.content.frames["right"].frames["right_bottom"];
    } else if (top.content.frames["right_bottom"]) {
        displayFrame = top.content.frames["right_bottom"];
    }

    var iFrameWidth = displayFrame.document.body.offsetWidth;
    var iFrameHeigth = displayFrame.window.innerHeight;
    if (bMsie) {
        iFrameHeigth = displayFrame.document.body.clientWidth-250;
    }

    var iPosLeft = parseInt((iFrameWidth-this.width)/2);
    var iPosTop = parseInt(((iFrameHeigth-this.height)/4)+displayFrame.document.body.scrollTop);

    var box = displayFrame.document.createElement("div");
    box.style.border = '1px solid #C54A33';
    box.style.backgroundColor = 'white';
    box.style.position = 'absolute';
    box.style.top = iPosTop+'px';
    box.style.left = iPosLeft+'px';
    box.style.zIndex = '1000000';
    box.id = 'message_box';
    box.innerHTML = template;

    var oScript = displayFrame.document.createElement("script");
    oScript.type = "text/javascript";
    oScript.text = script;

    if (typeof(displayFrame.window.msgCancel) == 'function') {
        displayFrame.window.msgCancel();
    }

    displayFrame.window.document.body.appendChild(oScript);
    displayFrame.window.document.body.appendChild(box);

    /* Open a new pop-up
       window */
    //this.winRef = window.open("", "", "left="+x+",top="+y+",width="+this.width+",height="+this.height+"\"");
    //this.winRef.moveTo(x, y);

    /* Write template */
    //this.winRef.document.open();
    //this.winRef.document.write(template);
    //this.winRef.document.close();

    /* Focus the pop-up */
    //this.winRef.focus();

}



/**
 * Displays a confirmation pop-up.
 *
 * @param head string Headline for the message
 * @param msg string The message
 * @param callback string Name of the function executed on confirmation
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
messageBox.prototype.confirm = function(head, msg, callback) {

    /* Some required variables */
    var template    = this.html;
    var script      = this.script;
    var msg         = msg || this.message;
    var head        = head || this.headline;

    /* X and Y position where the
       pop-up is centered */
    var x = parseInt( (screen.availWidth / 2) - (this.width / 2) );
    var y = parseInt( (screen.availHeight / 2) - (this.height / 2) );

    /* Replace placeholder with
       the contents  */
    template = template.replace(/{HEADLINE}/,   head);
    template = template.replace(/{MESSAGE}/,    msg);
    template = template.replace(/{IMAGE}/,      button['warn']);

	var sCallback = "";

    actionFrame = null;
    if (top.content.frames["right"] && this.actionFrameName.match(/right/g)) {
        actionFrame = 'top.content.frames["right"].frames["'+this.actionFrameName+'"]';
    } else if (top.content.frames["left"] && this.actionFrameName.match(/left/g)) {
        actionFrame = 'top.content.frames["left"].frames["'+this.actionFrameName+'"]';
    } else {
        actionFrame = 'top.content.frames["'+this.actionFrameName+'"]';
    }

	/* Check if the callback functions are passed as array */
	if (typeof(callback) == "object")
	{
		for (var i=0; i < callback.length; i++)
		{
			sCallback += actionFrame+"." + callback[i] + ";";
		}
	} else {
		sCallback = actionFrame+"." + callback + ";";
	}

    script = script.replace(/{CALLBACK}/,   sCallback);
    template = template.replace(/{CANCEL}/,     button['cancel']);
    template = template.replace(/{CONFIRM}/,    button['confirm']);
    template = template.replace(/{WIDTH}/,      this.width);
    template = template.replace(/{HEIGHT}/,     this.height);

    displayFrame = null;

    if (top.content.right) {
        displayFrame = top.content.right.right_bottom;
    } else if (top.content.right_bottom) {
        displayFrame = top.content.right_bottom;
    }
    var iFrameWidth = displayFrame.document.body.offsetWidth;
    var iFrameHeigth = displayFrame.window.innerHeight;
    if (bMsie) {
        iFrameHeigth = displayFrame.document.body.clientWidth-250;
    }

    var iPosLeft = parseInt((iFrameWidth-this.width)/2);
    var iPosTop = parseInt(((iFrameHeigth-this.height)/4)+displayFrame.document.body.scrollTop);

    var box = displayFrame.document.createElement("div");
    box.style.border = '1px solid #C54A33';
    box.style.backgroundColor = 'white';
    box.style.position = 'absolute';
    box.style.top = iPosTop+'px';
    box.style.left = iPosLeft+'px';
    box.style.zIndex = '1000000';
    box.id = 'message_box';
    box.innerHTML = template;

    var oScript = displayFrame.document.createElement("script");
    oScript.type = "text/javascript";
    oScript.text = script;

    if (typeof(displayFrame.window.msgCancel) == 'function') {
        displayFrame.window.msgCancel();
    }

    displayFrame.document.body.appendChild(oScript);
    displayFrame.document.body.appendChild(box);


    /* Open a new pop-up window */
    // this.winRef = window.open("", "", "left="+x+",top="+y+",width="+this.width+",height="+this.height+"\"");
    //this.winRef.moveTo(x, y);

    /* Write template */
    //this.winRef.document.open();
    //this.winRef.document.write(template);
    //this.winRef.document.close();

    /* Focus the pop-up */
    //this.winRef.focus();
}

} catch(e) {
  /* error catching is for weenies ! */
}

function performAction (area, action, frame, itemtype, itemid, sid)
{
    url  = 'main.php?area='+area;
    url += '&action='+action;
    url += '&frame='+frame;
    url += '&' + itemtype + '=' + itemid;
    url += '&contenido=' + sid;

	if (frame == 1)
    {
		parent.parent.left.left_top.location.href = url;
    }
	if (frame == 2)
    {
		parent.parent.left.left_bottom.location.href = url;
    }
	if (frame == 3)
    {
		parent.parent.right.right_top.location.href = url;
    }
	if (frame == 4)
    {
		parent.parent.right.right_bottom.location.href = url;
    }
}
