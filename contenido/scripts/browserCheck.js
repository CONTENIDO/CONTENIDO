/**
 * browserCheck class
 * Check if Browser is compatible with CONTENIDO.
 *
 * @author     Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @version    SVN Revision $Rev$
 * @deprecated [2013-10-16] Logic moved to contenido.js
 */
function browserCheck()
{
    var agent = navigator.userAgent.toLowerCase();

    this.IE = false;
    this.NS = false;

    if (agent.indexOf("msie") >= 0)
    {
        this.IE = true;
        this.version = parseInt(agent.appVersion);
        return;
    }

    if (agent.indexOf("netscape") >= 0 || agent.indexOf("mozilla") >= 0)
    {
        this.NS = true;
        this.version = parseInt(agent.appVersion);
        return;
    }

} // browserCheck

is = new browserCheck();
