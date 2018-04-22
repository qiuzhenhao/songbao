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



}
