<?php
/**
 * 微信销卡模块微站定义
 *
 * @author 微猴
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Vh_xiaokaModuleSite extends WeModuleSite {
	// 卡券信息数据表
	private $tb_cards = 'vh_kaquan';
	
	/**
	 * 链接数据库，获取卡券信息
	 * @return array()
	 */
	private function getAllKaquan(){

		// 声明微擎全局变量
		global $_W;

		$sql = 'SELECT * FROM '.tablename($this->tb_cards).' WHERE uniacid=:uniacid ORDER BY id asc';
		$params[':uniacid'] = $_W['uniacid'];
		$allKaquans = pdo_fetchall($sql, $params, 'id');
		return $allKaquans;
	}

	/**
	 * 执行具体的卡券信息操作
	 */
	public function doWebKaquan() {
		// 声明微擎全局变量
		global $_W, $_GPC;
		
		$ops = array('display', 'create', 'delete'); // 只支持此 3 种操作.
		$op = in_array($_GPC['op'], $ops) ? $_GPC['op'] : 'display';

		/* 展示信息请求 */
		if($op == 'display'){
			
			// 处理 POST 提交
			if (checksubmit()){
				$kaquan = $_GPC['allKaquans'];
				
				// 表单验证
				if(empty($kaquan)){
					message('尚未添加任何卡券信息');
				}
				foreach ($kaquan as $k => $cat){
					empty($cat['card_id']) && message('有卡券编码未添加,无法保存!');
				}
				
				// 数据更新
				foreach ($kaquan as $k => $cat){
					pdo_update($this->tb_cards, $cat, array('id'=>$k));
				}
				message('保存成功.','','success');
			}
			
			// 处理 GET 提交
			$allKaquans = $this->getAllKaquan();
			
			load()->func('tpl');
			include $this->template('kaquan');
		}
		
		/* 创建信息请求 */
		if ($op == 'create') {
			
			if (checksubmit()) {
				$kaquan = $_GPC['kaquan']; // 获取打包值
				if(empty($kaquan['card_id'])){
					message('未添加卡券编码, 无法保存');
				}
				if(empty($kaquan['card_detail'])){
					message('未添加卡券详情, 无法保存');
				}
				if(empty($kaquan['comment'])){
					message('卡券未备注，无法保存');
				}
				$kaquan['uniacid'] = $_W['uniacid'];
				pdo_insert($this->tb_cards, $kaquan);
				
				// createWebUrl是生成web url访问地址的函数 第一个参数是指定 模板页 ，第二个是指定 操作数据
				message('添加分类成功',$this->createWebUrl('kaquan', array('op'=>'display')),'success');
			}

			include $this->template('kaquan');
		}
			
		/* 删除信息请求 */
		if($op == 'delete') {

			$id = intval($_GPC['id']);
			if(empty($id)){
				message('未找到指定卡券信息');
			}
			$result = pdo_delete($this->tb_cards, array('id'=>$id, 'uniacid'=>$_W['uniacid']));
			if(intval($result) == 1){
				message('删除卡券信息成功.', $this->createWebUrl('kaquan'), 'success');
			} else {
				message('删除卡券信息失败.');
			}
		}
		
	}

}