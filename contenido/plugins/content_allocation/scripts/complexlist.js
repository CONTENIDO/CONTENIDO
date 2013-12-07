/*
 * ul2finder
 * written by Christian Heilmann (http://icant.co.uk)
 * turns the nested list with the ID "finder" into a dynamic list
 * uses the CSS classes defined in the variables
 */
function ul2finder()
{
    // Define variables used and classes to be applied/removed
    var i,uls,als,finder;
    var parentClass='parent';
    var showClass='shown';
    var hideClass='hidden';
    var openClass='open';

    // check if our finder list exists, if not, stop all activities
    finder=document.getElementById('finder');
    if(!finder){return;}

    // add the class domenabled to the body
    cssjs('add',document.body,'domenabled');

    // loop through all lists inside finder, position and hide them
    // by applying the class hidden
    uls=document.getElementById('finder').getElementsByTagName('ul');
    for(i=0;i<uls.length;i++)
    {
        cssjs('add',uls[i],hideClass);
    }

    // loop through all links of inside finder
    lis=document.getElementById('finder').getElementsByTagName('li');
    for(i=0;i<lis.length;i++)
    {
        var styleBgColor = lis[i].attributes["baseClass"].value

        // if the li containing the link has no nested list, skip this one
        if(!lis[i].getElementsByTagName('ul')[0])
        {
            //lis[i].className = "bright";
            cssjs('add',lis[i],styleBgColor);
            continue;
        }

        var newa=document.createElement('a');
        newa.href='#';

        newa.appendChild(document.createTextNode(lis[i].firstChild.nextSibling.nodeValue));
        lis[i].replaceChild(newa,lis[i].firstChild.nextSibling);
        // otherwise apply the parent class
        //alert(lis[i].innerHTML);
        cssjs('add',newa,styleBgColor);

        // if the user clicks on the link
        lis[i].getElementsByTagName('a')[0].onclick=function()
        {
            // loop through all lists inside finder
            for(var i=0;i<uls.length;i++)
            {
                // avoid the list connected to this link
                var found=false;
                for(j=0;j<uls[i].getElementsByTagName('ul').length;j++)
                {
                    if(uls[i].getElementsByTagName('ul')[j] ==
                        this.parentNode.getElementsByTagName('ul')[0])
                    {
                        found=true;
                        break;
                    }
                }
                // and hide all others
                if(!found)
                {
                    cssjs('add',uls[i],hideClass)
                    cssjs('remove',uls[i],showClass)
                    cssjs('remove',uls[i].parentNode.getElementsByTagName('a')[0],openClass)
                    cssjs('add',uls[i].parentNode.getElementsByTagName('a')[0],parentClass)
                    // remove all old "open" elements
                    styleColor = uls[i].parentNode.getElementsByTagName('a')[0].style.backgroundColor;
                    if (styleColor == "#E2E2E2" || styleColor == "rgb(226, 226, 226)") {
                        uls[i].parentNode.getElementsByTagName('a')[0].style.backgroundColor = '';
                    }
                }
            }

            // change the current link from parent to open
            cssjs('swap',this,parentClass,openClass)
            this.style.backgroundColor = "#E2E2E2";

            // show the current nested list
            cssjs('add',this.parentNode.getElementsByTagName('ul')[0],showClass)

            // don't follow the real HREF of the link
            return false;
        }
    }
    /*
     * cssjs
     * written by Christian Heilmann (http://icant.co.uk)
     * eases the dynamic application of CSS classes via DOM
     * parameters: action a, object o and class names c1 and c2 (c2 optional)
     * actions: swap exchanges c1 and c2 in object o
     *            add adds class c1 to object o
     *            remove removes class c1 from object o
     *            check tests if class c1 is applied to object o
     * example:    cssjs('swap',document.getElementById('foo'),'bar','baz');
     */
    function cssjs(a,o,c1,c2)
    {
        switch (a){
            case 'swap':
                o.className=!cssjs('check',o,c1)?o.className.replace(c2,c1):o.className.replace(c1,c2);
            break;
            case 'add':
                if(!cssjs('check',o,c1)){o.className+=(o.className)?' '+c1:c1;}
            break;
            case 'remove':
                var rep=o.className.match(' '+c1)?' '+c1:c1;
                o.className=o.className.replace(rep,'');
            break;
            case 'check':
                return new RegExp('\\b'+c1+'\\b').test(o.className)
            break;
        }
    }
}

// Check if the browser supports DOM, and start the script if it does.
if(document.getElementById && document.createTextNode)
{
    window.onload=ul2finder;
}
