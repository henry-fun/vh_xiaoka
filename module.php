<?php
/**
 * 微信销卡模块定义
 *
 * @author 微猴
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Vh_xiaokaModule extends WeModule {
	private $kaquan_rule = 'vh_kaquanrule';

	public function fieldsFormDisplay($rid = 0) {
		//要嵌入规则编辑页的自定义内容，这里 $rid 为对应的规则编号，新增时为 0
		global $_W;

		// 如果$rid=0表示没有规则，如果不等于0表示有规则，于是将其提取出来并加以显示
		if (!empty($rid)) {
			$reply = pdo_fetch("SELECT rule FROM ".tablename($this->kaquan_rule)." WHERE rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
		}

		load()->func('tpl');
		include $this->template('rule_reply');
	}

	public function fieldsFormValidate($rid = 0) {
		//规则编辑保存时，要进行的数据验证，返回空串表示验证无误，返回其他字符串将呈现为错误提示。这里 $rid 为对应的规则编号，新增时为 0
		return true;
	}

	/**
	 * 规则保存成功后执行此方法,保存附加字段入库
	 */
	public function fieldsFormSubmit($rid = 0) {
		//规则验证无误保存入库时执行，这里应该进行自定义字段的保存。这里 $rid 为对应的规则编号
		global $_GPC, $_W;

		$id = intval($_GPC['reply_id']);
		$insert = array(
			'rid' => $rid,
			'acid'=> $_W['uniacid'],
			'rule' => $_GPC['rule']
		);

		// 判断提交新规则的id值，如果不存在则插入新数据，如果存在则更新数据
		if (empty($id)) {
			pdo_insert($this->kaquan_rule, $insert);
		} else {
			pdo_update($this->kaquan_rule, $insert, array('id' => $id));
		}

	}

	public function ruleDeleted($rid) {
		//删除规则时调用，这里 $rid 为对应的规则编号
	}

	public function settingsDisplay($settings) {
		global $_W, $_GPC;
		//点击模块设置时将调用此方法呈现模块设置页面，$settings 为模块设置参数, 结构为数组。这个参数系统针对不同公众账号独立保存。
		//在此呈现页面中自行处理post请求并保存设置参数（通过使用$this->saveSettings()来实现）
		if(checksubmit()) {
			//字段验证, 并获得正确的数据$dat
			$this->saveSettings($dat);
		}
		//这里来展示设置项表单
		include $this->template('setting');
	}

}