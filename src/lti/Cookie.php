<?php
namespace IMSGlobal\LTI;

class Cookie {
    public function get_cookie($state) {
        $state_data = \Illuminate\Support\Facades\DB::Table('lti1p3_login_state')
        ->where('state', $state)
        ->where('expires_at', '>', now())->first();
        return $state_data;
    }

    public function set_cookie($name, $value, $exp = 3600, $options = []) {
        $state = \Illuminate\Support\Facades\DB::Table('lti1p3_login_state')->insert(array(
            'state' => $value,
            'expires_at' => \Carbon\Carbon::now()->addSeconds($exp)
        ));
    
        return $this;
    }
}
?>


