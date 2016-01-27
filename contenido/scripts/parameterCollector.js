/**
 * CONTENIDO JavaScript parameter collector module
 *
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */

var m_documentParameters = [];

function register_parameter (name, value)
{
    m_documentParameters[name] = value;
}

function get_registered_parameters ()
{
    var output = "";

    for (var parameter in m_documentParameters)
    {
        output += "&" + parameter + "=" + m_documentParameters[parameter];
    }

    return output;
}

function append_registered_parameters (form)
{
    for (var parameter in m_documentParameters)
    {
        var el = document.createElement("input");
        el.type = "hidden";
        el.name =  parameter;
        el.value = m_documentParameters[parameter];
        form.appendChild(el);
    }
}
