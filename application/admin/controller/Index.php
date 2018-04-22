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

		//判断是否登录超时
		$statu = $this -> sessionCheck('2');
		 if(!$statu){
             $this->error('操作失败');
         }

         $userName = Session::get('userName');    
         $headPicture = Session::get('headPicture');
        		
		 $this->assign('userName',$userName);
         $this->assign('headPicture',$headPicture);
 
         $this->view->engine->layout(true);
         return $this->fetch();
    }

};