?>

<table>
  <tr>
    <td><?php echo mi18n("URL");?>*</td>
    <td><input type="text" name="CMS_VAR[0]" value="<?php echo "CMS_VALUE[0]"; ?>"></td>
  </tr>
  <tr>
    <td>
        <?php echo mi18n("Plugin");?>
    </td>
    <td>
   
    <input type="radio" name="CMS_VAR[1]" value="like_button" <?php  $value = "CMS_VALUE[1]"; if($value == "like_button" ) echo 'checked="checked"'; ?>">
     <?php echo mi18n("like button");?>
    <br/>
    
   
    <input type="radio" name="CMS_VAR[1]" value="like_box"  <?php $value =  "CMS_VALUE[1]"; if($value =="like_box" || $value !="like_button" && $value!="like_box") echo 'checked="checked"'; ?>">
    <?php echo mi18n("like box");?>
    </td>
  </tr>
  
  <tr>
    <td>
        <?php echo mi18n("Layout");?>
    </td>
    <td>
   
    <input type="radio" name="CMS_VAR[2]" value="standard" <?php  $value = "CMS_VALUE[2]"; if($value == "standard" || $value !="button_count" && $value !="box_count" ) echo 'checked="checked"'; ?>">
     <?php echo mi18n("standard");?>
    <br/>
    
    
    <input type="radio" name="CMS_VAR[2]" value="button_count"  <?php $value =  "CMS_VALUE[2]"; if($value =="button_count") echo 'checked="checked"'; ?>">
    <?php echo mi18n("button_count");?>
    <br/>
    
    <input type="radio" name="CMS_VAR[2]" value="box_count"  <?php $value =  "CMS_VALUE[2]"; if($value == "box_count") echo 'checked="checked"'; ?>">
    <?php echo mi18n("box_count");?>
    
    </td>
    
   </tr>
   
    
  <tr>
    <td><?php echo mi18n("Show faces");?></td>
    <td>
     <input type="checkbox" name="CMS_VAR[3]" value="true"   <?php $value =  "CMS_VALUE[3]"; if($value) echo 'checked="checked"'; ?>">
    </td>
  </tr>
   
  <tr>
    <td><?php echo mi18n("Breite");?></td>
    <td><input type="text" name="CMS_VAR[4]" value="<?php echo "CMS_VALUE[4]"; ?>"></td>
  </tr>
  <tr>
    <td><?php echo mi18n("H&ouml;he");?></td>
    <td><input type="text" name="CMS_VAR[6]" value="<?php echo "CMS_VALUE[6]"; ?>"></td>
  </tr>
  <tr>
    <td><?php echo mi18n("LOCALE");?>*</td>
     <td><input type="text" name="CMS_VAR[5]" value="<?php echo "CMS_VALUE[5]"; ?>"></td>
  </tr>
  
 </table>


<?php
