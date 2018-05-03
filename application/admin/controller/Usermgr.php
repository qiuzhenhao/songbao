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
					}
					$userList[$key]['checked'] = false;
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
				$areaList = Db::table('area_list')->where('AREA_ID', $areaId)->where('STATU',1)->select();
				if(count($areaList) == 0){
					return array('result'=>false, 'msg' => '该地区不存在，请联系管理员');
				}
				
				//判读角色是否存在
				$roleList = Db::table('role_list')->where('ROLE_ID', $roleId)->where('STATU',1)->select();
				if(count($roleList) == 0){
					return array('result'=>false, 'msg' => '该角色不存在，请联系管理员');
				}
				
				//判断手机号是否注册过
				$userList = Db::table('user_info')->where('STATU',1)->select();
				foreach($userList as $k_user =>$v_user){
					if($v_user['TELEPHONE'] == $telephone){
						return array('result'=>false, 'msg' => '该手机号码已注册过，请重新输入');
					}
				}

				//创建者ID
				$creatId = Session::get('userId');

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
					'CREAT_ID' => $creatId
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

	//编辑用户
	public function editUser()
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
                $userId = $request->post('userId');
				
				//检查数据
				if($name == '' || !isset($name)  || !$this->checkMain('name', $name)){
					return array('result'=>false, 'msg' => '请填写正确的姓名');
				}
				if($telephone == '' || !isset($telephone)  || !$this->checkMain('telephone', $telephone)){
					return array('result'=>false, 'msg' => '请填写正确的手机号码');
				}
				if($userId == '' || !isset($userId)  || !$this->checkMain('key', $userId)){
					return array('result'=>false, 'msg' => '该用户不存在，请刷新重试');
				}

				//获取地区列表，判断该地区ID是否存在
				$areaList = Db::table('area_list')->where('AREA_ID', $areaId)->where('STATU',1)->select();
				if(count($areaList) == 0){
					return array('result'=>false, 'msg' => '该地区不存在，请联系管理员');
				}
				
				//判读角色是否存在
				$roleList = Db::table('role_list')->where('ROLE_ID', $roleId)->where('STATU',1)->select();
				if(count($roleList) == 0){
					return array('result'=>false, 'msg' => '该角色不存在，请联系管理员');
				}
				
				//判断手机号是否注册过
				$userList = Db::table('user_info')->where('STATU',1)->where('USER_ID', '<>', $userId)->select();
				foreach($userList as $k_user =>$v_user){
					if($v_user['TELEPHONE'] == $telephone){
						return array('result'=>false, 'msg' => '该手机号码已注册过，请重新输入');
					}
				}
				

				$userSelf =  Db::table('user_info')->where('STATU',1)->where('USER_ID', $userId)->select();
				if(count($userSelf) == 0){
					Db::rollback();
					return array('result'=>false, 'msg' => '该用户不存在，请联系管理员');
				}
				//更新role表, 若角色改变，则原角色需要 -1 ， 新角色需要 +1
				if($userSelf[0]['ROLE_ID'] != $roleId){
					//现角色
					$nowRoleAmount = $roleList[0]['PEOPLE_AMOUNT'] + 1;
					$nowResRole = Db::name('role_list')->where('ROLE_ID',$roleId)->update(['PEOPLE_AMOUNT' => $nowRoleAmount]);
					if($nowResRole != 1){
						Db::rollback();
						return array('result' => false, 'msg' => '编辑用户失败，请刷新重试');
					}
					//原角色
					$preRoleList = Db::table('role_list')->where('ROLE_ID', $userSelf[0]['ROLE_ID'])->where('STATU',1)->select();
					if(count($preRoleList) == 0){
						Db::rollback();
						return array('result'=>false, 'msg' => '编辑用户失败，请刷新重试');
					}
					$preRoleAmount = $preRoleList[0]['PEOPLE_AMOUNT'] - 1;
					$preResRole = Db::name('role_list')->where('ROLE_ID',$userSelf[0]['ROLE_ID'])->update(['PEOPLE_AMOUNT' => $preRoleAmount]);
					if($preResRole != 1){
						Db::rollback();
						return array('result' => false, 'msg' => '编辑用户失败，请刷新重试');
					}
				}
				
				//更新area表，如地区改变，则原地区需要 -1 ， 新地区需要 +1
				if($userSelf[0]['AREA_ID'] != $areaId){
					//现地区
					$nowAreaAmount = $areaList[0]['PEOPLE_AMOUNT'] + 1;
					$nowResArea = Db::name('area_list')->where('AREA_ID',$areaId)->update(['PEOPLE_AMOUNT' => $nowAreaAmount]);
					if($nowResArea != 1){
						Db::rollback();
						return array('result' => false, 'msg' => '编辑用户失败，请刷新重试');
					}
					//原地区
					$preAreaList = Db::table('area_list')->where('AREA_ID', $userSelf[0]['AREA_ID'])->where('STATU',1)->select();
					if(count($preAreaList) == 0){
						Db::rollback();
						return array('result'=>false, 'msg' => '该地区不存在，请联系管理员');
					}
					$preAreaAmount = $preAreaList[0]['PEOPLE_AMOUNT'] - 1;
					$preResArea = Db::name('area_list')->where('AREA_ID',$userSelf[0]['AREA_ID'])->update(['PEOPLE_AMOUNT' => $preAreaAmount]);
					if($preResArea != 1){
						Db::rollback();
						return array('result' => false, 'msg' => '编辑用户失败，请刷新重试');
					}
				}
	
				//编辑者ID
				$editId = Session::get('userId');
				//编辑数据
				$data = [
					'ROLE_ID' => $roleId,
					'NAME' => $name,
					'SEX' => $sex,
					'AREA_ID' => $areaId,
					'ADDRESS' =>  $address,
					'TELEPHONE' => $telephone,
					'EDIT_TIME' => date("Y-m-d H:i:s"),
					'EDIT_ID' => $editId,
				];

				$resUpdate = Db::name('user_info')->where('USER_ID',  $userId)->update($data);
				if($resUpdate != 1){
					Db::rollback();
					return array('result' => false, 'msg' => '编辑用户失败，请刷新重试');
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


	//删除用户
	public function deleteUser()
	{
		Db::startTrans();
		try{
    		if(request()->isPost()){
                $request = request();          
                $deleteId = $request->post('deleteId/a');
				$userId = Session::get('userId');	
				//检查数据
				if(count($deleteId) == 0 || !isset($deleteId)){
					return array('result' => false, 'msg' => '删除失败，请刷新重试');
				}
				
				foreach ($deleteId as $key => $value) {

					//由用户ID获取用户信息
					$userList = Db::table('user_info')->where('STATU',1)->where('USER_ID',$value)->select();
					if(count($userList) != 1){
						Db::rollback();
						return array('result'=>false, 'msg' => '删除失败，请刷新重试');
					}
						
					//判断是否为用户本人
					if($userId == $userList[0]['USER_ID']){
						Db::rollback();
						return array('result' => false, 'msg' => '不能删除用户本人');
					}
					
					//role_list表数量 -1 
					$roleList = Db::table('role_list')->where('ROLE_ID', $userList[0]['ROLE_ID'])->where('STATU',1)->select();
					if(count($roleList) == 0){
						Db::rollback();
						return array('result'=>false, 'msg' => '删除失败，请刷新重试');
					}
					$roleAmount = $roleList[0]['PEOPLE_AMOUNT'] - 1;
					$resRole = Db::name('role_list')->where('ROLE_ID',$userList[0]['ROLE_ID'])->update(['PEOPLE_AMOUNT' => $roleAmount]);
					if($resRole != 1){
						Db::rollback();
						return array('result' => false, 'msg' => '删除失败，请刷新重试');
					}
					
					//area_list表数量-1
					$areaList = Db::table('area_list')->where('AREA_ID', $userList[0]['AREA_ID'])->where('STATU',1)->select();
					if(count($areaList) == 0){
						Db::rollback();
						return array('result'=>false, 'msg' => '删除失败，请刷新重试');
					}
					$areaAmount = $areaList[0]['PEOPLE_AMOUNT'] - 1;
					$resArea = Db::name('area_list')->where('AREA_ID',$userList[0]['AREA_ID'])->update(['PEOPLE_AMOUNT' => $areaAmount]);
					if($resArea != 1){
						Db::rollback();
						return array('result' => false, 'msg' => '删除失败，请刷新重试');
					}
					
					//用户信息删除
					$data = [
						'STATU' => 0,
						'DELETE_ID' => $userId,
						'DELETE_TIME' => date("Y-m-d H:i:s"),
					];
					$deleteUser = Db::name('user_info')->where('USER_ID',$value)->update($data);
					if($deleteUser != 1){
						Db::rollback();
						return array('result' => false, 'msg' => '删除失败，请刷新重试');
					}

				}
				Db::commit();
				return array('result' => true, 'msg' => '删除成功');

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