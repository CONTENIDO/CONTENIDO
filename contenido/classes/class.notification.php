<?php
cInclude("classes", "class.table.php");

/**
 * class Contenido_Notification
 *
 * Class for displaying notifications
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @modified 04-04-2008 Timo Trautmann -  added new colors and functions for direct output
 * @copyright four for business AG <http://www.4fb.de>
 *
 */
class Contenido_Notification {

    /**
     * Constructor
     */
    function Contenido_Notification() {

    } # end function

	/**
	 * New message style without tables - please use this
	 */
	function messageBox ($level, $message, $style)
	{
        global $cfg;
        
        switch ($level)
        {
        case "error":
            $head = i18n('Error');
            $head_class = 'alertbox_error';
            $frameColor = $cfg["color"]["notify_error"];
            $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_fatalerror.gif";
          break;
                
        case "warning":
            $head = i18n('Warning');
            $head_class = 'alertbox_warning';
            $bgColor = $cfg["color"]["notify_warning"];
            $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_warning.gif";
          break;
                
        case "info":
            $head = i18n('Info');
            $head_class = 'alertbox_info';
            $message = '<span style="color:#435d06">'.$message.'</span>';
            $bgColor = $cfg["color"]["notify_info"];
            $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif";
          break;

        default:
            $head = i18n('Notification');
            $head_class = 'alertbox_notification';
            $message = '<span style="color:#435d06">'.$message.'</span>';
            $bgColor = $cfg["color"]["notify"];
            $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif";
          break;
        }
		
				// Box on login page
				if($style == 1) {
					$messageBox = 
						'<div class="alertbox '.$head_class.'_color" id="contenido_notification" style="border-top:0px;">' .
							'<h1 class="alertbox_head ' . $head_class . '">' . $head . '</h1>' .
							'<div class="alertbox_message">' . $message . '</div>' .
						'</div>';
						
				}
				// Simple box
				else {
					$messageBox = 
						'<div class="alertbox_line '.$head_class.'_color" id="contenido_notification">' .
							'<h1 class=" alertbox_head ' . $head_class . ' '.$head_class.'_color">' . $head . '</h1>' .
							'<div class="alertbox_message '.$head_class.'_color">' . $message . '</div>' .
						'</div>';
			    }
		return $messageBox;
	}
	
    function returnNotification($level, $message)
    {
        global $cfg;
        
        switch ($level)
        {
            case "error":
                $bgColor = $cfg["color"]["notify_error"];
                $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_fatalerror.gif";
                break;
                
            case "warning":
                $bgColor = $cfg["color"]["notify_warning"];
                $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_warning.gif";
                break;
                
            case "info":
                $message = '<span style="color:#435d06">'.$message.'</span>';
                $bgColor = $cfg["color"]["notify_info"];
                $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif";
                break;

            default:
                $message = '<span style="color:#435d06">'.$message.'</span>';
                $bgColor = $cfg["color"]["notify"];
                $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif";
                break;
        }

        $table = new Table($bgColor, "solid", 0, 2, "#FFFFFF", "#FFFFFF", "#FFFFFF", true, false);
        
        $noti = '<div id="contenido_notification" style="position: relative; left: 0; top: 0; z-index: 100000000;">';
        $noti .= $table->start_table();
        $noti .= $table->header_row();
        $noti .= $table->borderless_cell('<img src="'.$imgPath.'" />');
        $noti .= $table->borderless_cell('<font color="'.$bgColor.'" style="font-family: Verdana, Arial, Helvetica, Sans-Serif; font-size: 11px;">' .$message. '</font>', "left", "middle");
        $noti .= $table->end_row();
        $noti .= $table->end_table();
        $noti .= '</div>';

        return $noti;
    }
    
    /**
     * Function displays small message box directly
     * @param string $level - warning, error or info
     * @param string $message - displayed messagestring
     * @return void
     */
    function displayNotification($level, $message) {
        echo $this->returnNotification($level,$message)."<br>"; 
    } # end function
    
    /**
     * Function displays large message box directly
     * @param string $level - warning, error or info
     * @param string $message - displayed messagestring
     * @param boolean $style - use styles for display or not
     * @return void
     */
    function displayMessageBox($level, $message, $style = 1) {
        echo $this->messageBox($level, $message, $style)."<br>";  
    } # end function

} # end class

?>
