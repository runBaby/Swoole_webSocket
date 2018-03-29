<?php
/**
 * Created by PhpStorm.
 * User: weizaojiao-wjp
 * Date: 2018/3/21
 * Time: 15:46
 */
$host = '0.0.0.0';
$port = '9503';
$ws = new swoole_websocket_server($host,$port);

//建立连接
$ws->on('open',function ($ws,$request){
    echo "新用户 $request->fd  加入。 \n";
    $GLOBALS['fd'][$request->fd]['id'] = $request->fd;              //设置用户ID
    $GLOBALS['fd'][$request->fd]['name'] = '游客'.$request->fd;    //初定义名称

    $msg['token_init'] = $request->fd;
    $ws->push($request->fd, json_encode($msg));
});

//监听消息
$ws->on('message',function ($ws,$request){

    $msg['time'] = date('Y-m-d H:i:s',time());
    $msg['total_people'] = count($GLOBALS['fd']);

    $request_data = json_decode($request->data,true);

    if(strstr($request_data['content'],'#username#')){
        //用户设置名称
        $GLOBALS['fd'][$request->fd]['name'] = str_replace("#username#",'',$request_data['content']);
        //首次进入
        $msg['content'] = '欢迎  '.$GLOBALS['fd'][$request->fd]['name'].' 加入';
        //服务端保存客户端的唯一token  方便以后辨别敌我
        if(!isset($GLOBALS['fd'][$request->fd]['token'])){
            $GLOBALS['fd'][$request->fd]['token'] = $request->fd;
        }

        foreach ($GLOBALS['fd'] as $value){
            $ws->push($value['id'], json_encode($msg));
        }

    }else{
        //聊天语
        $msg['content'] = ':'.$request_data['content']."\n";
        //发言用户的fd
        $msg['from_client_id'] = $request->fd;
        $msg['from_client_name'] = $GLOBALS['fd'][$request->fd]['name'];
        //token
        $msg['token'] = $GLOBALS['fd'][$request->fd]['token'];

        foreach ($GLOBALS['fd'] as $value){

               $ws->push($value['id'], json_encode($msg));
        }
    }
});

//关闭事件
$ws->on('close',function ($ws,$fd){
    echo "client--{$fd} 关闭\n";
    $msg['content'] = $GLOBALS['fd'][$fd]['name'].' 离开';

     unset($GLOBALS['fd'][$fd]);    //清除映射关系。

    //在线人数变化
    $msg['time'] = date('Y-m-d H:i:s',time());
    $msg['total_people'] = count($GLOBALS['fd']);
    foreach ($GLOBALS['fd'] as $value){
        $ws->push($value['id'], json_encode($msg));
    }

});

$ws->start();