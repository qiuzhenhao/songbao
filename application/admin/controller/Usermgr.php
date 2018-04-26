<?php

namespace app\admin\controller;

use app\admin\controller\Index as commonIndex;
use think\Session;
use think\Db;

class Usermgr extends commonIndex
{

	//用户列表
	public function userList()
    {
        return view('userList');
    }
    //权限列表
    public function authority()
    {
        return view('authority');
    }
    //个人
    public function personal()
    {
        return view('personal');
    }

    //获取用户列表
    public function getUserList()
    {
    	try{
    		if(request()->isPost()){
                $request = request();
                $searchVal = $request->post('searchVal');
                $start = $request->post('start');
                $areaId = $request->post('areaId');

				//$areaId = 0 说明没选地区
				if($areaId != 0 && isset($areaId)){
					 //获取用户信息
					$userList = Db::table('user_info')
								->field('USER_ID,ROLE_ID,NAME,ADDRESS,TELEPHONE,EMAIL,CREAT_TIME,AREA_ID,SEX')
								->where('NAME|ADDRESS','like','%'.$searchVal.'%')
								->where('STATU',1)
								->where('AREA_ID',$areaId)
								->page($start,10)
								->select();
					//获取页数
					$allUserList = Db::table('user_info')
									->where('NAME|ADDRESS','like','%'.$searchVal.'%')
									->where('STATU',1)
									->where('AREA_ID',$areaId)
									->select();
					$pageCount = ceil(count($allUserList)/10);
				}else{
					 //获取用户信息
					$userList = Db::table('user_info')
								->field('USER_ID,ROLE_ID,NAME,ADDRESS,TELEPHONE,EMAIL,CREAT_TIME,AREA_ID,SEX')
								->where('NAME|ADDRESS','like','%'.$searchVal.'%')
								->where('STATU',1)
								->page($start,10)
								->select();
					//获取页数
					$allUserList = Db::table('user_info')
									->where('NAME|ADDRESS','like','%'.$searchVal.'%')
									->where('STATU',1)
									->select();
					$pageCount = ceil(count($allUserList)/10);
				}
	               
				//获取地区列表
				$areaList = Db::table('area_list')->where('STATU',1)->select();
				//获取roleName
				$roleList = Db::table('role_list')->where('STATU',1)->select();

				if(count($areaList) != 0 || count($roleList) != 0){
					foreach ($userList as $key => $value) {
						if(count($areaList) != 0){
							if($value['AREA_ID'] != null || $value['AREA_ID'] != ''){
								foreach($areaList as $kArea => $vArea){
									if($value['AREA_ID'] == $vArea['AREA_ID']){
										 $userList[$key]['area'] = $vArea['AREA_NAME'];
									}
								}
							}
						}
						if(count($roleList) != 0){
							foreach($roleList as $kRole => $vRole){
								if($value['ROLE_ID'] == $vRole['ROLE_ID']){
									 $userList[$key]['roleName'] = $vRole['ROLE_NAME'];
								}
							}
							$userList[$key]['roleName'] = $roleList[$value['ROLE_ID'] -1]['ROLE_NAME'];
						}
						
					}
				}

				

				return array('result'=>true,'msg'=>$userList,'pageCount'=>$pageCount);
			}else{
				 return $this->error('500');
			}

        } catch (\Exception $e) {
            return json($e->getMessage());
        }
    }
	
	//获取地区列表
	public function getAreaRoleList()
	{
		try{
			//获取地区列表
			$areaList = Db::table('area_list')->where('STATU',1)->select();
			
			//获取角色列表
			$roleList = Db::table('role_list')->where('STATU',1)->select();

			return array('result' => true, 'areaList' => $areaList, 'roleList' => $roleList);
				
		
		}catch(\Exception $e){
			 return json($e->getMessage());
		}
	}

	//新增用户
	public function addUser()
	{
		Db::startTrans();
		try{
    		if(request()->isPost()){
                $request = request();          
                $name = $request->post('name');
                $sex = $request->post('sex');
                $telephone = $request->post('telephone');
				$roleId = $request->post('roleId');
                $areaId = $request->post('areaId');
                $address = $request->post('address');
				
				//检查数据
				if($name == '' || !isset($name)  || !$this->checkMain('name', $name)){
					return array('result'=>false, 'msg' => '请填写正确的姓名');
				}
				if($telephone == '' || !isset($telephone)  || !$this->checkMain('telephone', $telephone)){
					return array('result'=>false, 'msg' => '请填写正确的手机号码');
				}

				//获取地区列表，判断该地区ID是否存在
				$areaList = Db::table('area_list')->where('AREA_ID', $areaId)->select();
				if(count($areaList) == 0){
					return array('result'=>false, 'msg' => '该地区不存在，请联系管理员');
				}
				
				//判读角色是否存在
				$roleList = Db::table('role_list')->where('ROLE_ID', $roleId)->select();
				if(count($roleList) == 0){
					return array('result'=>false, 'msg' => '该角色不存在，请联系管理员');
				}

				//添加数据
				$data = [
					'ROLE_ID' => $roleId,
					'NAME' => $name,
					'SEX' => $sex,
					'AREA_ID' => $areaId,
					'ADDRESS' =>  $address,
					'TELEPHONE' => $telephone,
					'PASSWORD' => $this->encrypt('123456', 'E'),
					'CREAT_TIME' => date("Y-m-d H:i:s"),
				];
				$resAdd = Db::name('user_info')->strict(false)->insert($data);
				if($resAdd != 1){
					Db::rollback();
					return array('result' => false, 'msg' => '添加用户失败，请刷新重试');
				}
				
				// //更新role表
				$roleAmount = $roleList[0]['PEOPLE_AMOUNT'] + 1;
				 $resRole = Db::name('role_list')->where('ROLE_ID',$roleId)->update(['PEOPLE_AMOUNT' => $roleAmount]);
				if($resRole != 1){
					Db::rollback();
					return array('result' => false, 'msg' => '添加用户失败，请刷新重试');
				}

				//更新area表
				$areaAmount = $areaList[0]['PEOPLE_AMOUNT']+1;
				$resArea = Db::name('area_list')->where('AREA_ID',$areaId)->update(['PEOPLE_AMOUNT' => $areaAmount]);
				if($resArea != 1){
					Db::rollback();
					return array('result' => false, 'msg' => '添加用户失败，请刷新重试');
				}

				Db::commit();
				return array('result' => true, 'msg' => '添加成功');

			}else{
				 Db::rollback();
				 return $this->error('500');
			}

        } catch (\Exception $e) {
        	 Db::rollback();
            return json($e->getMessage());
        }
	}

}