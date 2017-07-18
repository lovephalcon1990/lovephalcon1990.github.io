<?php
//创建websocket服务器对象，监听0.0.0.0:9501端口
$ws = new swoole_websocket_server("0.0.0.0", 9501);

//监听WebSocket连接打开事件
$ws->on('open', function ($ws, $request) {
	//相当于记录一个日志吧，有连接时间和连接ip
    echo $request->fd.'-----time:'.date("Y-m-d H:i:s",$request->server['request_time']).'--IP--'.$request->server['remote_addr'].'-----';
});

//监听WebSocket消息事件
$ws->on('message', function ($ws, $frame) {
    var_dump($frame->data,true);
    echo "\n";
	foreach($ws->connections as $fd){
		//如果不是自己就显示默认
		if($frame->fd == $fd){
			$ws->push($frame->fd, $frame->data);
		}else{
			$result = preg_replace('/\#\w{6}/','#ffffff',$frame->data);
			$ws->push($fd, "{$result}");
		}
    }
});

//监听WebSocket连接关闭事件
$ws->on('close', function ($ws, $fd) {
    echo "client-{$fd} is closed\n";
});

$ws->start();
