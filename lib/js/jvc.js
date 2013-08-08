var jvc={
	'afterAjaxResponseJS':'',
	'beforeAjaxSubmit':'',
	'formErrors':'',
	'clearFormErrors':'',
	'beforeNavigate':''
};

jvc.init=function(){
	$(window).hashchange( function(){
		var data = {};
		var urlParts = new String(location.hash).split(/--/);
		urlParts[0] = new String(urlParts[0]).replace('#!','').replace('-','/');
		if(urlParts.length>1){
			var urlData = new String(urlParts[1]).split(/\|/);
			for(var i=0;i<urlData.length;i+=2){
				data[urlData[i]] = urlData[i+1];
			}
		}
		if(urlParts[0]+'' != 'undefined' && urlParts[0]+''!=''){
			if(jvc['beforeNavigate'] != '')
				jvc['beforeNavigate']();
			jvc.requestData(urlParts[0],data);
		}
	})
	$(window).hashchange();
}

jvc.formatFormData=function(formObj,event){
	var toReturn={};
	for(var i=0;i<formObj.elements.length;i++){
		var elem  = formObj.elements[i];
		var tag   = new String(elem.tagName).toLowerCase();
		var name  = elem.getAttribute('name');;
		var id    = elem.getAttribute('id');
		var value = elem.value;
		
		
		if(name == null || typeof(name) == 'undefined' || name == ''){
			name = id;
		}
		console.log('getting form data for '+tag+' / '+name+ ' / '+value);
		switch(tag){
			case 'select':
				toReturn[name] = elem.options[elem.selectedIndex].value;
				break;
			case 'textarea':
				toReturn[name] = value;
				break;
			case 'input':
				var type = elem.getAttribute('type');
				if(type == 'radio'){
					console.log(name+ ' is a radio: '+elem.checked);
					if(elem.checked){
						toReturn[name] = value;
					}else if(toReturn[name]+'' == 'undefined'){
						toReturn[name] = '';
					}
				}else if(type == 'checkbox'){
					toReturn[name] = (elem.checked)?1:0;
				}else{
					toReturn[name] = value;
				}
				break;
		}
	}
	
	if(typeof(event) == 'object')
		var target = event.explicitOriginalTarget || event.relatedTarget
	else if(typeof(document.activeElement) == 'object')
		var target = document.activeElement;
	else
	   var target = {value:''};

	if(target.value != ''){
		toReturn['after_submit'] = target.value;
	}
	
	return toReturn;
}

jvc.submitForm=function(form){
	var formObj = $(form);
	var data    = jvc.formatFormData(form);
	
	if(jvc['beforeAjaxSubmit'] != ''){	
		jvc['clearFormErrors'](form);
		var result  = jvc['beforeAjaxSubmit'](data,formObj.attr('name'));
		if(!result[0]){
			jvc['showFormErrors'](form,result[1]);
		}else{
			jvc['clearFormErrors'](form);
			data['ajax'] = 'yes';
			jvc.requestData(formObj.attr('action'),data);
		}
	}else{
		data['ajax'] = 'yes';
		jvc.requestData(formObj.attr('action'),data);
	}
	return false;
}

jvc.requestData=function(url,data){
	if(typeof(data) == 'undefined')	data={};
	data['ajax'] = 'yes';
	jQuery.ajax(url,{
		'data':data,
		'type':'POST',
		'dataType':'json',
		'success':jvc.handleResponse,
		'error':function(jqXHR,textStatus,errorThrown){
			alert(textStatus+' / '+errorThrown);
		}
	});
}

jvc.alertHash=function(myHash,depth,noRecurse){
	depth = parseInt(depth);
	if(isNaN(depth))
		depth = 0;
	var s='';
	var doDepth=function(numLevels){
		var r = '';
		for (var i = 0; i < numLevels; i++)
			r += '\t';
		return r;
	}
	for(var key in myHash){
		if(typeof(myHash[key]) == 'object' && noRecurse)
			s+=doDepth(depth)+key+':{object}\n';
		else if(typeof(myHash[key]) == 'object')
			s+=doDepth(depth)+key+':{\n'+jvc.alertHash(myHash[key],(depth+1))+doDepth(depth)+'}\n';
		else
			s+=doDepth(depth)+key+':'+myHash[key]+'\n';
	}
	if(depth == 0)
		alert(s);
	else
		return s;
}

jvc.handleResponse=function(json,textStatus){
	var js = '';
	for(var position in json){
		switch(position){
			case 'author':
			case 'description':
			case 'keywords':
				$('meta[name='+position+']').attr('content',json[position]);
				break;
			case 'js':
				js = json[position];
				break;
			case 'title':
				$('title').html(json[position]);
				break;
			case 'replace':
				for(var id in json[position]){
					$('#'+id).html(new String(json[position][id]));
				}
				break;
			case 'append':
				for(var id in json[position]){
					var obj = $('#'+id);
					obj.html(obj.html() + new String(json[position][id]));
				}
				break;
			case 'prepend':
				for(var id in json[position]){
					var obj = $('#'+id);
					obj.html(new String(json[position][id]) + obj.html());
				}
				break;
		}
	}
	$('html, body').animate({scrollTop: 0}, 200);
	eval(js + jvc.afterAjaxResponseJS);
};

