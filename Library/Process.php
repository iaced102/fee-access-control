<?php
namespace Library;
class Process{
    public static function returnAndContinue(){
        $size = ob_get_length();
        header("Content-Length: ". $size . "\r\n");
        ob_end_flush();
        flush();
        if(session_id()){
            session_write_close();
        } 
    }

    public static function respondAndContinue($data){
        // Buffer all upcoming output...
           ob_start();
        //    header('Content-Type: application/json');
   
           // Send your response.
           $data = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
           print $data;
           // Get the size of the output.
           $size = ob_get_length();
           // Disable compression (in case content length is compressed).
           // Set the content length of the response.
           header("Content-Length: {$size}");
           // Close the connection.
           header("Connection: close");
           // Flush all output.
           ob_end_flush();
           ob_flush();
           flush();
               
           if (is_callable('fastcgi_finish_request')) {
           /*
               * This works in Nginx but the next approach not
               */
           session_write_close();
           fastcgi_finish_request();
   
           return;
           }
       }
}