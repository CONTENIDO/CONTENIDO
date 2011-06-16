<?php

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}  

cInclude('classes','module/class.contenido.module.handler.php');
cInclude("classes", "class.ui.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.htmlvalidator.php");
cInclude("external", "edit_area/class.edit_area.php");
cInclude("includes", "functions.file.php");

class Contenido_Modul_Templates_Handler extends Contenido_Module_Handler {

    #Form fields
    private $_code;
    private $_file;
    private $_tmp_file;
    private $_area;
    private $_frame;
    private $_status;
    private $_action;
    private $_new;
    private $_delete;
    private $_selectedFile;
    private $_reloadScript;
    private $_page = NULL;
    
    /**
     * 
     * The file end of template files.
     * @var string
     */
    private $_templateFileEnding = 'html';
    
    /**
     * 
     * The name of the new file. 
     * @var string
     */
    private $_newFileName = "NewFileName";
    
    private $_actionCreate = 'htmltpl_create';
    private $_actionEdit = 'htmltpl_edit';
    private $_actionDelete = 'htmltpl_delete';
    
    
    /**
     * 
     * In template we test if we have premission for htmltpl. 
     * @var string
     */
    private $_testArea = 'htmltpl';
    
    public function __construct($idmod) {
        parent::__construct($idmod);
        $this->_page = new cPage();
        $this->_page->setEncoding(Contenido_Vars::getVar('encoding'));
       
    }

    
    /**
     * 
     * Set the new delete from Form.
     * This are set if user had push the delete or new button.
     * 
     * @param string $new
     * @param string $delete
     */
    public function setNewDelete($new, $delete) {
        $this->_new = $new;
        $this->_delete = $delete;
    }
    
    /**
     * 
     * Set the code from Form!
     * 
     * 
     * @param string $code
     */
    public function setCode( $code) {
        $this->_code = stripslashes($code);
    }

    /**
     * 
     * Set the selected file from Form.
     * 
     * 
     * @param string $selectedFile
     */
    public function setSelectedFile($selectedFile) {
        $this->_selectedFile = $selectedFile;
    }
    /**
     * 
     * Set the file and tmpFile from Form.
     * (get it with $_Request...)
     * 
     * @param string $file
     * @param string $tmpFile
     */
    public function setFiles($file, $tmpFile) {

        $this->_file = $file;
        $this->_tmp_file = $tmpFile;
    }
    
    /**
     * 
     * Set the status it can be send or empty ''
     * @param string $status
     */
    public function setStatus($status) {
        $this->_status = $status;
    }
    
    /**
     * 
     * Set $frame and idmod and are.
     * 
     * @param int $frame
     * @param int $idmod
     * @param int $area
     */
    public function setFrameIdmodArea($frame, $idmod ,$area) {

        $this->_frame = $frame;
        $this->_idmod = $idmod;
        $this->_area = $area;
    }
    
    /**
     * 
     * We have two actions wich culd send from form. 
     * 
     * @param string $action
     */
    public function setAction(  $action) {
        $this->_action = $action;
    }
    
    
    /**
     *
     * Heir are the method witch decide what action are send from
     * user (form).
     */
    private function _getAction() {

        if( isset($this->_status)) {

            if(isset($this->_new))
            return 'new';
             
            if(isset($this->_delete))
            return 'delete';

            if(isset($this->_file) && isset($this->_tmp_file)) {
                 
                if($this->_file == $this->_tmp_file) {
                     
                    #file ist empty also no file in template
                    #directory
                    if(empty($this->_file)) {
                         
                        return 'empty';
                    } else
                    return 'save';
                }
                 
                 
                if($this->_file != $this->_tmp_file)
                return 'rename';
                 
            } else  {
                #one of files (file or tmp_file) is not set
                throw  new Exception(i18n('Field of the filename is empty!'));
            }
             
             
        } else {
            return 'default';
        }

        #default case
        return 'default';
    }

    /**
     *
     * Has the selected file changed.
     */
    private function _hasSelectedFileChanged() {

        if($this->_file != $this->_selectedFile) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     *
     * save the code in the file
     */
    private function _save() {
        
        #save the contents of file
        $this->makeNewModuleFile('template' , $this->_file , $this->_code);
        	  
        #if user selected other file display it
        if($this->_hasSelectedFileChanged()) {
            $this->_file = $this->_selectedFile;
            $this->_tmp_file = $this->_selectedFile;
        }
    }

    /**
     *
     * rename a file in template directory
     * @throws Exception if rename not success
     */
    private function _rename() {

        if( $this->renameModulFile('template',$this->_tmp_file, $this->_file) == false) {
            throw new Exception(i18n("Rename of the file not successfully!"));
        } else { #
            $this->makeNewModuleFile('template', $this->_file,$this->_code);

            $this->_tmp_file = $this->_file;

        }

    }

    /**
     *
     * Make new file
     */
    private function _new() {

        $fileName = '';
        if($this->existFile('template', $this->_newFileName.'.'.$this->_templateFileEnding)) {

            $fileName = $this->_newFileName.$this->getFiveRandomCharacter().".".$this->_templateFileEnding;
            $this->makeNewModuleFile('template', $fileName ,'');
        } else {
            $this->makeNewModuleFile('template', $this->_newFileName.'.'.$this->_templateFileEnding ,'');
            $fileName = $this->_newFileName.".".$this->_templateFileEnding;
        }
        #set to new fileName
        $this->_file = $fileName;
        $this->_tmp_file = $fileName;
    }


    /**
     *
     * Delete a file
     */
    private function _delete() {

        
        $this->deleteFile('template',$this->_tmp_file);

        $files = $this->getAllFilesFromDirectory('template');
         
        if(is_array($files)){
             
            if(!key_exists('0' , $files)){
                $this->_file = '';
                $this->_tmp_file = '';
                 
            } else {
                $this->_file = $files[0];
                $this->_tmp_file = $files[0];
            }
        }
    }


     


    /**
     *
     * Default case
     */

    public function _default() {

        $files = $this->getAllFilesFromDirectory('template');

        #one or more templates files are in template direcotry
        if(count($files)> 0) {
             
            $this->_tmp_file = $files[0];
            $this->_file = $files[0];
        } else {
            #template directory is empty
            $this->_file = '';#$this->getTemplateFileName();
            $this->_tmp_file = '';#$this->getTemplateFileName();
             
        }


    }

    
    /**
     * 
     * Have the user premissions for the actions.
     * 
     * @param Contenido_Perm $perm
     * @param Contenido_Notification $notification
     * @param string $action
     */
    private function _havePremission($perm , $notification , $action) {
    
 
        switch($action) {
            
            case 'new':
                 if (!$perm->have_perm_area_action($this->_testArea, $this->_actionCreate))
                 {
                    $notification->displayNotification("error", i18n("Permission denied"));
                    return -1;
                 }
                break;
                
            case 'save':
            case 'rename':
                if (!$perm->have_perm_area_action($this->_testArea, $this->_actionEdit))
                 {
                    $notification->displayNotification("error", i18n("Permission denied"));
                    return -1;
                 }
                break;
            case 'delete':
                if (!$perm->have_perm_area_action($this->_testArea, $this->_actionDelete))
                 {
                    $notification->displayNotification("error", i18n("Permission denied"));
                    return -1;
                 }
                break ;
            default:
                    return true ;
                break;
                
        }
        
    } 
    /**
     * 
     * This method test the code if the client setting htmlvalidator 
     * is not set to false.
     */
    private function _validateHTML($notification) {
       
    /* Try to validate html */
		if (getEffectiveSetting("layout", "htmlvalidator", "true") == "true" && $this->_code !== "")
		{
			$v = new cHTMLValidator;
			$v->validate($this->_code);
			$msg = "";

			foreach ($v->missingNodes as $value)
			{
				$idqualifier = "";

				$attr = array();
			
				if ($value["name"] != "")
				{
					$attr["name"] = "name '".$value["name"]."'";
				}
			
				if ($value["id"] != "")
				{
					$attr["id"] = "id '".$value["id"]."'";
				}
			
				$idqualifier = implode(", ",$attr);
			
				if ($idqualifier != "")
				{
					$idqualifier = "($idqualifier)";	
				}
				$msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value["tag"], $idqualifier, $value["line"],$value["char"]) . "<br />";
			}
		
			if ($msg != "")
			{
				$notification->displayNotification("warning", $msg) . "<br />";
			}
		}
    }
    
    private function _makeFormular($belang) {
         
        $form = new UI_Table_Form("file_editor");
        $form->addHeader(i18n("Edit file"));
        $form->setWidth("100%");

        $form->setVar("area", $this->_area);
        $form->setVar("action", $this->_action);
        $form->setVar("frame", $this->_frame);
        $form->setVar("status", 'send');
        $form->setVar("tmp_file", $this->_tmp_file);
        $form->setVar("idmod", $this->_idmod);
        $form->setVar("file", $this->_file);

        $selectFile = new cHTMLSelectElement('selectedFile');
        #array with all files in template directory
        $filesArray =$this->getAllFilesFromDirectory('template');

       

        #make options fields
        foreach( $filesArray as $key => $file) {

            $optionField = new cHTMLOptionElement($file,$file);

            #select the current file
            if($file == $this->_file) {
                $optionField->setAttributes('selected','selected');
            }
             
            $selectFile->addOptionElement($key, $optionField);
        }


        $inputAdd = new cHTMLTextbox('new',i18n('Make new template file'),60);
        $inputAdd->setAttribute('type', 'image');
        $inputAdd->setClass("addfunction");
        //$inputAdd->setAttribute("alt",i18n("New template file"));

        $inputDelete = new cHTMLTextbox('delete',$this->_file,60);
        $inputDelete->setAttribute('type', 'image');
        $inputDelete->setClass("deletefunction");
        //$inputDelete->setAttribute("alt",i18n("Delete file"));
        $aDelete = new cHTMLLink('');
        $aDelete->setContent($this->_file);
        $aDelete->setClass('deletefunction');

        $aAdd =new cHTMLLink('');
        $aAdd->setContent(i18n("New Template file"));
        $aAdd->setClass('addfunction');

        // $tb_name = new cHTMLLabel($sFilename,'');
        $tb_name = new cHTMLTextbox("file", $this->_file, 60);
         
        $ta_code = new cHTMLTextarea("code", htmlspecialchars($this->_code), 100, 35, "code");


        $ta_code->setStyle("font-family: monospace;width: 100%;");
         
        $ta_code->updateAttributes(array("wrap" => getEffectiveSetting('html_editor', 'wrap', 'off')));
        $form->add(i18n('Action'),$inputDelete->toHTML());
        $form->add(i18n('Action'), $inputAdd->toHTML());
        $form->add(i18n("File"),$selectFile);
        $form->add(i18n("Name"),$tb_name);
        $form->add(i18n("Code"),$ta_code);
        $this->_page->setContent($notis . $form->render());
        
        $oEditArea = new EditArea('code', 'html', substr(strtolower($belang), 0, 2), true, $this->_cfg);
        $this->_page->addScript('editarea', $oEditArea->renderScript());

        
        //$this->_reloadScript = "das ist mein script hello world rusmirus";
        //$this->_page->addScript('reload', $this->_reloadScript);
        $this->_page->render();
         
    }




    /**
     * 
     * Display the form and evaluate the action and excute the action.
     * 
     * @param Contenido_Perm $perm
     * @param Contenido_Notification $notificatioin
     */
    public function display($perm , $notificatioin , $belang) {
        
        $myAction = $this->_getAction();
        
      
        #if the user dont have premissions 
        if( $this->_havePremission($perm, $notificatioin , $myAction) === -1)
            return;
       
        try {
            switch($myAction) {
                case 'save':
                    $this->_save();
                    break;
                case 'rename':
                    $this->_rename();
                    break;
                case 'new':
                    $this->_new();
                    break;
                case 'delete':
                    $this->_delete();
                    break;
                     
                default:
                    $this->_default();

                    break;

            }
            
            $this->_code = $this->getFilesContent('template','',$this->_file);
            $this->_validateHTML($notificatioin);
            $this->_makeFormular($belang);

        }catch(Exception $e) {
            $notificatioin->displayNotification('error', i18n($e->getMessage()));
        }

    }


}


?>