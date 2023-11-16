<?php 

class PCSOFT_CLI{
    function __construct(){

    }
    
    // public function hello_world() {
	// 	//WP_CLI::line( 'Hello World!' );
	// }
    public function server(){
        $wp = exec("which wp");
        exec("sudo {$wp} pcsoft run --allow-root");
    }

    public function run(){
        $web_root_path = rtrim(get_home_path(),'/');
        /**
         * check OS of the server
         */
        $user_agent = $_SERVER["HTTP_USER_AGENT"];
        
        if(strpos($user_agent, "Win") !== FALSE): // is windows
            $os = 'win';
        elseif(strpos($user_agent, "Mac") !== FALSE): // is mac
            $os = 'mac';
        else: // is linux
            $os = 'linux';
        endif;

        // check if nginx unit is installed or not
        $output = exec("which unitd");
        if(!$output){ // not installed
            if($os == 'linux'){

            }elseif($os == 'mac'){
                exec('brew install unit unit-php');
            }elseif($os == 'win'){

            }
        }else{ //installed
            $upload_path = wp_upload_dir();
            $config = file_get_contents(dirname(__FILE__).'/wordpress.json');
            $config = str_replace('siteurl',$web_root_path,$config);
            $config = str_replace('website',apply_filters("pcsoft_live_website",""),$config);
            $tmp_config = $upload_path['basedir'].'server.json';
            file_put_contents($tmp_config,$config);
            $flags = explode('/',$web_root_path); 
            
            exec("chmod g+x ".$web_root_path);

            if($os == 'win'){

            }elseif($os == 'mac'){
                $socket = "/opt/homebrew/var/run/unit/control.sock";
                exec("brew services stop unit");
                exec("sudo pkill unitd");
                $user = "root";
                $group = "wheel";
            }elseif($os == 'linux'){
                $socket = "/var/run/control.unit.sock";
                exec("sudo systemctl stop unit");
                exec("sudo pkill unitd");
                $user = "root";
                $group = "root";
            }
            
            exec("sudo unitd --no-daemon --user ".$user." --group ".$group." --log /dev/stderr & sleep 2 && curl -X PUT --data-binary @".$tmp_config." --unix-socket ".$socket." http://localhost/config/");
            
        }
    }
}