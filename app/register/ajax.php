<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function process_action()
	{
		if ($this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/')
			), 1, null));
		}

		if (get_setting('register_type') == 'close')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));
		}
		else if (get_setting('register_type') == 'invite')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
		}

		if (!$_POST['username'] OR
			!$this->model('password')->check_structure($_POST['scrambled_password'], $_POST['client_salt']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的用户名和密码')));
		}

		// 检查验证码
		if ($this->model('register')->is_captcha_required())
		{
			if (!AWS_APP::captcha()->is_valid($_POST['captcha']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
			}
		}

		$register_interval = rand_minmax(get_setting('register_interval_min'), get_setting('register_interval_max'), get_setting('register_interval'));
		if (!check_user_operation_interval('register', 0, $register_interval, false))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('本站已开启注册频率限制, 请稍后再试')));
		}

		if ($check_result = $this->model('register')->check_username_char($_POST['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, $check_result));
		}

		if ( trim($_POST['username']) != $_POST['username'] OR
			$this->model('register')->check_username_sensitive_words($_POST['username']) )
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名中包含敏感词或系统保留字')));
		}

		if ($this->model('account')->username_exists($_POST['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
		}

		$uid = $this->model('register')->register($_POST['username'], $_POST['scrambled_password'], $_POST['client_salt']);
		if (!$uid)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('注册失败')));
		}

		set_user_operation_last_time('register', 0);

		$this->model('login')->logout();

		$this->model('login')->cookie_login($uid, $_POST['scrambled_password']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/home/first_login-TRUE')
		), 1, null));

	}

}
