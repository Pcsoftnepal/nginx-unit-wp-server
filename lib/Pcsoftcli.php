<?php 

class PCSOFT_CLI{
    function __construct(){

    }
    
    // public function hello_world() {
	// 	//WP_CLI::line( 'Hello World!' );
	// }
    public function server(){
        $wp = exec("which wp");
        if(PHP_OS == "Darwin"): // is windows
            exec("{$wp} pcsoft run");
        elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'):
            $os = 'win';
        else:
            exec("sudo {$wp} pcsoft run --allow-root");
        endif;

        
    }

    public function run(){
        $web_root_path = rtrim(get_home_path(),'/');
        /**
         * check OS of the server
         */

        if(PHP_OS == "Darwin"): // is windows
            $os = 'mac';
        elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'):
            $os = 'win';
        else:
            $os = 'linux';
        endif;
        

        // check if nginx unit is installed or not
        $output = exec("which unitd");
        
        if(!$output){ // not installed
            
            if($os == 'linux'){

            }elseif($os == 'mac'){
                exec("export HOMEBREW_TEMP=/tmp");
                exec("brew update");

                echo exec('brew install unit');
                echo exec('brew install unit-php');
            }elseif($os == 'win'){

            }
        } //installed
            $upload_path = wp_upload_dir();
            $config = file_get_contents(dirname(__FILE__).'/wordpress.json');
            $config = str_replace('siteurl',$web_root_path,$config);
            $config = str_replace('website',apply_filters("pcsoft_live_website",""),$config);
            $tmp_config = $upload_path['basedir'].'/server.json';
            file_put_contents($tmp_config,$config);
            exec("chmod g+x ".$web_root_path);

            if($os == 'win'){

            }elseif($os == 'mac'){
                $os_type = php_uname();
                if(strpos('x86_64',$os_type) != -1){
                    $socket = "/usr/local/var/run/unit/control.sock";
                }else{
                    $socket = "/opt/homebrew/var/run/unit/control.sock";
                }
                
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