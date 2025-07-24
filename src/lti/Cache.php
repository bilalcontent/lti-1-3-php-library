<?php
namespace IMSGlobal\LTI;

interface Cache {
    
     function get_launch_data($key);

     function cache_launch_data($key, $jwt_body);

     function cache_nonce($nonce);

     function check_nonce($nonce);
}
?>
