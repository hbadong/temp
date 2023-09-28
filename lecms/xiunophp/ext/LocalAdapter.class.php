<?php
/**
 * 本地策略文件管理适配器
 */
class LocalAdapter{
    private $fileModel; //文件信息
    private $userModel;     //用户信息

    /**
     *
     * Array
    (
        [id] => 2
        [filename] => 17006016-经纬-Q161204.rar
        [filepath] => upload/article/20220626/1/1_Ck6eEenF_17006016-经纬-Q161204.rar.
        [uid] => 1
        [filesize] => 66338922
        [createtime] => 整型
    )
    Array
    (
        [id] => 1
        [policy_name] => 默认上传策略
        [policy_type] => local
        [server] => /Upload
        [bucketname] => 0
        [bucket_private] => 0
        [url] => http://cloudreve.org/public/uploads/
        [ak] => 0
        [sk] => 0
        [op_name] => 0
        [op_pwd] => 0
        [filetype] => []
        [mimetype] => 0
        [max_size] => 104857600
        [autoname] => 1
        [dirrule] => {date}/{uid}
        [namerule] => {uid}_{randomkey8}_{originname}
        [origin_link] => 1
    )
    Array
    (
        [uid] => 1
        [user_email] => admin@cloudreve.org
        [user_nick] => Admin
        [user_pass] => d8446059f8846a2c111a7f53515665fb
        [user_date] => 2022-06-26 20:19:29
        [user_status] => 0
        [user_group] => 1
        [group_primary] => 0
        [user_activation_key] => n
        [used_storage] => 66382842
        [two_step] => 0
        [delay_time] => 0
        [avatar] => default
        [profile] => 1
        [webdav_key] => d8446059f8846a2c111a7f53515665fb
        [options] =>
    )
     */
    public function __construct($file = array(), $user = array()){
        $this->fileModel = $file;
        $this->userModel = $user;
    }

    /**
     * 处理下载请求
     * @param array $speedInfo 限速和断点续传
     * @param array $sendFileOptions 文件数据发送模式 0:传统，1:X-Sendfile 推荐使用X-Sendfile以获得高效的文件传输，启用前请确保服务器安装X-Sendfile模块并在站点配置文件中启用。
     * @param boolean $isAdmin 是否为管理员请求
     * @return void
     */
    public function Download($speedInfo = array(), $sendFileOptions = array('sendfile'=>0, 'header'=>'X-Sendfile'), $isAdmin=false){
        $rangeTransfer = isset($speedInfo["range_transfer"]) ? $speedInfo["range_transfer"] : false; //断点续传及多线程下载
        $speedLimit = isset($speedInfo["speed"]) ? $speedInfo["speed"] : ''; //限速多少 kb/s
        //$sendFileOptions = Option::getValues(["download"]); //Array ( [sendfile] => 0 [header] => X-Sendfile )

        if(isset($this->userModel['groupid']) && $this->userModel['groupid'] < 6){
            $isAdmin = true;
        }

        if($sendFileOptions["sendfile"] == "1"){
            $this->sendFile(true,$sendFileOptions["header"]);
        }else{
            if($isAdmin){   //管理员不限速
                $speedLimit = "";
            }

            if($speedLimit === "0"){ // 0 表示禁止下载
                exit();
            }else if(empty($speedLimit)){   //不限速下载
                $this->outputWithoutLimit(true,$rangeTransfer);
                exit();
            }else if((int)$speedLimit > 0){ //限速下载
                $this->outputWithLimit($speedLimit,true);
            }
        }
    }

    /**
     * 使用Sendfile模式发送文件数据
     *
     * @param boolean $download 是否为下载请求
     * @param string $header    Sendfile Header
     * @return void
     */
    private function sendFile($download=false, $header="X-Sendfile", $filePath = ''){
		$filePath == '' && $filePath = ROOT_PATH . $this->fileModel["filepath"];   //下载文件绝对路径
		if(!is_file($filePath)){
            exit('File No Exists!');
        }
        $realPath = $filePath == '' ? ROOT_PATH . $this->fileModel["filepath"] : $filePath;
        if($header == "X-Accel-Redirect"){
            $filePath = $this->fileModel["filepath"];
        }
        if($download){
            $filePath = str_replace("\\","/",$filePath);
            if($header == "X-Accel-Redirect"){
                ob_flush();
                flush();
                echo "s";
            }
            //保证如下顺序，否则最终浏览器中得到的content-type为'text/html'
            //1,写入 X-Sendfile 头信息
            $pathToFile = str_replace('%2F', '/', rawurlencode($filePath));
            header($header.": ".$pathToFile);
            //2,写入Content-Type头信息
            $mime_type = self::getMimetypeOnly($realPath);
            header('Content-Type: '.$mime_type);
            //3,写入正确的附件文件名头信息
            $orign_fname = $this->fileModel["filename"];  //文件名
            $ua = $_SERVER["HTTP_USER_AGENT"]; // 处理不同浏览器的兼容性
            if (preg_match("/Firefox/", $ua)) {
                $encoded_filename = rawurlencode($orign_fname);
                header("Content-Disposition: attachment; filename*=\"utf8''" . $encoded_filename . '"');
            } else if (preg_match("/MSIE/", $ua) || preg_match("/Edge/", $ua) || preg_match("/rv:/", $ua)) {
                $encoded_filename = rawurlencode($orign_fname);
                header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
            } else {
                // for Chrome,Safari etc.
                header('Content-Disposition: attachment;filename="'. $orign_fname .'";filename*=utf-8'."''". $orign_fname);
            }
            exit;
        }else{
            $filePath = str_replace("\\","/",$filePath);
            header('Content-Type: '.self::getMimetype($realPath));
            if($header == "X-Accel-Redirect"){
                ob_flush();
                flush();
                echo "s";
            }
            header($header.": ".str_replace('%2F', '/', rawurlencode($filePath)));
            ob_flush();
            flush();
        }
    }

    /**
     * 无限速发送文件数据
     *
     * @param boolean $download 是否为下载
     * @param boolean $reload   是否支持断点续传
     * @return void
     */
    public function outputWithoutLimit($download = false, $reload = false, $filePath = ''){
        ignore_user_abort(false);
        $filePath == '' && $filePath = ROOT_PATH . $this->fileModel["filepath"];   //下载文件绝对路径
        if(!is_file($filePath)){
            exit('File No Exists!');
        }
        set_time_limit(0);
        session_write_close();
        $file_size = filesize($filePath);

        $ranges = $this->getRange($file_size);
        if($reload == 1 && $ranges!=null){
            header('HTTP/1.1 206 Partial Content');
            header('Accept-Ranges:bytes');
            header(sprintf('content-length:%u',$ranges['end']-$ranges['start']));
            header(sprintf('content-range:bytes %s-%s/%s', $ranges['start'], $ranges['end']-1, $file_size));
        }
        if($download){
            //header('Cache-control: private');
            header('Content-Type: application/octet-stream');//返回内容的类型，二进制流。
            Header ( "Accept-Ranges: bytes" );//请求范围的度量单位--字节
            header('Content-Length: '.filesize($filePath));//文件大小
            $encoded_fname = rawurlencode($this->fileModel["filename"]).'.'.$this->fileModel["filetype"];
            header('Content-Disposition: attachment;filename="'.$encoded_fname.'";filename*=utf-8'."''".$encoded_fname);
            header('Content-Transfer-Encoding: binary');//内容编码方式，直接二进制，不要gzip压缩
            header('Expires: 0');//过期时间
            header('Cache-Control: must-revalidate');//缓存策略，强制页面不缓存，作用与no-cache相同，但更严格，强制意味更明显
            header('Pragma: public');

//            $file = @fopen($filePath,"rb");
//            while(!feof($file)){
//                print(@fread($file, 10240));
//                ob_flush();
//                flush();
//            }
        }

        if(file_exists($filePath)){
            if(!$download){
                header('Content-Type: '.self::getMimetype($filePath));
                ob_flush();
                flush();
            }
            $fileObj = fopen($filePath,"rb");

            if($reload == 1){
                fseek($fileObj, sprintf('%u', $ranges['start']));
            }

            // 设置指针位置
            fseek($fileObj, 0);
            $chunk_size = 1024 * 1024 * 2; // 2MB
            //总的缓冲的字节数
            $sum_buffer = 0;
            while( !feof($fileObj) && $sum_buffer < $file_size){
                echo fread($fileObj,$chunk_size);
                $sum_buffer += $chunk_size;
            }
            fclose($fileObj);
        }else{
            exit('File No Exists!');
        }
    }

    /**
     * 有限速发送文件数据
     *
     * @param int $speed        最大速度
     * @param boolean $download 是否为下载请求
     * @return void
     */
    public function outputWithLimit($speed, $download = false, $filePath = ''){
        ignore_user_abort(false);
        $filePath == '' && $filePath = ROOT_PATH . $this->fileModel["filepath"];   //下载文件绝对路径
        set_time_limit(0);
        session_write_close();
        if($download){
            header('Cache-control: private');
            header('Content-Type: application/octet-stream');
            header('Content-Length: '.filesize($filePath));
            $encoded_fname = rawurlencode($this->fileModel["filename"]);
            header('Content-Disposition: attachment;filename="'.$encoded_fname.'";filename*=utf-8'."''".$encoded_fname);
            ob_flush();
            flush();
        }else{
            header('Content-Type: '.self::getMimetype($filePath));
            ob_flush();
            flush();
        }
        if(file_exists($filePath)){
            $fileObj = fopen($filePath,"r");
            while (!feof($fileObj)){
                echo fread($fileObj,round($speed*1024));
                ob_flush();
                flush();
                sleep(1);
            }
            fclose($fileObj);
        }
    }

    /**
     * 获取文件MIME Type
     *
     * @param string $path 文件路径
     * @return void
     */
    static function getMimetype($path){
        //FILEINFO_MIME will output something like "image/jpeg; charset=binary"
        $finfoObj	= finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfoObj, $path);
        finfo_close($finfoObj);
        return $mimetype;
    }

    /**
     * 获取文件MIME Type
     *
     * @param string $path 文件路径
     * @return void
     */
    static function getMimetypeOnly($path){
        //FILEINFO_MIME_TYPE will output something like "image/jpeg"
        $finfoObj	= finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfoObj, $path);
        finfo_close($finfoObj);
        return $mimetype;
    }

    /**
     * 获取断点续传时HTTP_RANGE头
     *
     * @param int $file_size 文件大小
     * @return void
     */
    private function getRange($file_size){
        if(isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])){
            $range = $_SERVER['HTTP_RANGE'];
            $range = preg_replace('/[\s|,].*/', '', $range);
            $range = explode('-', substr($range, 6));
            if(count($range)<2){
                $range[1] = $file_size;
            }
            $range = array_combine(array('start','end'), $range);
            if(empty($range['start'])){
                $range['start'] = 0;
            }
            if(empty($range['end'])){
                $range['end'] = $file_size;
            }
            return $range;
        }
        return null;
    }
}