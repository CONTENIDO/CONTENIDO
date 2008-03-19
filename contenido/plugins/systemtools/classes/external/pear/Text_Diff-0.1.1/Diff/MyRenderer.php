<?php
/**
 * A class to render Diffs in table format.
 *
 * @modified 15.11.2005 by Willi Man
 *           This class is based on class Text_Diff_Renderer.
 *           I do not inherit class Text_Diff_Renderer because I need a different render mechanism.
 */

if (isset($cfg))
{
	include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/systemfunctions.php');
}else
{
	print "Class My_Text_Diff_Renderer requires Contenido class class.template.php and requires a Contenido environment!";
}
 
class My_Text_Diff_Renderer{


    /**
     * Constructor.
     */
    function My_Text_Diff_Renderer($stringTitle, $bShowEmptyDiff = true, $params = array(), &$cfg, $sTemplate = '')
    {
        foreach ($params as $param => $value) {
            $v = '_' . $param;
            if (isset($this->$v)) {
                $this->$v = $value;
            }
        }
        
        # modified 15.11.2005 by Willi Man
        $this->objTemplateEngine = new Template();
        $this->sTemplate = $sTemplate;
        $this->stringTitle = $stringTitle;
        $this->bShowEmptyDiff = $bShowEmptyDiff;
        $aConfig = $cfg;
    }

    /**
     * Get any renderer parameters.
     *
     * @return array  All parameters of this renderer object.
     */
    function getParams()
    {
        $params = array();
        foreach (get_object_vars($this) as $k => $v) {
            if ($k{0} == '_') {
                $params[substr($k, 1)] = $v;
            }
        }

        return $params;
    }

    /**
     * Renders a diff.
     *
     * @param Text_Diff $diff  A Text_Diff object.
     *
     * @return string  The formatted output.
     * 
     * @modified 15.11.2005 by Willi Man
     */
    function render($diff)
    {

		$this->objTemplateEngine->reset();

		$this->objTemplateEngine->set('s', 'title', $this->stringTitle);

		if ($diff->isEmpty())
		{
			$bDifference = false;
		}else
		{
			$bDifference = true;
		}

        foreach ($diff->getDiff() as $objectEdit) 
		{
			
			switch (strtolower(get_class($objectEdit))) 
			{
	            case 'text_diff_op_copy':
	                $strinTypeOfDiff = 'copy';
	                $sBackgroundColor = '#fff';
	                break;
	            case 'text_diff_op_add':
	                $strinTypeOfDiff = 'add';
	                $sBackgroundColor = '#ffff5c';
	                break;
	            case 'text_diff_op_delete':
	                $strinTypeOfDiff = 'delete';
	                $sBackgroundColor = '#ffff5c';
	                break;
	            case 'text_diff_op_change':
	                $strinTypeOfDiff = 'change';
	                $sBackgroundColor = '#ffff5c';
	                break;
	            default:
	                $strinTypeOfDiff = 'copy';
	                $this->objTemplateEngine->set('d', 'background_color', '#fff');
            }
			
			$this->objTemplateEngine->set('d', 'background_color', $sBackgroundColor);
			$this->objTemplateEngine->set('d', 'type_of_difference', $strinTypeOfDiff);
			
			$arrayOfOriginalCode = $objectEdit->orig;
			$stringOriginalCode = '';
			
			if ($this->bShowEmptyDiff OR $bDifference) 
			{
				for ($i = 0; $i < count($arrayOfOriginalCode); $i++)
				{
					$stringOriginalCode .= htmlentities($arrayOfOriginalCode[$i]);
					$stringOriginalCode .= "<br />";
				}
			}else
			{
				$stringOriginalCode .= "Equal";
			}
			
			if (strlen($stringOriginalCode) == 0)
			{
				$stringOriginalCode = '&nbsp;';
			}
			
			$this->objTemplateEngine->set('d', 'original', $stringOriginalCode);
			
			$arrayOfFinalCode = $objectEdit->final;
			$stringFinalCode = '';
			
			if ($this->bShowEmptyDiff OR $bDifference) 
			{
				for ($f = 0; $f < count($arrayOfFinalCode); $f++)
				{
					$stringFinalCode .= htmlentities($arrayOfFinalCode[$f]);
					$stringFinalCode .= "<br />";
				}
			}else
			{
				$stringFinalCode .= "Equal";
			}
			
			if (strlen($stringFinalCode) == 0)
			{
				$stringFinalCode = '&nbsp;';
			}
			
			$this->objTemplateEngine->set('d', 'final', $stringFinalCode);
			
			$this->objTemplateEngine->next();
		}
		
		$sHTMLOutput = $this->objTemplateEngine->generate($this->sTemplate, true);
		
		return $sHTMLOutput;
    }

}
