<?php
/**
 * Created by PhpStorm.
 * User: xiaoqiu
 * Date: 2018/4/19
 * Time: 15:26
 */
namespace app\admin\controller;

use app\common\Controller\Index as commonIndex;
use think\Session;
use think\Db;

class Index extends commonIndex
{
  //    public function __construct()
  //    {
  //        $statu = $this -> sessionCheck();
  //        if(!$statu){
  //            $this->error('操作失败');
  //        }
	 // }

    public function index()
    {

		try{
			//判断是否登录超时
			$statu = $this -> sessionCheck('2');

			 if(!$statu){
	             $this->error('500');
	         }

	         $userName = Session::get('userName');
	         $headPicture = Session::get('headPicture');

	         $userId = Session::get('userId');	
	         //获取左侧列表
	         $navArr = [];

	         $userList = DB::table('user_info')->where('USER_ID', $userId)->where('statu',1)->select();
	         if(count($userList) != 1){
	                return $this->error('500');
	         }
	         $roleId = $userList[0]['ROLE_ID'];	
	         $roleList = DB::table('role_nav')->where('ROLE_ID', $roleId)->select();
	         foreach ($roleList as $key => $value){
	             $navList = DB::table('nav_list')->where('NAV_ID', $value['NAV_ID'])->select();

	            $navList[0]['child']= null;

	            if($navList[0]['PARENT_ID'] != null || $navList[0]['PARENT_ID'] != ''){
	                foreach ($navArr as $k => $v){
	                    if($v['NAV_ID'] == $navList[0]['PARENT_ID']){
	                        $navArr[$k]['child'][] = $navList[0];
	                    }
	                }

	            }else{
	                $navArr[] = $navList[0];
	            }
	         }
			 $this->assign('userName',$userName);
	         $this->assign('headPicture',$headPicture);
	         $this->assign('navArr',$navArr);

	         return $this->fetch('layoutIndex');
	     }catch(\Exception $e){
			 return json($e->getMessage());
	     }
    }

    public function home()
    {
		return view('home');
    }
   public function userList()
    {
		return view('userList');
    }

};