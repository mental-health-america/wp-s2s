<?php
class iDropBox {

	private $auth = [
		"auth_token" => 'eKnhAR9EhQIAAAAAAABWvaCgBuWQqdaU5G8x_TfzGJKzZge8tDDlgGOJce_CBk_9',
		"app_key" => 'nhsmdpstq752hix',
	    "app_secret" => 'eapb88opxkgb7pa'
	];

	private $db_url = 'https://api.dropboxapi.com/2/';

	public function __construct() {
	}

	private function getAuthToken(){
	    $url = 'https://api.dropboxapi.com/oauth2/token?';

	    $data = [
	    	'code' => $this->auth['auth_code'],
	    	'grant_type' => 'authorization_code',
	    	'client_id' => $this->auth['app_key'],
	    	'client_secret' => $this->auth['app_secret'],
	    	'redirect_uri' => 'https://reports.ck.agency/dropbox-port/'
	    ];
	    $datastr = '';
	    foreach ($data as $k => $v) {
	    	$datastr .= $k.'='.$v.'&';
	    }
	    $datastr = rtrim($datastr, '&');

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $datastr);
	    $result = curl_exec($ch);
	    curl_close($ch);
	    // $r = json_decode($result);
	    return $result;
	}

	public function viewFiles($data=''){
	    $url = $this->db_url.'/files/list_folder';
	    $headers = array(
	        'Authorization: Bearer '.$this->auth['auth_token'],
	        'Accept: /',
	        'Content-Type: application/json'
	    );
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL,$url);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	    $result = curl_exec($ch);
	    curl_close($ch);
	    
	    return $result;
	}

	public function addFiles($data='',$file){
	    $url = 'https://content.dropboxapi.com/2/files/upload';
	    $headers = array(
	        'Authorization: Bearer '.$this->auth['auth_token'],
	        'Dropbox-API-Arg: '.json_encode($data),
	        'Content-Type: application/octet-stream'
	    );
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL,$url);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $file);
	    $result = curl_exec($ch);
	    curl_close($ch);
	    
	    return $result;
	}
}