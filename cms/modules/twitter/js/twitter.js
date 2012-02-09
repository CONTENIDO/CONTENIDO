/**
    parse twitter date
**/
function parseDate(str) {
    var v=str.split(' ');
    return new Date(Date.parse(v[1]+" "+v[2]+", "+v[5]+" "+v[3]+" UTC"));
}


/**
 * hilgiting the links
 */
function setLinks (text) {
	
	//extract links
	 text= text.replace(/(http:\/\/\S*)/g, '<a  target="_blank" href="$1">$1<\/a>');
	 //link to users
	 text = text.replace(/[@]+[A-Za-z0-9-_]+/g, function(s){ 
		  var link = '<a href="http://twitter.com/' + s.replace('@','') + '">' + s + '</a>';
		  return link;
	 }); 
	 
	 text = text.replace(/[#]+[A-Za-z0-9-_]+/g, function(s){ // hashtags
		  var link = '<a href="http://twitter.com/search?q=' + s.replace('#','%23') + '">' + s + '</a>';
		 return link;
		  }); 
	 
	 return text;
	 
	
}

/**
 * Funktion sprintf for javascript 
 */
String.prototype.sprintf = function() {
	if (arguments.length < 1) {
		return this;
	}

	var data = this; // arguments[ 0 ];

	for ( var k = 0; k < arguments.length; ++k) {

		switch (typeof (arguments[k])) {
		case 'string':
			data = data.replace(/%s/, arguments[k]);
			break;
		case 'number':
			data = data.replace(/%d/, arguments[k]);
			break;
		case 'boolean':
			data = data.replace(/%b/, arguments[k] ? 'true' : 'false');
			break;
		default:
			// / function | object | undefined
			break;
		}
	}
	return (data);
}



function writeTime(twitt_time) {
	
	//different time
	var dt = ((new Date()).getTime() - twitt_time.getTime()) / 1000;

	//text 
	var text = ''
	 if (dt < 60) { text = translations_twitter ['less_minutes']; }
	 else if (dt < 120) { text = translations_twitter ['one_minute']; }
	 else if (dt < (45 * 60)) { text = translations_twitter ['more_minutes'].sprintf(Math.round(dt / 60)); }
	 else if (dt < (90 * 60)) { text = translations_twitter ['one_hours'] ;}
	 else if (dt < (24 * 60 * 60)) { text = translations_twitter ['more_hours'].sprintf(Math.round(dt / 3600)); }
	 else if (dt < (48 * 60 * 60)) { text = translations_twitter ['one_day']; }
	 else { text = translations_twitter ['more_days'].sprintf(Math.round(dt / 86400)); } 
	
	return text;
}

/**
 * function callTwitterSmall
 */
function  callTwitterSmall(tweeter_json) {
	
	var twitterHead = document.getElementById('twitter_head_small');
	
	var divImage = document.createElement('div');
    divImage.setAttribute ('class','image');
    
	 var imgTag = document.createElement('img');
	 imgTag.setAttribute('src', tweeter_json[0].user.profile_image_url);
	 imgTag.setAttribute('alt', 'profilBild');
	 divImage.appendChild(imgTag); 
	 twitterHead.appendChild(divImage);
	 
	 var aDisplayName = document.createElement('a');
	    aDisplayName.setAttribute('class', 'display_name');
	    aDisplayName.setAttribute('href', 'http://twitter.com/'+tweeter_json[0].user.screen_name);
	    aDisplayName.innerHTML = tweeter_json[0].user.screen_name;
	    
	    var userName = document.createElement('p');
	    userName.setAttribute('class', 'user_name');
	    userName.innerHTML = tweeter_json[0].user.name;
	  
	 //twitterHead 
	 twitterHead.appendChild(aDisplayName);
	 twitterHead.appendChild(userName);
	
	anzahlObjekte = tweeter_json.length;
	 var twitter = document.getElementById('twitter_masages');//.innerHTML= "<img src=\""+bild+"\">";
	 for(var i=0; i<anzahlObjekte; i++) {
	    
		var divMessage = document.createElement('div');
	    divMessage.setAttribute ('class','twitter_row clearfix');
	    
	    
	  
	    
	    var pNachrichtTag = document.createElement('p');
	    pNachrichtTag.setAttribute('class', 'message');
	    pNachrichtTag.innerHTML = setLinks( tweeter_json[i].text);
	    
	    
	    
	    var pTime = document.createElement('p');
	    pTime.setAttribute('class', 'message_date');
	    var timeObjekt = parseDate(tweeter_json[i].created_at);//new Date(Date.parse(tweeter_json[i].created_at));
	    
	    var hours = timeObjekt.getHours();
	    if(hours  < 10) 
	        hours  = '0'+ hours ;
	    
	    var minutes =   timeObjekt.getMinutes();
	    if(minutes <10)
	        minutes = '0' + minutes;

	    var seconds = timeObjekt. getSeconds();
	    if(seconds < 10)
	        seconds = '0'+seconds;
	    
	    var date = timeObjekt.getDate();
	    if(date < 10 )
	        date = '0' +date;
	        
	    var month = timeObjekt.getMonth();
	    if(month < 10)
	        month = '0'+month;
	    var year = timeObjekt.getYear();
	    if(year < 999)
	        year += 1900;
	        
	    
	    
	    //display the time
	    pTime.innerHTML = writeTime(timeObjekt);//" " + date+'.'+month+'.'+year+'  '+ hours +':'+ minutes+':'+ seconds ;
	  
	    divMessage.appendChild(pNachrichtTag);
	    
	    
	    divMessage.appendChild(pTime);
	    
	    
	    twitter.appendChild(divMessage);
	    //var benutzerBild = tweeter_json[i].user.profile_image_url;
	 }
	
}
  
function callTwitter(tweeter_json) {
 
 anzahlObjekte = tweeter_json.length;
 var twitter = document.getElementById('twitter');//.innerHTML= "<img src=\""+bild+"\">";
 for(var i=0; i<anzahlObjekte; i++) {
	 var allMessage = document.getElementById('twitter_masages');
	 
    var divMessage = document.createElement('div');
    divMessage.setAttribute ('class','twitter_row clearfix');
    
    
    var divImage = document.createElement('div');
    divImage.setAttribute ('class','image');
    
    
    var imgTag = document.createElement('img');
    imgTag.setAttribute('src', tweeter_json[i].user.profile_image_url);
    imgTag.setAttribute('alt', 'profilBild');
    divImage.appendChild(imgTag);
    
    var pNachrichtTag = document.createElement('p');
    pNachrichtTag.setAttribute('class', 'message');
    pNachrichtTag.innerHTML = setLinks(tweeter_json[i].text);
    
    var aDisplayName = document.createElement('a');
    aDisplayName.setAttribute('class', 'display_name');
    aDisplayName.setAttribute('href', 'http://twitter.com/'+tweeter_json[i].user.screen_name);
    aDisplayName.innerHTML = tweeter_json[i].user.screen_name;
    
    var userName = document.createElement('p');
    userName.setAttribute('class', 'user_name');
    userName.innerHTML = tweeter_json[i].user.name;
    
    
    var pTime = document.createElement('p');
    pTime.setAttribute('class', 'message_date');
    var timeObjekt = parseDate(tweeter_json[i].created_at);//new Date(Date.parse(tweeter_json[i].created_at));
    
    var hours = timeObjekt.getHours();
    if(hours  < 10) 
        hours  = '0'+ hours ;
    
    var minutes =   timeObjekt.getMinutes();
    if(minutes <10)
        minutes = '0' + minutes;

    var seconds = timeObjekt. getSeconds();
    if(seconds < 10)
        seconds = '0'+seconds;
    
    var date = timeObjekt.getDate();
    if(date < 10 )
        date = '0' +date;
        
    var month = timeObjekt.getMonth();
    if(month < 10)
        month = '0'+month;
    var year = timeObjekt.getYear();
    if(year < 999)
        year += 1900;
    //display the time
    pTime.innerHTML = writeTime(timeObjekt);
    
    
    divMessage.appendChild(divImage);
    divMessage.appendChild(aDisplayName);
    divMessage.appendChild(userName);
    divMessage.appendChild(pNachrichtTag);
    divMessage.appendChild(pTime);
    
    
    allMessage.appendChild(divMessage);
    //var benutzerBild = tweeter_json[i].user.profile_image_url;
    
 }
 }
 
 
 