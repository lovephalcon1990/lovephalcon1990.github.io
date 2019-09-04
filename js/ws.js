layui.define(['jquery'], function(exports){
    var $=layui.jquery,
    ws= {
        config:{
            wsServer : '',
            setIntervalInt:'',
            localData:'',
            websocket:{},
            counter: 0,
            tryCnt:5,
            heartTime:10000,
            host:'',
            uinfo:{},
        }
        ,_init:function(data) {
            
            ws.config.localData = data;
            ws.config.host = data.web;
            ws.config.uinfo = data.uinfo;
            ws.config.wsServer = ws.config.host ;
            ws.config.websocket = new WebSocket(ws.config.wsServer);

            ws.config.websocket.onopen = function (evt) {
                console.log("Connected to WebSocket server.");
                ws.config.counter = 0;
                ws.api.heart(ws.config.heartTime);
                var ob = { id: ws.config.uinfo.id, sign: ws.config.uinfo.sign, token: ws.config.uinfo.token},
                    buff = ws.api.write(ob, 101);
                ws.config.websocket.send(buff);
            };

            ws.config.websocket.onclose = function (evt) {
                console.log("Disconnected");
            };

            ws.config.websocket.onmessage = function (evt) {
                var json = JSON.parse(evt.data);
                console.log(json);
                ws.api.read(json);
            };
        },
        api:{
            write : function (ob, cmd=100) {
                var o={'data': ob, 'cmd': cmd},
                    buff = JSON.stringify(o);
                return buff;
                // return buff + "\r\n";
            }
            ,read : function (json) {
                if(json.cmd === undefined){
                    return false;
                }
                var m= 'do_'+json.cmd ;
                ws.api[m] && ws.api[m].call(this, json.data);
            }
            ,heart : function (s) {
                ws.api.clearIvl(0);
                ws.config.setIntervalInt = setInterval(function () {
                    if(ws.config.counter >= ws.config.tryCnt){
                        console.log(ws.config.counter);
                        layer.open({
                            title:'断线重连!'
                            ,content:'断线重连'+ws.config.tryCnt + '次失败， 确定后刷新页面'
                            ,btn:['确认']
                            ,yes:function(index, layero){
                                layer.close(index);
                                window.location.reload();
                            }
                            ,cancel:function(){
                                layer.close(index);
                                window.location.reload();
                            },
                            success: function(layero, index){
                                ws.api.clearIvl(0);
                            }
                        });
                    }
                    if(ws.config.websocket.readyState != 1){
                        ws._init(ws.config.localData);
                        ++ws.config.counter;
                    }
                    if(ws.config.websocket.readyState == 1){
                        ws.config.websocket.send(ws.api['write']('ping'));
                    }
                }, s);
            }
            ,close(){
                window.location = $("#logout").attr('href');
            }
            ,clearIvl(c=1){
                if( ws.config.setIntervalInt != 0 ){
                    clearInterval(ws.config.setIntervalInt);
                    if(c == 1){
                        ws.config.websocket.onclose();
                    }
                }
            }
            ,do_100: function(data){

            }

            ,do_102: function(data){

            }
            ,do_101: function(data){ //退出
                layer.open({
                    title:'被踢下线!'
                    ,content:'你的账号在别处登录，如不是本人操作，请联系管理员！'
                    ,btn:['确认']
                    ,yes:function(index, layero){
                        layer.close(index);
                        ws.api.close();
                    }
                    ,cancel:function(){
                        layer.close(index);
                        ws.api.close();
                    },
                    success: function(layero, index){
                        ws.api.clearIvl(0);
                    }
                });
            }
        }
    };
    exports('ws', ws);
});
