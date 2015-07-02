<?php
/**
 * 微信卡券管理模块处理程序
 *
 * @author 微猴
 * @url vmonkey.cn
 */
defined('IN_IA') or exit('Access Denied');

class Vh_xiaokaModuleProcessor extends WeModuleProcessor {
	// 卡券信息数据表
	private $tb_cards = 'vh_kaquan';
	// 自定义规则信息数据表
	private $tb_rule = 'vh_kaquanrule';
	// 规则信息总表
	private $tb_modelrule = 'rule';

	public function respond() {
		// 定义全局变量
		global $_GPC, $_W;
		
		// 获取传进来的内容 'content'
		$content = $this->message['content'];
		
		// 判断是否进入了上下文模式
		// if (!$this->inContext) {
		// 	$reply = "亲，直接回复卡券号码就可以销毁了哦！不要带 -，例如：115371299650。";
		// 	// 开始上下文模式,将上下文模式设置为2分钟
		// 	$this->beginContext(120);
		// }else {
		
		// 判断内容
		// return $this->match_card($content);
		// }
		// 返回消息内容
		$reply =  $this->judge_rule();
		return $this->respText($reply);
	}

	/* 定义判断规则的函数 */
	private function judge_rule() {
		// 定义全局变量
		global $_GPC, $_W;

		// 根据不同反应的规则得出相应的规则内容
		$rid = $this->rule;
		$rule_sql = "SELECT * FROM ".tablename($this->tb_rule)." WHERE acid=:acid AND rid=:rid LIMIT 1";
		$params = array(
			':acid' => $_W['acid'],
			':rid' => $rid
		);
		$rule_reply = pdo_fetch($rule_sql, $params);
		// print_r($rule_reply);
		
		// 从系统的 ims_rule 表中提取去规则的名称(name)
		$modelrule_sql = "SELECT * FROM ".tablename($this->tb_modelrule)." WHERE uniacid=:uniacid AND id=:rid LIMIT 1";
		$modlerule_reply = pdo_fetch($modelrule_sql, array(':uniacid' => $_W['uniacid'],':rid' => $rid));

		if ($modlerule_reply['name'] == '正则') {
			$reply = '正则判断';
		} else {
			$reply = $rule_reply['rule'];
		}
		return $reply;
	}

	/* 定义判断卡券内容的函数 */
	private function match_card($content) {

		//首先判断内容是否为空
		if (empty($content)) {
			$reply = "亲，卡券号码不能为空哦~";
		} else {
			/* 利用正则判断是否为12位数字 */
			$match = '/^\d{12}$/';
			if(!preg_match($match, $content)) {
				$reply = "亲，请正确输入12位数字哦~";
			}else {
				// 调用销毁卡券函数
				return $this->destroy_card($content);
			}
		}
	
	// 返回消息内容
	return $this->respText($reply);
	}

	/* 定义根据 card_id 返回对应卡券详情的函数 */
	private function reply_kaquanDetail($card_id) {

		$sql = "SELECT card_detail FROM ".tablename($this->tb_cards)." WHERE card_id = :card_id";
		$param[':card_id'] = $card_id;
		$card_detail = pdo_fetch($sql, $param);
		return $card_detail['card_detail'];
	}

	/* 定义销毁卡券的函数 */
	private function destroy_card($content) {

		// 获取公众号的 access_token
		load()->classs('weixin.account');
		$access_token = WeiXinAccount::fetch_available_token();
		// 生成post请求的url地址
		$url = 'https://api.weixin.qq.com/card/code/consume?access_token='.$access_token;
		// post请求的内容
		$posts = '{"code":'.$content.'}';
		// 加载文件: load()→func('communication')
		load()->func('communication');
		// 向处理卡券的模块提交post请求，并返回数据,可以尝试对结果打印
		$result = ihttp_post($url, $posts);
		// print_r($result);
		// 对返回的数据中的['content']进行json_decode处理
		$res = json_decode($result['content'], true);
		// 获取返回的错误码
		$errcode = $res["errcode"];
		// 获取返回的card_id
		$card_id = $res["card_id"];

		// 根据返回的错误码进行相应的处理
		switch ($errcode) {
			case '0':
				$message = '消费成功！卡券信息为：'.$this->reply_kaquanDetail($card_id);
				break;
			case '40099':
				$message = '该卡券已经被销毁！请重新输入'.$str.'进行销卡';
				break;
			case '40078':
				$message = '不合法的卡券状态！请重新输入“销卡”进行销卡';
				break;
			default:
				$message = '出现错误，无法销毁！请重新输入“销卡”进行销卡';
				break;
		}
		$this->endContext();
	// 返回消息内容
	return $this->respText($message);
	}

	/* 定义根据卡券card_id返回卡券内容的函数 */

}