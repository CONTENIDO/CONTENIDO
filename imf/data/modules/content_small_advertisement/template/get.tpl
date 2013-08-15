
<h1>Anzeige hinzufügen</h1>
<form action="">
<div id="ctitle" name="ctitle">
    <div id="ctitlelabel" name="ctitlelabel">
    <label  id="ltitle" name="ltitle" for="title">Titel: *</label> 
    </div>
    <div id="ctitleinput" name="ctitleinput">
    <input name="title" id="title" type="text">
    </div>
</div>
<div id="clist" name="clist">    
      <div id="clistlabel" name="clistlabel"> 
    <label for="slist">Typ: </label> 
    </div>
    <div id="clistinput" name="clistinput"> 
    <select name="slist" id="slist">
        <option value="Angebot">Angebot</option>
        <option value="Gesuch">Gesuch</option>
    </select>
        </div>
</div>
<div id="ccatlist" name="ccatlist">        
    
    <div id="ccatlistlabel" name="ccatlistlabel">        
    <label  id="lcatlist" name="lcatlist"  for="catlist">Kategorie: </label> 
    </div>
    <div id="ccatlistselect" name="ccatlistselect">        
    <select name="catlist" id="catlist">
        {foreach $categories as $key => $item}
            <option value="{$item->getCategory()}">{$item->getName()}</option>
        {/foreach}
    </select>
    </div>
</div>    
<div id="cname" name="cname">
    <div id="cnamelabel" name="cnamelabel"> 
    <label id="lname" name="lname" for="name">Name: *</label> 
    </div>
     <div id="cnameinput" name="cnameinput"> 
    <input name="name" id="name" type="text">
    </div>
</div>
<div id="cemail" name="cemail">    
    <div id="cemaillabel" name="cemaillabel"> 
    <label  id="lemail" name="lemail"  for="email">Email: *</label> 
    </div>
    <div id="cemailinput" name="cemailinput"> 
    <input name="email" id="email" type="text">
    </div>
</div>
<div id="ctext" name="ctext">
   <div id="ctextlabel" name="ctextlabel">
    <label  id="ltext" name="ltext"  for="text">Text: *</label>
    </div>
    <textarea id="text" name="text"></textarea>
</div>
<div id="cerror" name="cerror">    
    {$error}
</div>
<div id="csubmit" name="csubmit">
    <input type="submit" name="buttonadvertisement" id="buttonadvertisement">
</div>    
</form>
<div id="cinfotext" name="cinfotext">
 {$infotext}
 </div>


{*  First name: <input type="text" name="fname"><br>
Last name: <input type="text" name="lname"><br>
<input type="submit" value="Submit">
</form>*} 