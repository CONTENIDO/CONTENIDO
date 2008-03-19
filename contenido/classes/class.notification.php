<?php
cInclude("classes", "class.table.php");

/**
 * class Contenido_Notification
 *
 * Class for displaying notifications
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
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
					$head_class = 'alertbox_head alertbox_error';
          $frameColor = $cfg["color"]["notify_error"];
          $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_fatalerror.gif";
          break;
                
        case "warning":
					$head = i18n('Warning');
					$head_class = 'alertbox_head alertbox_warning';
          $bgColor = $cfg["color"]["notify_warning"];
          $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_warning.gif";
          break;
                
        case "info":
					$head = i18n('Info');
					$head_class = 'alertbox_head alertbox_info';
          $bgColor = $cfg["color"]["notify_info"];
            //$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_ok.gif";
            $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."spacer.gif";
          break;

        default:
					$head = i18n('Notification');
					$head_class = 'alertbox_head alertbox_notification';
          $bgColor = $cfg["color"]["notify"];
            //$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_ok.gif";
            $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."spacer.gif";
          break;
        }
		
				// Box on login page
				if($style == 1)
					{
					$messageBox = 
						'<div class="alertbox">' .
							'<h1 class="' . $head_class . '">' . $head . '</h1>' .
							'<div class="alertbox_message">' . $message . '</div>' .
						'</div>';
						
					}
				// Simple box
				else
					{
					$messageBox = 
						'<div class="alertbox_line">' .
							'<h1 class="' . $head_class . '">' . $head . '</h1>' .
							'<div class="alertbox_message">' . $message . '</div>' .
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
                $bgColor = $cfg["color"]["notify_info"];
                //$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_ok.gif";
                $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."spacer.gif";
                break;

            default:
                $bgColor = $cfg["color"]["notify"];
                //$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_ok.gif";
                $imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."spacer.gif";
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
     * Begins the new table
     * @param none
     * @return void
     */
    function displayNotification($level, $message) {

        echo $this->returnNotification($level,$message)."<br>"; 
        
        
    } # end function

} # end class

?>
