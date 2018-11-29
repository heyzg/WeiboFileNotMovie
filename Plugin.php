<?php
/**
 * （微博图床无视频版）将 Typecho 的附件上传至新浪微博云存储中，无需申请appid，不占用服务器大小，可永久保存，只需一个不会登录的微博小号即可。
 * @package WeiboFileNotMovie For Typecho
 * @author 二呆
 * @version 1.0.6
 * @link http://www.tongleer.com/
 * @date 2018-08-30
 */
require __DIR__ . '/include/Sinaupload.php';

class WeiboFileNotMovie_Plugin implements Typecho_Plugin_Interface{
    // 激活插件
    public static function activate(){
		//图片
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('WeiboFileNotMovie_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('WeiboFileNotMovie_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('WeiboFileNotMovie_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('WeiboFileNotMovie_Plugin', 'attachmentHandle');
        return _t('插件已经激活，需先配置微博图床的信息！');
    }

    // 禁用插件
    public static function deactivate(){
        return _t('插件已被禁用');
    }

    // 插件配置面板
    public static function config(Typecho_Widget_Helper_Form $form){
		//版本检查
		$version=file_get_contents('https://tongleer.com/api/interface/WeiboFile.php?action=updateWeiboFileNotMovie&version=6');
		$headDiv=new Typecho_Widget_Helper_Layout();
		$headDiv->html('版本检查：'.$version);
		$headDiv->render();
		
        $weibouser = new Typecho_Widget_Helper_Form_Element_Text('weibouser', null, '', _t('微博小号用户名：'), _t('备注：设置后可多尝试多上传几次，上传成功尽量不要将此微博小号登录微博系的网站、软件，可以登录，但不确定会不会上传失败，上传失败了再重新上传2次同样可以正常上传，如果小号等级过低，可尝试微博大号，插件可正常使用，无需担心。'));
        $form->addInput($weibouser->addRule('required', _t('微博小号用户名不能为空！')));

        $weibopass = new Typecho_Widget_Helper_Form_Element_Password('weibopass', null, '', _t('微博小号密码：'));
        $form->addInput($weibopass->addRule('required', _t('微博小号密码不能为空！')));
    }
	
    // 个人用户配置面板
    public static function personalConfig(Typecho_Widget_Helper_Form $form){
    }

    // 获得插件配置信息
    public static function getConfig(){
        return Typecho_Widget::widget('Widget_Options')->plugin('WeiboFileNotMovie');
    }

    // 删除文件
    public static function deleteFile($filepath){
        return true;
    }

    // 上传文件
    public static function uploadFile($file, $content = null){
        // 获取上传文件
        if (empty($file['name'])) return false;

        // 校验扩展名
        $part = explode('.', $file['name']);
        $ext = (($length = count($part)) > 1) ? strtolower($part[$length-1]) : '';
        if (!Widget_Upload::checkFileType($ext)) return false;

        // 获取插件配置
        $option = self::getConfig();
        $date = new Typecho_Date(Typecho_Widget::widget('Widget_Options')->gmtTime);

        // 上传文件
        $filename = $file['tmp_name'];
        if (!isset($filename)) return false;
		
		if(in_array($ext,array('gif','jpg','jpeg','png','bmp'))){
			$Sinaupload=new Sinaupload('');
			$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
			$result=$Sinaupload->upload($filename);
			$arr = json_decode($result,true);
			return array(
				'name'  =>  $file['name'],
				'path'  =>  $arr['data']['pics']['pic_1']['pid'],
				'size'  =>  $file['size'],
				'type'  =>  $ext,
				'mime'  =>  Typecho_Common::mimeContentType($filename)
			);
		}else{
			return array(
				'name'  =>  $file['name'],
				'path'  =>  '插件WeiboFile暂时只限上传gif、jpg、jpeg、png、bmp图片格式的附件',
				'size'  =>  $file['size'],
				'type'  =>  $ext,
				'mime'  =>  Typecho_Common::mimeContentType($filename)
			);
		}
    }

    // 上传文件处理函数
    public static function uploadHandle($file){
        return self::uploadFile($file);
    }

    // 修改文件处理函数
    public static function modifyHandle($content, $file){
        return self::uploadFile($file, $content);
    }

    // 删除文件
    public static function deleteHandle(array $content){
        self::deleteFile($content['attachment']->path);
    }

    // 获取实际文件绝对访问路径
    public static function attachmentHandle(array $content){
        $option = self::getConfig();
        return Typecho_Common::url($content['attachment']->path.'.jpg', 'https://ws3.sinaimg.cn/large/');
    }
}