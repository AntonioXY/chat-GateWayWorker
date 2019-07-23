<?php
/**
 * Created by PhpStorm.
 * User: w
 * Date: 2019/7/1
 * Time: 23:33
 */
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Request;

class Chat extends Controller{



    public function save_message(){
        if(Request::instance()->isAjax()){
            try{
                $message = input();
                $data = [
                    'from_id'=>$message['from_id'],
                    'to_id'=>$message['to_id'],
                    'from_name'=>$this->getName($message['from_id']),
                    'to_name'=>$this->getName($message['to_id']),
                    'content'=>$message['data'],
                    'time'=>$message['time'],
                    //'is_read'=>$message['is_read'],
                    'is_read'=>0,
                    'type'=>1,//文本消息标识为1
                ];
                Db::name('communication')->insert($data);
            }catch (\Exception $e){
                var_dump($e);exit;
            }
        }
    }

    //根据用户id返回用户姓名
    public function getName($user_id){
        $user_info = Db::name('user')->where('id',$user_id)->field('nickname')->find();

        return $user_info['nickname'];
    }

    public function get_head(){
        if(Request::instance()->isAjax()){
            $from_id = input('from_id');
            $to_id = input('to_id');
            $from_info = Db::name('user')->where('id',$from_id)->field('headimgurl')->find();
            $to_info = Db::name('user')->where('id',$to_id)->field('headimgurl')->find();

            return [
                'from_head'=>$from_info['headimgurl'],
                'to_head'=>$to_info['headimgurl']
            ];
        }
    }

    public function get_name(){
        if(Request::instance()->isAjax()){
            $to_id = input('uid');
            $to_info = Db::name('user')->where('id',$to_id)->field('nickname')->find();

            return [
                'name'=>$to_info['nickname']
            ];
        }
    }

    //加载聊天内容
    public function load(){
        try{
            if(Request::instance()->isAjax()){
                $from_id = input('from_id');
                $to_id = input('to_id');
                $message = Db::name('communication')
                    ->where('(from_id=:from_id and to_id=:to_id) or (from_id=:to_id1 and to_id=:from_id1)',[
                        'from_id'=>$from_id,
                        'from_id1'=>$from_id,
                        'to_id'=>$to_id,
                        'to_id1'=>$to_id
                    ])
                    ->order('id')
                    ->select();


                return $message;
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
        }
    }
    public function getUserInfo(){

    }

    //上传图片
    public function upload_img(){
        try{
            $file = $_FILES['file'];
            $from_id = input('from_id');
            $to_id = input('to_id');
            $online = input('online');

            $suffix = strtolower(strrchr($file['name'],'.'));
            $type_arr = ['.jpg','.jpeg','.png','.gif'];
            if(!in_array($suffix,$type_arr)){
                return ['status'=>-1,'msg'=>'img type error'];
            }
            if($file['size']/1024 > 5120){
                return ['status'=>-1,'msg'=>'img is too large'];
            }

            $file_name = uniqid("chat_img",false);
            $upload_path = ROOT_PATH . 'public\\uploads\\';
            $file_up = $upload_path . $file_name . $suffix;
            $re = move_uploaded_file($file['tmp_name'],$file_up);
            if($re){
                $name = $file_name . $suffix;
                $data = [
                    'content'=>$name,
                    'from_id'=>$from_id,
                    'to_id'=>$to_id,
                    'type'=>2,
                    'from_name'=>$this->getName($from_id),
                    'to_name'=>$this->getName($to_id),
                    'time'=>time(),
                    //'is_read'=>$online,
                    'is_read'=>0,
                ];
                $message_id = Db::name('communication')->insert($data);
                if($message_id){
                    return ['status'=>0,'img_name'=>$name];
                }else{
                    return ['status'=>-1,'msg'=>'uplaod fail'];
                }
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
        }

    }


    //获取聊天列表
    public function get_list(){
        if(Request::instance()->isAjax()){
            $from_id = input('id');
            $info = Db::name('communication')->field(['from_id','to_id','from_name'])->where('to_id',$from_id)->group('from_id')->select();

            $rows = array_map(function ($res){
                return [
                    'head_url'=>$this->get_head_one($res['from_id']),
                    'username'=>$res['from_name'],
                    'countNotRead'=>$this->getCountNotRead($res['from_id'],$res['to_id']),
                    'last_message'=>$this->getLastMessage($res['from_id'],$res['to_id']),
                    'chat_page'=>'http://chat.com/index.php/index/index/index?fromid='.$res['to_id'].'&toid=' . $res['from_id']
                ];
            },$info);
            return $rows;
        }
    }

    //根据id获取用户头像
    private function get_head_one($uid){
        $user = Db::name('user')->where('id',$uid)->field('headimgurl')->find();
        return $user['headimgurl'];
    }

    //获取聊天未读对象
    private function getCountNotRead($from_id,$to_id){
        $count = Db::name('communication')
            ->where('to_id',$to_id)
            ->where('from_id',$from_id)
            ->where('is_read',0)
            ->count();
        return $count;
    }

    //获取聊天最近一条数据
    private function getLastMessage($from_id,$to_id){
        $message = Db::name('communication')
            ->where('(from_id=:from_id and to_id=:to_id) or (from_id=:from_id2 and to_id=:to_id2)',[
                'from_id'=>$from_id,
                'to_id'=>$to_id,
                'from_id2'=>$to_id,
                'to_id2'=>$from_id
            ])
            ->order('id','desc')
            ->limit(1)
            ->find();
        return $message;
    }


    public function changeNotRead()
    {
        if (Request::instance()->isAjax()) {
            $from_id = input('from_id');
            $to_id = input('to_id');
            Db::name('communication')
                ->where('from_id',$from_id)
                ->where('to_id',$to_id)
                ->update(['is_read'=>1]);

        }
    }
}
