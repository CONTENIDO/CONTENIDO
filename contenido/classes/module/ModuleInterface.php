<?php
interface ModuleInterface {
    /**
     * 
     * Configuration layer in EditMod for module.
     * @param array $post 
     */
    public function setConfigEditMode();
    
    /**
     * 
     * HTML output of modul
     */
    public function renderOutput();
}