<?php
class Vmestecdn{

    private $upload_uuid = null;

    private $SITE_URL = "https://vmestecdn.site";
    private $UPLOAD_URL = "/api/upload/";
    private $INFO_URL = "/api/info/";
    private $DELETE_URL = "/api/delete";
    private $boundary = null;

    private $result_upload = false;
    private $result_uuid = null;

    private $active = false;
    private $iframe = null;
    private $duration = 0;
    private $preview = null;

    private $delete_result = false;
    private $delete_error = null;

    function __construct($upload_uuid){
        $this->upload_uuid = $upload_uuid;
        $this->UPLOAD_URL = $this->SITE_URL . "/api/upload/";
        $this->INFO_URL = $this->SITE_URL . "/api/info/";
        $this->DELETE_URL = $this->SITE_URL . "/api/delete/";
        $this->boundary = "-------------".microtime(true);
    }

    function __destruct(){
        $this->upload_uuid = null;
        $this->boundary = null;
        $this->result_upload = null;
        $this->result_uuid = null;
        $this->active = null;
        $this->iframe = null;
        $this->duration = null;
        $this->preview = null;
        $this->delete_result = null;
        $this->delete_error = null;
    }

    function upload_chunk($name, $chunks_count, $current_chunk, $data){
        //$name .' '.$chunks_count." ".$current_chunk." ".strlen($data);

        $fields = array(
            'name' => $name,
            'uuid' => $this->upload_uuid,
            'chunk' => $current_chunk,
            'chunks' => $chunks_count
        );

        $post_data = $this->build_data_files($this->boundary, $fields, array($name=>$data));

        $r = $this->sendData($post_data);

        if($current_chunk+1==$chunks_count){
            return array(
                'result'=>'success',
                'uuid'=>$r
            );
        }
        if($r != "ok"){
            return array(
                'result'=>'error',
                'error'=>$r
            );
        }
    }

    function upload_file($fpath, $file_name = ""){
        if(!file_exists($fpath)){
            return "Upload file not exists ".$fpath;
        }

        $chunk_size = 1024*1024;
        $chunks_count = ceil(filesize($fpath) / $chunk_size);
        $current_chunk = 0;
        $current_chunk2 = 0;

        if($file_name == ""){
            $name = explode("/", str_replace("\\", "/", $fpath));
            $name = $name[count($name)-1];
        } else {
            $name = $file_name;
        }
        $file_uuid = '';

        $handle = fopen($fpath, "rb");
        
        while($current_chunk < filesize($fpath)){
            $contents = fread($handle, $chunk_size);

            $file_uuid = $this->upload_chunk($name, $chunks_count, $current_chunk2, $contents);

            $current_chunk += strlen($contents);
            $current_chunk2 += 1;
            fseek($handle, $current_chunk);
        }
        fclose($handle);

        if($file_uuid['result'] == "success"){
            $this->result_upload = true;
            $this->result_uuid = $file_uuid['uuid'];
        }

        return json_encode($file_uuid);
    }

    function upload_form($file){
        if(empty($file)){
            return json_encode(
                array(
                    'result'=>'error',
                    'error'=>'Upload file not exists'
                )
            );
        }

        $file_name = $file['name'];
        $files = $file['tmp_name']; 

        return $this->upload_file($files, $file_name);
    }

    function build_data_files($boundary, $fields, $files){
        $data = '';
        $eol = "\r\n";
    
        $delimiter = $boundary;
    
        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
                . $content . $eol;
        }
    
    
        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
                //. 'Content-Type: image/png'.$eol
                . 'Content-Transfer-Encoding: binary'.$eol
                ;
    
            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--".$eol;
    
    
        return $data;
    }

    function sendData($data){
        
        $headers = array(
            'Content-type: multipart/form-data; boundary='.$this->boundary,
            'Content-Length: '.strlen($data)
        ); 
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->UPLOAD_URL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($curl);
        
        curl_close($curl);
        return $result;
    }

    function result_upload(){
        return $this->result_upload;
    }

    function uuid_upload(){
        return $this->result_uuid;
    }

    function info($uuid){
        $data = file_get_contents($this->INFO_URL . $uuid);

        $info = json_decode($data, true);

        if($info['status'] == "success"){
            $this->active = $info['active'];
            $this->iframe = $info['iframe'];
            $this->duration = $info['duration'];
            $this->preview = $info['preview'];
        }

        return $data;
    }

    function getActive(){
        return $this->active;
    }

    function getIframe(){
        return $this->iframe; 
    }

    function getDuration(){ 
        return $this->duration; 
    }
    
    function getPreview(){
        return $this->preview;
    }

    function delete($video_uuid){
        $data = file_get_contents($this->DELETE_URL.$this->upload_uuid."/".$video_uuid);
        $result = json_decode($data, true);

        if($result['status'] == "success"){
            $this->delete_result = true;
        } else {
            $this->delete_result = false;
            $this->delete_result = $result['error'];
        }

        return $data;
    }

    function getResultDelete(){
        return $this->delete_result;
    }

    function getErrorDelete(){
        return $this->delete_error;
    }
}

?>
