/* MeteorDdp - a client for DDP version pre1 */

var MeteorDdp = function(wsUri) {
  this.VERSIONS = ["pre1"];

  this.wsUri = wsUri;
  this.sock;
  this.defs = {};         // { deferred_id => deferred_object }
  this.subs = {};         // { pub_name => deferred_id }
  this.watchers = {};     // { coll_name => [cb1, cb2, ...] }
  this.collections = {};  // { coll_name => {docId => {doc}, docId => {doc}, ...} }
};

MeteorDdp.prototype._Ids = function() {
  var count = 0;
  return {
    next: function() {
      return ++count + '';
    }
  }
}();

MeteorDdp.prototype.connect = function() {
  var self = this;
  var conn = new $.Deferred();

  self.sock = new ReconnectingWebSocket(self.wsUri);

  self.sock.onopen = function() {
    self.send({
      msg: 'connect',
      version: self.VERSIONS[0],
      support: self.VERSIONS
    });
	RTemplate.loading = {};
	if(RTemplate.subscription != undefined && RTemplate.subscription != ""){
		ddp.subscribe(RTemplate.subscription);
	}
	
  };

  self.sock.onerror = function(err) {
    conn.reject(err);
  };

  self.sock.onmessage = function(msg) {
	//alert(msg.data);
    var data = JSON.parse(msg.data);
    switch (data.msg) {
      case 'connected':
        conn.resolve(data);
        break;
      case 'result':
        self._resolveCall(data);
        break;
      case 'ping':
        self._resolvePing(data);
        break;
      case 'updated':
        // TODO method call was acked
        break;
      case 'changed':
        self._changeDoc(data);
        break;
      case 'added':
        self._addDoc(data);
        break;
      case 'removed':
        self._removeDoc(data);
        break;
      case 'ready':
        self._resolveSubs(data);
        break;
      case 'nosub':
        self._resolveNoSub(data);
        break;
      case 'addedBefore':
        self._addDoc(data);
        break;
      case 'movedBefore':
        // TODO
        break;
    }
  };
  return conn.promise();
};

MeteorDdp.prototype._resolveNoSub = function(data) {
  if (data.error) {
    var error = data.error;
    this.defs[data.id].reject(error.reason || 'Subscription not found');
  } else {
    this.defs[data.id].resolve();
  }
};

MeteorDdp.prototype._resolvePing = function(data) {
	//alert("received ping");
  var self = this;
  //alert(JSON.stringify(data));
  self.send({
      msg: 'pong',
      id: data.id,
    });
  
};
MeteorDdp.prototype._resolveCall = function(data) {
  if (data.error) {
    this.defs[data.id].reject(data.error.reason);
  } else if (typeof data.result !== 'undefined') {
    this.defs[data.id].resolve(data.result);
  }
};

MeteorDdp.prototype._resolveSubs = function(data) {
  var subIds = data.subs;
  for (var i = 0; i < subIds.length; i++) {
    this.defs[subIds[i]].resolve();
  }
};

MeteorDdp.prototype._changeDoc = function(msg) {
	//alert(JSON.stringify(msg));
  if(msg.changed.template != undefined){
    //alert(RTemplate.data[msg.changed.template][msg.changed.tid]);
	RTemplate.data[msg.changed.template][msg.changed.tid] = msg.data;
    //alert(RTemplate.data[msg.changed.template][msg.changed.tid]);
	RTemplate.render2(URLParser.currenturl());
	return;
  }
  
  var collName = msg.collection;
  var id = msg.id;
  var fields = msg.fields;
  var cleared = msg.cleared;
  var coll = this.collections[collName];
  if (fields) {
    for (var k in fields) {
      coll[id][k] = fields[k];
    }
  } else if (cleared) {
    for (var i = 0; i < cleared.length; i++) {
      var fieldName = cleared[i];
      delete coll[id][fieldName];
    }
  }

  var changedDoc = coll[id];
  this._notifyWatchers(collName, changedDoc, id, msg.msg);
};

MeteorDdp.prototype._addDoc = function(msg) {
  var collName = msg.collection;
  var id = msg.id;
  if (!this.collections[collName]) {
    this.collections[collName] = {};
  }
  /* NOTE: Ordered docs will have a 'before' field containing the id of
   * the doc after it. If it is the last doc, it will be null.
   */
  this.collections[collName][id] = msg.fields;

  var changedDoc = this.collections[collName][id];
  this._notifyWatchers(collName, changedDoc, id, msg.msg);
};

MeteorDdp.prototype._removeDoc = function(msg) {
  var collName = msg.collection;
  var id = msg.id;
  var doc = this.collections[collName][id];

  var docCopy = JSON.parse(JSON.stringify(doc));
  delete this.collections[collName][id];
  this._notifyWatchers(collName, docCopy, id, msg.msg);
};

MeteorDdp.prototype._notifyWatchers = function(collName, changedDoc, docId, message) {
  changedDoc = JSON.parse(JSON.stringify(changedDoc)); // make a copy
  changedDoc._id = docId; // id might be useful to watchers, attach it.

  if (!this.watchers[collName]) {
    this.watchers[collName] = [];
  } else {
    for (var i = 0; i < this.watchers[collName].length; i++) {
      this.watchers[collName][i](changedDoc, message);
    }
  }
};

MeteorDdp.prototype._deferredSend = function(actionType, name, params) {
  var id = this._Ids.next();
  this.defs[id] = new $.Deferred();

  var args = params || [];

  var o = {
    msg: actionType,
    params: args,
    id: id
  };

  if (actionType === 'method') {
    o.method = name;
  } else if (actionType === 'sub') {
    o.name = name;
    this.subs[name] = id;
  }

  this.send(o);
  return this.defs[id].promise();
};

MeteorDdp.prototype.call = function(methodName, params) {
  return this._deferredSend('method', methodName, params);
};

MeteorDdp.prototype.subscribe = function(pubName, params) {
  return this._deferredSend('sub', pubName, params);
};

MeteorDdp.prototype.unsubscribe = function(pubName) {
  this.defs[id] = new $.Deferred();
  if (!this.subs[pubName]) {
    this.defs[id].reject(pubName + " was never subscribed");
  } else {
    var id = this.subs[pubName];
    var o = {
      msg: 'unsub',
      id: id
    };
    this.send(o);
  }
  return this.defs[id].promise();
};

MeteorDdp.prototype.watch = function(collectionName, cb) {
  if (!this.watchers[collectionName]) {
    this.watchers[collectionName] = [];
  }
  this.watchers[collectionName].push(cb);
};

MeteorDdp.prototype.getCollection = function(collectionName) {
  return this.collections[collectionName] || null;
}

MeteorDdp.prototype.getDocument = function(collectionName, docId) {
  return this.collections[collectionName][docId] || null;
}

MeteorDdp.prototype.send = function(msg) {
  this.sock.send(JSON.stringify(msg));
};

MeteorDdp.prototype.close = function() {
  this.sock.close();
};

(function (global, factory) {
  if (typeof exports === "object" && exports) {
    factory(exports); // CommonJS
  } else if (typeof define === "function" && define.amd) {
    define(['exports'], factory); // AMD
  } else {
    factory(global.RTemplate = {}); // <script>
  }
}(this, function (rtemplate) {
  rtemplate.templates = {};
  rtemplate.data = {};
  rtemplate.tdata = {};
  rtemplate.urldata = {};
  rtemplate.vars = {};
  rtemplate.loading = {};
  rtemplate.subscription = "";
  rtemplate.track = function(){
	  //$('a[data-ttarget]').each(function(val){
	$('a:internal').each(function(val){
		if($(this).data("track")) return;
		$(this).on("click", function(event){
			if(ddp.sock.readyState == "1"){
				event.preventDefault();
				if(!$(this).attr("href")) return;
				RTemplate.render2($(this).attr("href"));
			}else{
				//use standard href (websocket server is down, or there is some other problem)
			}
			/*
			atr = $(this).attr("href").split("/");
			templateid = atr[1];
			target = "";
			for(i=0;i<atr.length;i++){
				if(atr[i].indexOf(":") > -1){
					split = atr[i].split(":");
					templateid = split[1];
					target = split[0];
				}
			}
			if(!target) return;
			/**/
			//RTemplate.render(templateid,$(this).attr("href"),target);
		});
		$(this).data("track","1");
	});
  };
  rtemplate.getUsedTemplates = function(url){
	var ret = {};
	ret["m"] = "m";
	var done = false;
	while(!done){
		done = true;
		for(var key in ret){
			var template = ret[key];
			for(var item in rtemplate.getInnerTemplates(template)){
				if(item.substring(0,4) == "url:"){}else{
					var p = item.indexOf(":");
					var itemid = item;
					if(p > 0){
						itemid = item.substring(0,p);
					}
					item = URLParser.get(item,url);
					if(ret[itemid] == undefined){
						ret[itemid] = item;	
						done = false;
					}
				}
			}
		}
	}
	return ret;
	
	var arr = URLParser.parse(url);
	if(arr["tmpl"] != undefined){
		for(var key in arr["tmpl"]){
			var templateid=arr["tmpl"][key];
			if(rtemplate.templates[templateid] == undefined) return false;
			ret[key] = templateid;
			for(var item in rtemplate.getInnerTemplates(templateid)){
				if(item.substring(0,4) == "url:"){
				}else //if(data[templateid][tid][item] == undefined)
				{
					item = URLParser.get(item,url);
					ret[key] = templateid;
					
					/*
					var merge = {};
					var p = item.indexOf(":");
					itemid = item;
					if(p > 0){
						itemid = item.substring(0,p);
					}
					item = URLParser.get(item,url);
					if(itemid != item){
						merge = rtemplate.getUsedTemplates("/"+itemid+":"+item);
					}else{
						merge = rtemplate.getUsedTemplates("/"+item);
					}
					for(var key in merge){
						ret[key] = merge[key];
					}/**/
				}
			}
		}
	}
	return ret;
  };
  rtemplate.getInnerTemplates = function(templateid){
	ret = {};
	if(rtemplate.templates[templateid]==undefined) return ret;
	var n = rtemplate.templates[templateid].indexOf("{{{"); 
		while(n >= 0){
			n+=3;
			var nc = rtemplate.templates[templateid].indexOf("}}}",n); 
			var item = rtemplate.templates[templateid].substring(n,nc);	
			n = rtemplate.templates[templateid].indexOf("{{{",nc); 
			
			ret[item] = true;
		}
		return ret;
  };
  rtemplate.checkTemplate = function(templateid,url){
		//alert("checking: "+templateid);
			
		var done = true;
		var arr = URLParser.parse(url);
		var tid = URLParser.selectParameters(rtemplate.vars[templateid],arr);
		for(var item in rtemplate.getInnerTemplates(templateid)){
			//alert(item);
			if(item.substring(0,4) == "url:"){
				//newurl = URLParser.add(item.substring(4));
				//alert(url);
				//if(rtemplate.urldata[templateid] == undefined) rtemplate.urldata[templateid] = {};
				//if(rtemplate.urldata[templateid][tid] == undefined) rtemplate.urldata[templateid][tid] = {};
				//rtemplate.urldata[templateid][tid][item] = newurl;
			}else //if(rtemplate.data[templateid][tid][item] == undefined)
			{
				var p = item.indexOf(":");
				var itemid = item;
				if(p > 0){
					itemid = item.substring(0,p);
				}
				item = URLParser.get(item,url);
					
				if(rtemplate.templates[item] == undefined){
					done = false;
					rtemplate.loadTemplate(item,url);
				}
			}
		}
		return done;
  }
  rtemplate.loadTemplate = function(templateid,url){
		var done = true;
		templateid = URLParser.get(templateid,url);
		if(rtemplate.templates[templateid] == undefined){
			//alert("loading: "+templateid);
			done = false;
			
			if(rtemplate.loading["tmpl"] == undefined) rtemplate.loading["tmpl"] = {};
			if(rtemplate.loading["tmpl"][templateid] == undefined) rtemplate.loading["tmpl"][templateid] = {};
			
			//alert("requesting loading template "+templateid);
			
			var d = new Date();

			if(rtemplate.loading["tmpl"][templateid] > d.getTime() - 1000){return false;}
			
			
			rtemplate.loading["tmpl"][templateid] = d.getTime();
			ddp.call("/RTemplate/getTemplate",templateid).done(function(ret){
				//alert("done loading template "+templateid);
				//alert(ret);
				
				if(rtemplate.loading["tmpl"] != undefined && rtemplate.loading["tmpl"][templateid] != undefined) rtemplate.loading["tmpl"][templateid] = false;
				
				rtemplate.templates[templateid] = ret.template;
				rtemplate.vars[templateid] = ret.vars;
				rtemplate.checkTemplate(templateid,url);
				
				rtemplate.render2(url);
			}).fail(function(err) {
			
				rtemplate.render2(url);
				alert("template error 0x91859");
			});
		}else{
			if(!rtemplate.checkTemplate(templateid,url)) done = false;
		}
		return done;
  };
  rtemplate.loadData = function(templateid,url){
		var done = true;
		var tid = URLParser.selectParameters(rtemplate.vars[templateid],URLParser.parse(url));
		//alert("tid: "+templateid+":"+tid);
		//alert(JSON.stringify(rtemplate.vars[templateid]));
		if(rtemplate.data[templateid] == undefined || rtemplate.data[templateid][tid] == undefined){
			//alert("loading data: "+templateid);
			done = false;
			
			if(rtemplate.loading["data"] == undefined) rtemplate.loading["data"] = {};
			if(rtemplate.loading["data"][templateid] == undefined) rtemplate.loading["data"][templateid] = {};
			if(rtemplate.loading["data"][templateid][tid] == undefined) rtemplate.loading["data"][templateid][tid] = {};	
			
			var d = new Date();

			if(rtemplate.loading["data"][templateid][tid]  > d.getTime() - 1000){return false;}
			
			//alert("getting "+templateid+":"+tid);
			rtemplate.loading["data"][templateid][tid] = d.getTime();
			ddp.call("/RTemplate/getData",{"template":templateid,"tid":tid}).done(function(ret){
				//alert("got "+templateid+" "+tid);
				if(rtemplate.loading["data"] != undefined && rtemplate.loading["data"][templateid] != undefined && rtemplate.loading["data"][templateid][tid] != undefined) rtemplate.loading["data"][templateid][tid] = false;
				if(rtemplate.data[templateid] == undefined)rtemplate.data[templateid] = {};
				if(rtemplate.data[templateid][tid] == undefined)rtemplate.data[templateid][tid] = {};
				if(ret.data){
					rtemplate.data[templateid][tid] = ret.data;
				}else{
					rtemplate.data[templateid][tid]=  {};
				}
				
				rtemplate.render2(url);
			}).fail(function(err) {
			
				rtemplate.render2(url);
				alert("data error 0x91858");
			});
		}else{
			if(!rtemplate.checkTemplate(templateid,url)) done = false;
			//rtemplate.render2(url);
		}
		return done;
  };
  rtemplate.render2 = function (url){
   
   //lastcall = function(){}
	var arr = URLParser.parse(url);
	//alert(JSON.stringify(arr));
	var done = true;
	if(arr["tmpl"] != undefined){
		for(var key in arr["tmpl"]){
			var templateid = arr["tmpl"][key];
			if(!rtemplate.loadTemplate(templateid,url)){
				done = false;
				return;
			}
		}
	}
	
	
	if(done){
		parent.location.hash = url;
		
		//alert("generating page");
		var tmpl = rtemplate.getUsedTemplates(url);
		for(var key in tmpl){
			var templateid = tmpl[key];
			var tid = URLParser.selectParameters(rtemplate.vars[templateid],URLParser.parse(url));
			if(rtemplate.data[templateid] == undefined || rtemplate.data[templateid][tid] == undefined){
				//alert("starting loading data "+templateid+":"+tid);
				if(!rtemplate.loadData(templateid,url)){
					return;
				}
			}
		}
		//		alert("generating page");
		
		
			
	
			for(var key in tmpl){
				if(tmpl[key] == undefined) continue;
				var templateid = tmpl[key];
				var tid = URLParser.selectParameters(rtemplate.vars[templateid],URLParser.parse(url));
			
				var d = rtemplate.data[templateid][tid];
				
				if(rtemplate.tdata[templateid] != undefined && rtemplate.tdata[templateid][tid] != undefined){
					for (var attrname in rtemplate.tdata[templateid][tid]) { 
						d[attrname] = '<span id="T_'+attrname+'">'+rtemplate.tdata[templateid][tid][attrname]+'</span>'; 
					}
				}
				//if(rtemplate.urldata[templateid] != undefined) alert(JSON.stringify(rtemplate.urldata[templateid][tid]));
				for(var item in rtemplate.getInnerTemplates(templateid)){
					if(item.substring(0,4) == "url:"){
						//alert(item);
						d[item] = URLParser.add(item.substring(4));//rtemplate.urldata[templateid][tid][item];
						//alert(d[item]);
					}else{
						var itemid = item;
						var p=item.indexOf(":");
						if(p>0){
							itemid = item.substring(0,p);
						}
						d[item] = '<span id="T_'+itemid+'"></span>'; 
					}
				}
				/*
				if(urldata[templateid] != undefined && urldata[templateid][tid] != undefined){
					for (var attrname in urldata[templateid][tid]) { 
						d[attrname] = urldata[templateid][tid][attrname]; 
					}
				}/**/
				//alert(rtemplate.templates[templateid]);
				//alert("key:"+key);
				//alert(JSON.stringify(d));
				//alert(rtemplate.templates[templateid].substring(0,15));
				if(rtemplate.templates[templateid].substring(0,15)=='<!DOCTYPE html>'){
					//$(document).html(Mustache.render(rtemplate.templates[templateid], d));
				}else{
					$('#T_'+key).html(Mustache.render(rtemplate.templates[templateid], d));
				}
				//alert("k");
			}
		
		if(rtemplate.subscription!="") ddp.unsubscribe(rtemplate.subscription);
		rtemplate.subscription = parent.location.hash.substring(1);
		ddp.subscribe(rtemplate.subscription);
		RTemplate.track();
		return true;
	}
	return false;
  }
  /*
  rtemplate.render = function (templateid, tid, target) {
	alert("Render: "+templateid+","+tid+","+(typeof target));
	
	if(tid == undefined || !tid) tid = templateid;
	var templateidarr = templateid.split("/");
	templateid = templateidarr[templateidarr.length-1];
	
	
	if(data[tid] == undefined){
		ddp.call("/RTemplate/getData",tid).done(function(ret){			
			data[tid] = ret.data;
			if(data[tid] != undefined){
				RTemplate.render(templateid,tid,target);
			}
		});
	}else if(templates[templateid] == undefined){
		ddp.call("/RTemplate/getTemplate",templateid).done(function(ret){
			templates[templateid] = ret.template;
			
			var n = templates[templateid].indexOf("{{{"); 
			while(n >= 0){
				n+=3;
				var nc = templates[templateid].indexOf("}}}",n); 
				item = templates[templateid].substring(n,nc);	
				n = templates[templateid].indexOf("{{{",nc); 
				//alert(item);
				if(item.substring(0,4) == "url:"){
					url = URLParser.add(item.substring(4));
					//alert(url);
					if(urldata[templateid] == undefined) urldata[templateid] = {};
					if(urldata[templateid][tid] == undefined) urldata[templateid][tid] = {};
					urldata[templateid][tid][item] = url;
				
				}else if(data[tid][item] == undefined){
					if(templates[item] == undefined){
					
						tidarr = tid.split("/");
						newtid = "";
						for(i = 0;i<tidarr.length - 1;i++){
							if(!tidarr[i]) continue;
							newtid += "/"+tidarr[i];
						}
						newtid += "/"+item+"/"+tidarr[tidarr.length - 1];

						RTemplate.render(templateid+"/"+item,newtid,function(data){
							if(tdata[templateid] == undefined) tdata[templateid] = {};
							if(tdata[templateid][tid] == undefined) tdata[templateid][tid] = {};
							tdata[templateid][tid][item] = data;
							RTemplate.render(templateid,tid,target);
						});
					}
				}
			}
			
			if(templates[templateid] != undefined){
				RTemplate.render(templateid,tid,target);
			}
		});
	}else {		
		if(typeof target == "string"){
			
			var d = data[tid];
			
			if(tdata[templateid] != undefined && tdata[templateid][tid] != undefined){
			//					$data[$item] = '<span id="T_'.$item.'">'.$itemcl->get().'</span>';

				for (var attrname in tdata[templateid][tid]) { 
					d[attrname] = '<span id="T_'+attrname+'">'+tdata[templateid][tid][attrname]+'</span>'; 
					alert(JSON.stringify(tdata[templateid][tid][attrname]));
				}
				
			}

			if(urldata[templateid] != undefined && urldata[templateid][tid] != undefined){
				for (var attrname in urldata[templateid][tid]) { d[attrname] = urldata[templateid][tid][attrname]; }
			}

			
			$('#T_'+target).html(Mustache.render(templates[templateid], d));
			RTemplate.track();
			parent.location.hash = tid;
			return true;
		}else{
			target(Mustache.render(templates[templateid], data[tid]));
		}
	}
  };/**/
}));


$(document).ready(function(){

ddp = new MeteorDdp('ws://www.jobler.cz:3001/websocket');

ddp.connect().done(function() {

});

	RTemplate.track();
	
	if(parent.location.hash != null){
		url = parent.location.hash.substring(1);
		RTemplate.render2(url);
		/*
		atr = url.split("/");
		
		templateid = atr[1];
		target = "";
		for(i=0;i<atr.length;i++){
			if(atr[i].indexOf(":") > -1){
				split = atr[i].split(":");
				templateid = split[1];
				target = split[0];
			}
		}
		if(target){
			RTemplate.render(templateid,url,target);
		}/**/
	}
	
});



//http://stackoverflow.com/questions/1227631/using-jquery-to-check-if-a-link-is-internal-or-external
    $.expr[':'].external = function (a) {
        var PATTERN_FOR_EXTERNAL_URLS = /^(\w+:)?\/\//;
        var href = $(a).attr('href');
        return href !== undefined && href.search(PATTERN_FOR_EXTERNAL_URLS) !== -1;
    };

    $.expr[':'].internal = function (a) {
        return $(a).attr('href') !== undefined && !$.expr[':'].external(a);
    };

	
	
(function (global, factory) {
  if (typeof exports === "object" && exports) {
    factory(exports); // CommonJS
  } else if (typeof define === "function" && define.amd) {
    define(['exports'], factory); // AMD
  } else {
    factory(global.URLParser = {}); // <script>
  }
}(this, function (urlparser) {

	urlparser.get = function(templateid,url){
		//alert(templateid);
		//alert(url);
		//alert(templateid);
		if(url == "") url = urlparser.currenturl();
		arr = urlparser.parse(url);
		
		var itembase = templateid;
		var replace = templateid;
		var p = -1;
		if((p = templateid.indexOf(":")) > 0){
			itembase = templateid.substring(0,p);
			replace = templateid.substr(p+1);
		}
		if(arr["tmpl"] != undefined && arr["tmpl"][itembase] != undefined){
			//alert(arr["tmpl"][itembase]);
			return arr["tmpl"][itembase];
		}
		//alert(replace);
		return replace;
	}

	urlparser.currenturl = function(){
		
		if(parent.location.hash == undefined){
			if(window.location.pathname.substring(0,3) != "/m/"){
				return "/m/"+window.location.pathname;
			}
			return window.location.pathname;
		}else{
			if(parent.location.hash.substring(1).substring(0,3) != "/m/"){
				return "/m/"+parent.location.hash.substring(1);
			}
			return parent.location.hash.substring(1);
		}
	};
	urlparser.parse = function (url) {
		if(url == undefined) alert("urlparser.parse:"+JSON.stringify(url));
		var arr = url.split("/");
		var ret = {"var":{},"tmpl":{}};
		
		for(var i in arr){
			if(i == undefined) continue;
			if(!arr[i] || arr[i]=="") continue;
			var p;
			if((p = arr[i].indexOf("=")) >= 0){
				ret["var"][arr[i].substring(0,p)] = arr[i].substring(p+1);
			}else{
				//alert(i);
				//alert(arr[i]);
				//alert(arr[i].indexOf(":"));
				if((p = arr[i].indexOf(":")) >= 0){
					ret["tmpl"][arr[i].substring(0,p)] = arr[i].substring(p+1);
				}else{
					ret["tmpl"][arr[i]] = arr[i];
				}
			}
		}
		return ret;
	};
	urlparser.merge = function (arr) {
		ret = "";
		if(arr["tmpl"] != undefined){
			for (var key in arr["tmpl"]) {
				if(arr["tmpl"][key] == key){
					ret += "/"+key;
				}else{
					ret += "/"+key+":"+arr["tmpl"][key];
				}
			}
		}
		
		if(arr["var"] != undefined){
			for (var key in arr["var"]) {
				ret += "/"+key+"="+arr["var"][key];
			}
		}
		return ret;
	};
	urlparser.add = function (param) {
		arr = URLParser.parse(URLParser.currenturl());
		
		parama = URLParser.parse(param);

		for(var vartmpl in parama){
			for(var k in parama[vartmpl]){
				arr[vartmpl][k] = parama[vartmpl][k];
			}
		}
		return URLParser.merge(arr);
	};
	
	urlparser.selectParameters = function (paramarr,urlarr) {
		var ret = "";
		for(var k in paramarr){
			var key = paramarr[k];
			if(urlarr["var"][key] != undefined){
				ret += "/"+key+"="+urlarr["var"][key];
			}
		}
		return ret;
	};
}));