/*****************************************
*
* $Id: header.js,v 1.2 2004/03/22 14:46:52 timo.hummel Exp $
*
* File      :   $RCSfile: header.js,v $
* Project   :
* Descr     :
*
* Author    :   $Author: timo.hummel $
* Modified  :   $Date: 2004/03/22 14:46:52 $
*
* © four for business AG, www.4fb.de
******************************************/

function Highlight(sObject, sTextObj, size)
{
    this.object = document.getElementById(sObject);
    this.tObject = document.getElementById(sTextObj);
    this.imageSize = size;
}

Highlight.prototype.getPos = function(oElement)
{
    this.x = 0;
    this.y = 0;

    var el = oElement;

    while (el.tagName != "BODY")
    {
        this.x += el.offsetLeft;
        this.y += el.offsetTop;
        el = el.offsetParent;
    }
}

Highlight.prototype.moveTo = function(x, y)
{
    this.object.style.left = x + "px";
    this.object.style.top  = y + "px";
    this.object.style.display = "block";
}

Highlight.prototype.on = function(obj, size)
{
    this.getPos(obj);
    xpos = this.x - this.imageSize;
    this.moveTo(xpos, this.y);
    this.showText(obj.innerHTML);
}

Highlight.prototype.showText = function(sText)
{
    this.tObject.innerHTML = sText;
}

var active_main;
var active_sub;
var active_link;
var active_sub_link;

function show(id, slink) {
    document.getElementById("sub_0").style.display = "none";
    
    if (active_main) {
        hide(active_main);
    }

    if (active_link) {
        active_link.className = "main";
    }
    document.getElementById(id).style.display = "block";
    resetLinkStyleNavigation(document.getElementById("head_nav1"));
    if (slink) {
        slink.style.color = "#0060B1";
    }
    active_main = id;
}

function hide(id) {
    document.getElementById(id).style.display = "none";
    active_main = 0;
}

function resetLinkStyleNavigation(element)
  {
	spanlist = element.getElementsByTagName("span");
  for (i = 0; i < spanlist.length; i++)
    {
    spanlist[i].firstChild.style.color = "#000000";
    }
  }

function imgOn(id, slink) {
    if (active_sub) {
        imgOff(active_sub);
        active_sub_link.className = 'sub';
    }

    slink.className = 'sub_on';
    active_sub_link = slink;
    document.getElementById(id).src = "images/arrow.gif";
    active_sub = id;
}

function imgOff(id) {
    document.getElementById(id).src = "images/spacer.gif";
}

