<?php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        $from_id = input('fromid');
        $to_id = input('toid');
        $this->assign('from_id',$from_id);
        $this->assign('to_id',$to_id);
       return $this->fetch();
    }

    public function lists(){
        $from_id = input('from_id');
        $this->assign('from_id',$from_id);

        return $this->fetch();
    }
}
