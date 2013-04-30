var jvc={
	'afterAjaxResponseJS':'',
	'beforeAjaxSubmit':''
};

jvc.init=function(){
	$(window).hashchange( function(){
		var data = {};
		var urlParts = new String(location.hash).split(/--/);
		urlParts[0] = new String(urlParts[0]).replace('#!','').replace('-','/');
		if(urlParts.length>1){
			var urlData = new String(urlParts[1]).split(/|/);
			for(var i=0;i<urlData.length;i+=2){
				data[urlData[i]] = urlData[i+1];
			}
		}
		if(urlParts[0]+'' != 'undefined' && urlParts[0]+''!='')
			jvc.requestData(urlParts[0],data);
	})
	$(window).hashchange();
}

jvc.formatFormData=function(formObj){
	var data = formObj.serializeArray();
	var toReturn = {};
	for(i=0;i<data.length;i++){
		toReturn[data[i].name] = data[i].value;
	}
	return toReturn;
}

jvc.submitForm=function(formObj){
	var formObj = $(formObj);
	var data    = jvc.formatFormData(formObj);
	
	if(jvc['beforeAjaxSubmit'] != ''){	
		var result  = jvc['beforeAjaxSubmit'](data,formObj.attr('name'));
		if(!result[0]){
			jvc.alertHash(result[1]);
		}else{
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
	eval(js + jvc.afterAjaxResponseJS);
};

