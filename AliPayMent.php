<?php
namespace AliPay;
class AliPayMent {

	private $alipay_gateway = 'https://mapi.alipay.com/gateway.do';
	protected $sign_type = 'MD5';
	//基本参数
	private $key = '';
	public $basic_params = array(
		'service' =>'create_direct_pay_by_user',
		 
	);


	function __construct($config){
		$this->key = trim($config['key']);
		$this->basic_params['partner'] = trim($config['partner']);
		$this->basic_params['seller_id'] = trim($config['partner']);
		$this->basic_params['notify_url'] = trim($config['notify_url']);
		$this->basic_params['return_url'] = trim($config['return_url']);
		$this->basic_params['_input_charset'] = trim(strtolower('utf-8'));
	}


	/**
	 * 合并请求参数
	*/
	public function mergeParams($business_params){
		$para_temp = array_merge($this->basic_params,$business_params);
		return $para_temp;
	}

	/**
	 * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
	*/
	public function createRequestPara($para_temp){
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->createSign($para_sort);
		
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->sign_type));
		
		return $para_sort;

	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function paraFilter($para) {
		$para_filter = array();
		while (list ($key, $val) = each ($para)) {
			if($key == "sign" || $key == "sign_type" || $val == "")continue;
			else	$para_filter[$key] = $para[$key];
		}
		return $para_filter;
	}
	/**
	 * 对数组排序
	 * @param $para 排序前的数组
	 * return 排序后的数组
	 */
	private function argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}
	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	protected function createSign($para_sort) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);
		$mysign = md5($prestr.$this->key);
		return $mysign;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	private function createLinkstring($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".$val."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);
		
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
		
		return $arg;
	}
	/**
	 *支付请求表单
	*/


	public function buildRequestForm($para_temp, $method, $button_name) {
		//待请求参数数组
		
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway."' method='".$method."'>";
		while (list ($key, $val) = each ($para_temp)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='".$button_name."' style='display:none;'></form>";
		
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		
		return $sHtml;
	}
} 