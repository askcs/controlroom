var CapeClient = function(messageHandler, messageFromAgentHandler) {

	this.messageHandler = messageHandler;
	this.messageFromAgentHandler = messageFromAgentHandler;
	this.lastCalledMethod;
	
    this.server = "http://xmpp.ask-cs.com:5280/http-bind";

    this.requestid=0;
    this.stateAgentUrl = null;
    this.requests = new Array();
    this.connection = new Strophe.Connection(this.server);
	
    var sid = localStorage['capeclient_sid'];
    var rid = localStorage['capeclient_rid'];
    var jid = localStorage['capeclient_jid'];
    this.userid = localStorage['capeclient_uid'];

    var thiz=this;
	/*
    if(sid!=null) {
        console.log('reconnecting');
        this.connection.attach(jid, sid, rid+1, function(status) {
            if(status==Strophe.Status.ATTACHED) {
                console.log('reconnected');
                var iq = $iq({to: Strophe.getDomainFromJid(jid), type: "get"})
                    .c('query', {xmlns:'http://jabber.org/protocol/disco#info'});
                thiz.connected();
            } else if (status == Strophe.Status.CONNFAIL) {
                console.log('Strophe failed to connect.');
            } else if (status == Strophe.Status.DISCONNECTING) {
                console.log('Strophe is disconnecting.');
            } else if (status == Strophe.Status.DISCONNECTED) {
                console.log('Strophe is disconnected.');
                thiz.disconnected();
            } else if (status == Strophe.Status.CONNECTED) {
                console.log('Strophe is connected.');

                thiz.connected();
            } else if (status == Strophe.Status.CONNFAIL) {
                console.log('Strophe failed to connect.');
            }
        });
    }
	*/
}

var JSONRPC = function(connection) {
    this.connection = connection;
}

JSONRPC.prototype.send = function(reqid, url, method, params) {

    var request = {};
    request.id=reqid;
    request.method= method;
    request.params = params;
    var json = JSON.stringify(request);

    var reply = $msg({to: url, from: this.username, type: 'chat'})
            .c("body")
            .t( json );
    this.connection.send(reply.tree());
}

CapeClient.prototype.login = function(username, password) {
    this.userid = username;
    this.username = username + '@xmpp.ask-cs.com/web'; // NOTE: Changed web to Smack to match with the alarm sending/receiving in the app/agents

    var thiz = this;
    this.connection.connect(this.username,
                            password,
                            onConnect);

    function onConnect(status) {

        if (status == Strophe.Status.CONNECTING) {
            console.log('Strophe is connecting (' + thiz.username + ').');
        } else if (status == Strophe.Status.CONNFAIL) {
            console.log('Strophe failed to connect.');
        } else if (status == Strophe.Status.DISCONNECTING) {
            console.log('Strophe is disconnecting.');
            thiz.disconnected();
        } else if (status == Strophe.Status.CONNECTED) {
            console.log('Strophe is connected.');

            thiz.connected();
        } else if (status == Strophe.Status.CONNFAIL) {
            console.log('Strophe failed to connect.');
        }
    }

    /**/
}

CapeClient.prototype.connected = function() {
    localStorage['capeclient_sid'] = this.connection.sid;
    localStorage['capeclient_rid'] = this.connection.rid;
    localStorage['capeclient_jid'] = this.connection.jid;
    localStorage['capeclient_uid'] = this.userid;

	var thiz = this;
	var msgHandler = function onMessage(msg) {
        
		//console.log("Msg data"); console.log(msg);
		
		var to = msg.getAttribute('to');
        var from = msg.getAttribute('from');
        var type = msg.getAttribute('type');
        var elems = msg.getElementsByTagName('body');
        var errors = msg.getElementsByTagName('error');

        if (elems.length > 0) {
            var body = elems[0];
            var json = JSON.parse(Strophe.getText(body));

             if(json.method != null) { // Pandion
				//console.log("Received new call: ",json);
				thiz.messageHandler(json.method, json.params);
                
            } else { // AlarmAgent
				//console.log("Received new call: ",json);
				thiz.messageFromAgentHandler(json, thiz.lastCalledMethod);
			}
        }
		
        if (errors.length > 0) {
            var error = errors[0];
            console.log(error);
            console.log('Received error from ' + from + ': ' +
                Strophe.getText(error));
        }

        // we must return true to keep the handler alive.
        // returning false would remove it after it finishes.
        return true;
    }
	
	try{
		console.log("Set handler");
		//this.connection.addHandler(msgHandler, null, null, 'chat');
		this.connection.addHandler(msgHandler);
	} catch(e){
		console.log(e);
	}
	
    this.connection.send($pres().tree());

    
    //this.findDatasource(this.userid, "state", function(result) {
    //    thiz.stateAgentUrl = "";//result.result[0].agentUrl.replace('xmpp:','');
    //});
	
}

CapeClient.prototype.disconnect = function() {
    this.connection.disconnect();
}

CapeClient.prototype.disconnected = function() {
    localStorage.removeItem('capeclient_sid');
    localStorage.removeItem('capeclient_rid');
    localStorage.removeItem('capeclient_jid');
}

CapeClient.prototype.findDatasource = function(agentid, type, callback) {
    var merlin = "merlin@xmpp.ask-cs.com/merlin";
    var params = {};
    params.userId = agentid;
    params.dataType = type;

    this.call(merlin, "find", params, callback);
}

CapeClient.prototype.isConnected = function() {
    return this.connection.connected;
}

CapeClient.prototype.call = function(agentUrl, method, params, callback) {
    this.requestid++;
    var rpc = new JSONRPC(this.connection);
	
	this.lastCalledMethod = method;

    rpc.send(this.requestid, agentUrl, method, params);
    this.requests[this.requestid] = callback;
}
