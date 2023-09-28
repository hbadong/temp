<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once FRAMEWORK_PATH."ext/phpmailer/PHPMailer.php";
require_once FRAMEWORK_PATH."ext/phpmailer/SMTP.php";
require_once FRAMEWORK_PATH."ext/phpmailer/Exception.php";

/**
    $config = array(
        'debug' => 0,
        'smtp' => $cfg['email_smtp'],
        'port' => $cfg['email_port'],
        'account' => $cfg['email_account'],
        'account_name' => $cfg['email_account_name'],
        'password' => $cfg['email_password'],
        'to' => '' ,    //收件人
        'title' => '',  //邮件标题
        'body' => '',  //邮件内容
    );
 */

class email{
    
    public function sendemail($config = array(), $to = array()){
        $mail = new PHPMailer(true);
        try {
            //服务器配置
            $mail->CharSet ="UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = isset($config['debug']) ? (int)$config['debug']:0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = $config['smtp'];                // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = $config['account'];                // SMTP 用户名  即邮箱的用户名
            $mail->Password = $config['password'];             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
            $mail->Port = $config['port'];                            // 服务器端口 25 或者465 具体要看邮箱服务器支持

            $mail->setFrom($config['account'], $config['account_name']);  //发件人
			
			if( empty($config['to']) && $to ){
				foreach($to as $toemail){
					$mail->addAddress($toemail);
				}
			}else{
				$mail->addAddress($config['to']);  // 收件人
			}
            
            $mail->addReplyTo($config['account'], $config['account_name']); //回复的时候回复给哪个邮箱 建议和发件人一致
            (isset($config['cc']) && $config['cc']) && $mail->addCC($config['cc']);                    //抄送
            (isset($config['bcc']) && $config['bcc']) && $mail->addBCC($config['bcc']);                 //密送

            //发送附件
            (isset($config['attachment']) && $config['attachment']) && $mail->addAttachment($config['attachment']);         // 添加附件
            // $mail->addAttachment('../thumb-1.jpg', 'new.jpg');    // 发送附件并且重命名

            //Content
            $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = $config['title'];
            $mail->Body    = $config['body'];
            $mail->AltBody = strip_tags($config['body']);

            $res = $mail->send();
            return $res;
        } catch (Exception $e) {
            return false;
            //echo '邮件发送失败: ', $mail->ErrorInfo;
        }
    }
}