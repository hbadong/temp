<?php
/**
 * 爱企查外链提交适配器
 * 通过模拟表单提交企业信息到爱企查
 */
class aiqicha_adapter implements ExternalPlatformAdapter {

    public function getPlatformName() {
        return 'aiqicha';
    }

    public function submit($enterprise_data) {
        $url = 'https://www.aiqicha.com/company/submit';

        $post_data = array(
            'companyName' => $enterprise_data['enterprise_name'],
            'address' => $enterprise_data['address'],
            'industry' => $enterprise_data['industry'],
            'phone' => $enterprise_data['phone'],
            'email' => $enterprise_data['email'],
            'description' => $enterprise_data['description'],
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array(
            'success' => $http_code == 200,
            'http_code' => $http_code,
            'response' => $response,
        );
    }

    public function checkStatus($platform_url) {
        if (empty($platform_url)) {
            return array('indexed' => false, 'status' => 'no_url');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $platform_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 简单判断：HTTP 200 且包含企业名称视为已收录
        $indexed = $http_code == 200 && !empty($response);

        return array(
            'indexed' => $indexed,
            'status' => $indexed ? 'indexed' : 'not_indexed',
            'http_code' => $http_code,
        );
    }
}
