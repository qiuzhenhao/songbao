<?php
namespace app\index\controller;

use app\common\Controller\Index as commonIndex;
use think\Request;
use think\Validate;
use think\Session;
use think\Db;

class Index extends commonIndex
{


    public function index()
    {
    	$statu = $this -> sessionCheck('1');
        if(!$statu){
            $this->error('操作失败');
        }
        return view('login');

    }

    //跳转至登录界面
    public function login(){
    
        return view('login');
    }

    //用户登录判断
    public function loginCheck()
    {

        try {
            Db::startTrans();
            if(request()->isPost()){
                $request = request();
                $userName = $request->post('username');
                $passWord = $request->post('password');

                //判断该用户名是邮箱还是电话号码
                $validate = new Validate();
                $validate->rule('email', 'email');
                $data = [
                    'email' => $userName
                ];
                if (!$validate->check($data)) {
                    $arrUser = DB::table('user_info')->where('TELEPHONE', '=', $userName)->find();
                    if(count($arrUser) == 0 )
                    {
                        return array('result'=>false, 'msg'=>'用户名不存在');
                    }
                }else{
                    $arrUser = DB::table('user_info')->where('EMAIL', '=', $userName)->find();
                    if(count($arrUser) == 0 )
                    {
                        return array('result'=>false, 'msg'=>'用户名不存在');
                    }
                }
                //加解密，E表示加密，D表示解密
                $passWordCheck = $arrUser['PASSWORD'];
                $passWordCheck = $this->encrypt($passWordCheck,'D');
                if($passWord === $passWordCheck)
                {
                    session('userId', $arrUser['USER_ID']);
                    session('userName', $arrUser['NAME']);
                    session('headPicture', $arrUser['HEAD_PIC']);
                    session('session_start_time', time());//记录会话开始时间！判断会话时间的重点！重点！重点！
                    return array('result'=>true, 'msg'=>'登录成功');
                }else{
                    return array('result'=>false, 'msg'=>'密码错误，请重新登录');
                }
            }else{
                // 回滚事务
                Db::rollback();
                return $this->error('500');
            }

        } catch (\Exception $e) {
            return json($e->getMessage());
        }
    }

    public function loginOUt()
    {
		try {
               Session::clear();
               return $this->login();

        } catch (\Exception $e) {
            return json($e->getMessage());
        }
    }
}
