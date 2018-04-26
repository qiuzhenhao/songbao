<?php

namespace app\common\controller;

use think\Controller;
use think\Session;

/**
*  
*/
class Index extends Controller
{
	//判断是否已登录超时或已登录 statu=1 : 由登录页面发出  statu=2 : 由其他模块发出
    public function sessionCheck($statu){

        if($statu == 1){
            if(session('userId') || session('userId') != null || session('userId') != ''){
                return $this->redirect('/admin/Index/index');
            };

            // 判断会话是否过期
            if (time() - session('session_start_time') < Config('SESSION_OPTIONS')['expire']) {
                return $this->redirect('/admin/Index/index');
            }
        }else if($statu == 2){
        	 
            if(!session('userId') || session('userId') == null || session('userId') == ''){

                return $this->redirect('/index/Index/login');
            };

            // 判断会话是否过期
            if (time() - session('session_start_time') > Config('SESSION_OPTIONS')['expire']) {
                Session::clear();
                return $this->redirect('/index/Index/login');
            }
        }

        return true;
    }
	//函数encrypt($string,$operation,$key)中$string：需要加密解密的字符串；$operation：判断是加密还是解密，E表示加密，D表示解密；$key：密匙。
	public function encrypt($string,$operation,$key='www.songbaocc.com'){ 
		// dump($string);exit;
	    $key=md5($key); 
	    $key_length=strlen($key); 
	      $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string; 
	    $string_length=strlen($string); 
	    $rndkey=$box=array(); 
	    $result=''; 
	    for($i=0;$i<=255;$i++){ 
	           $rndkey[$i]=ord($key[$i%$key_length]); 
	        $box[$i]=$i; 
	    } 
	    for($j=$i=0;$i<256;$i++){ 
	        $j=($j+$box[$i]+$rndkey[$i])%256; 
	        $tmp=$box[$i]; 
	        $box[$i]=$box[$j]; 
	        $box[$j]=$tmp; 
	    } 
	    for($a=$j=$i=0;$i<$string_length;$i++){ 
	        $a=($a+1)%256; 
	        $j=($j+$box[$a])%256; 
	        $tmp=$box[$a]; 
	        $box[$a]=$box[$j]; 
	        $box[$j]=$tmp; 
	        $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256])); 
	    } 
	    if($operation=='D'){ 
	        if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)){ 
	            return substr($result,8); 
	        }else{ 
	            return''; 
	        } 
	    }else{ 
	        return str_replace('=','',base64_encode($result)); 
	    } 
	}
	
	/**
     * 参数校验
     *
     * @param $checkType 被校验参数的类型
     * @param $checkContent 被校验的内容
     * @return true 校验通过
     *         false 校验不通过
     */
	public function checkMain($checkType, $checkContent)
	{
		 try {
            if (!isset($checkType) || !isset($checkContent)) {
                return false;
            }
            $checkContent = trim($checkContent);
            switch ($checkType) {
                case "key":
                    /* 1~11位数字*/
                    $RegExp = '/^[\d]{1,11}$/';
                    return preg_match($RegExp, $checkContent);
                case "name":
                    /* 只含有汉字(包括汉字字符，例如：？《》)、数字、字母、下划线,并且不能以下划线开头和结尾*/
                    $RegExp = '/^(?!_|\s\')[A-Za-z0-9_\x80-\xff\s\']+$/';
                    return preg_match($RegExp, $checkContent);
                case "userName":
                    /* 由大小写字母跟数字组成并且长度在6-30字符*/
                    $RegExp = '/^[a-zA-Z0-9_]{6,30}$/';
                    return preg_match($RegExp, $checkContent);
                case "password":
                    /* 由大小写字母跟数字组成并且长度在6-16字符*/
                    $RegExp = '/^[a-zA-Z0-9_]{6,16}$/';
                    return preg_match($RegExp, $checkContent);
                case "rolename":
                    /* 由大小写字母组成并且长度在2-6字符*/
                    $RegExp = '/^[a-zA-Z]{2,6}$/';
                    return preg_match($RegExp, $checkContent);
                case "telephone":
                    /* 第一个数字是，第二个数字是3|4|5|6|7|8|9,后面跟着9个数字*/
                    $RegExp = '/^1(3|4|5|6|7|8|9)\d{9}$/';
                    return preg_match($RegExp, $checkContent);
                case "homePhone":
                    /* 校验家庭电话*/
                    $RegExp = '/^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$/';
                    return preg_match($RegExp, $checkContent);
                case "symbol":
                    /* 校验标志或代号，由大小写字母跟数字组成并且长度在1-6字符*/
                    $RegExp = '/^[a-zA-Z0-9_]{1,6}$/';
                    return preg_match($RegExp, $checkContent);
                case "email":
                    /* 邮箱*/
                    $RegExp = '/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i';
                    return preg_match($RegExp, $checkContent);
                case "selectNum":
                    /* 1~8位数字*/
                    $RegExp = '/^[\d]{1,8}$/';
                    return preg_match($RegExp, $checkContent);
                case "boolNum":
                    return !($checkContent != 0 && $checkContent != 1);
                case "date":
                    return strtotime(date('Y-m-d', strtotime($checkContent))) === strtotime($checkContent);
                default:
                    /* 传入字符串内有其它非法字段*/
                    return false;
            }
        } catch (\Exception $e) {
             return json($e->getMessage());
        }
	}


}
