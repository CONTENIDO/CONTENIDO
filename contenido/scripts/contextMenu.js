var display_url;

function createMenu (menuname, items, links)
{
	document.writeln('<div id="'+menuname+'" class="skin0" onMouseover="highlightie5(event)" onMouseout="lowlightie5(event)" onClick="jumptoie5(event)" display:none>');
	document.writeln('<div class="skin1">Context Menu</div>');
	for (var i=0;i<items.length;i++)
	{
		if (items[i] == "--")
		{
			document.writeln('<hr style="line-height:4px;border: 0px;border-bottom:1px;border-color: black; border-style: solid;">');
		} else {
			document.writeln('<div class="menuitems" url="'+links[i]+'">'+items[i]+'</div>');
		}
	}
	
	document.writeln('</div>');

	document.getElementById(menuname).style.display='';
}

var ie5=document.all&&document.getElementById
var ns6=document.getElementById&&!document.all
if (ie5||ns6)
//var menuobj=document.getElementById("ie5menu")

function showmenuie5(e){
	
if (ie5)
{
	menuobj = document.getElementById(window.event.srcElement.getAttribute("helpid"));
} else
{
	menuobj = document.getElementById(e.target.getAttribute("helpid"));
}


//Find out how close the mouse is to the corner of the window
var rightedge=ie5? document.body.clientWidth-event.clientX : window.innerWidth-e.clientX
var bottomedge=ie5? document.body.clientHeight-event.clientY : window.innerHeight-e.clientY

//if the horizontal distance isn't enough to accomodate the width of the context menu
if (rightedge<menuobj.offsetWidth)
//move the horizontal position of the menu to the left by it's width
menuobj.style.left=ie5? document.body.scrollLeft+event.clientX-menuobj.offsetWidth : window.pageXOffset+e.clientX-menuobj.offsetWidth
else
//position the horizontal position of the menu where the mouse was clicked
menuobj.style.left=ie5? document.body.scrollLeft+event.clientX : window.pageXOffset+e.clientX

//same concept with the vertical position
if (bottomedge<menuobj.offsetHeight)
menuobj.style.top=ie5? document.body.scrollTop+event.clientY-menuobj.offsetHeight : window.pageYOffset+e.clientY-menuobj.offsetHeight
else
menuobj.style.top=ie5? document.body.scrollTop+event.clientY : window.pageYOffset+e.clientY

menuobj.style.visibility="visible"

	
	return false;


return false

}

function hidemenuie5(e){
if (typeof menuobj == "object")
{
	menuobj.style.visibility="hidden"
}
}

function highlightie5(e){
var firingobj=ie5? event.srcElement : e.target
if (firingobj.className=="menuitems"||ns6&&firingobj.parentNode.className=="menuitems"){
if (ns6&&firingobj.parentNode.className=="menuitems") firingobj=firingobj.parentNode //up one node
firingobj.style.backgroundColor="#F4F8BD"
firingobj.style.color="black"
firingobj.style.cursor="pointer"
if (display_url==1)
window.status=event.srcElement.url
}
}

function lowlightie5(e){
var firingobj=ie5? event.srcElement : e.target
if (firingobj.className=="menuitems"||ns6&&firingobj.parentNode.className=="menuitems"){
if (ns6&&firingobj.parentNode.className=="menuitems") firingobj=firingobj.parentNode //up one node
firingobj.style.backgroundColor=""
firingobj.style.color="black"
window.status=''
}
}

function jumptoie5(e){
var firingobj=ie5? event.srcElement : e.target
if (firingobj.className=="menuitems"||ns6&&firingobj.parentNode.className=="menuitems"){
if (ns6&&firingobj.parentNode.className=="menuitems") firingobj=firingobj.parentNode
if (firingobj.getAttribute("target"))
window.open(firingobj.getAttribute("url"),firingobj.getAttribute("target"))
else
window.location=firingobj.getAttribute("url")
}
}

var menuobj;

if (ie5||ns6){
//menuobj.style.display=''
document.oncontextmenu=showmenuie5
document.onclick=hidemenuie5
}