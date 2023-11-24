<?php
#!/bin/php
class Server
{
    public $os, $config, $web_root_path;
    public $port = "8080";
    public $php = "7.4";
    public $daemon = false;
    public $live_site = "https://levhatorah.org"; // live site link, used load live site images to local

    function __construct()
    {
        $this->initalSetup();
        // get project root directory path
        $this->web_root_path = dirname(__FILE__);
        $this->getOs();
        $this->configJson();

    }

    /**
     * install php unit for selected version
     */
    public function installPhpUnit(){
        //Latest version of Unit as of this release
        $ver="1.31.1";
        exec("wget https://unit.nginx.org/download/unit-{$ver}.tar.gz");
        exec("tar xzf unit-$".$ver.".tar.gz");
        exec("cd unit-".$ver);
        exec("apt-get -y install libssl-dev");

        exec("./configure --prefix=/usr --state=/var/lib/unit --control=unix:/var/run/control.unit.sock \
    --pid=/var/run/unit.pid --log=/var/log/unit.log --tmp=/var/tmp --user=unit --group=unit \
    --tests --openssl --modules=/usr/lib/unit/modules --libdir=/usr/lib/x86_64-linux-gnu");

        exec("./configure php --module=php".$this->php." --config=php-config --lib-path=/usr/lib");
        exec("make php".$this->php);
        exec("make install");
    }

    public function initalSetup(){
        exec('sudo pkill unitd');
        $php = exec("which php".$this->php);
        if(!$php){
            if($this->os == 'linux'){
                // install php version
                echo exec("sudo apt-get update && apt -y install software-properties-common && add-apt-repository ppa:ondrej/php && apt-get update && apt -y install php".$this->php);
                // install php dev and embed
                echo exec("sudo apt install php".$this->php."-dev libphp".$this->php."-embed");

                #install php-unit
                $this->installPhpUnit();
            }
        }

        $php_ver = exec($php .' -v');
        if(strpos($this->php,$php_ver) > 0){

        }else{
            //exec("sudo update-alternatives --set php /usr/bin/php".$this->php);
        }

    }

    public function getOs(){
        if (PHP_OS == "Darwin"): // is windows
            $this->os = 'mac';
        elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'):
            $this->os = 'win';
        else:
            $this->os = 'linux';
        endif;
    }

    public function configJson(){
        $php = $this->php ? 'php'.$this->php :'';
        $this->config = '
        {
            "listeners": {
                "*:'.$this->port.'": {
                    "pass": "routes"
                }
            },

            "routes": [
            {
                "match": {
                    "uri": [
                        "*.php",
                        "*.php/*",
                        "/wp-admin/",
                        "!/wp-content/uploads/"
                    ]
                },

                "action": {
                    "pass": "applications/wordpress/direct"
                }
            },
            {
                "match":{
                    "uri":[
                        "/wp-content/uploads/*"
                    ]
                },
                "action":{
                    "share": "siteurl$uri",
                    "fallback":{
                        "return":301,
                        "location":"website$uri"
                    }
                }
            },
            {
                "action": {
                    "share": "siteurl$uri",
                    "fallback": {
                        "pass": "applications/wordpress/index"
                    }
                }
            }],

            "applications": {
                "wordpress": {
                    "type": "'.$php.'",
                    "processes": {
                        "max": 15,
                        "spare": 5,
                        "idle_timeout": 180
                    },
                "targets": {
                    "direct": {
                        "root": "siteurl"
                    },
                "index": {
                    "root": "siteurl",
                    "script": "index.php"
                }
            },
            "options": {
                "admin": {
                    "file":"siteurl/server/php.ini",
                    "memory_limit": "512M",
                    "max_execution_time":"0"
                },

                "user": {
                    "display_errors": "1"
                }
            }
        }
    }}';
        $this->config = str_replace('siteurl',$this->web_root_path,$this->config);
        $this->config = str_replace('website',$this->live_site,$this->config);

    }

    /***
     * @return void
     * config file for running in linux
     *
     */
    public function runInLinux(){
        $socket = "/var/run/control.unit.sock";
        exec("sudo systemctl stop unit");
        if(exec('pidof unitd')){
            echo $flag = exec("sudo pkill unitd");
        }

        $user = "root";
        $group = "root";
        if($this->daemon == false){
            $daemon_txt = "";
        }else{
            $daemon_txt = " --no-daemon ";
        }
        echo exec("sudo unitd ".$daemon_txt." --user " . $user . " --group " . $group . " --log /dev/stderr");
        sleep(2);
        echo exec("sudo curl -X PUT --data-binary '".$this->config."' --unix-socket " . $socket . " http://localhost/config/");
    }

    public function runInMac(){
        $os_type = php_uname();
        if (strpos('x86_64', $os_type) != -1) {
            $socket = "/usr/local/var/run/unit/control.sock";
        } else {
            $socket = "/opt/homebrew/var/run/unit/control.sock";
        }

        exec("brew services stop unit");
        exec("sudo pkill unitd");
        $user = exec("whoami");
        $group = "staff";
        exec("brew services stop unit");
        exec("sudo pkill unitd");
        echo exec("unitd --no-daemon --user " . $user . " --group " . $group . " --log /dev/stderr & sleep 2 && curl -X PUT --data-binary @" . $this->web_root_path.'/server.json' . " --unix-socket " . $socket . " http://localhost/config/");
    }

    private function installInMac(){
        echo exec("brew update");
        echo exec('brew install unit');
        echo exec('brew install unit-php');
    }

    private function installInLinux(){

    }

    public function server(){
        // check if nginx unit is installed or not
        $output = exec("which unitd");
        if($output){ //installed
            file_put_contents($this->web_root_path.'/server.json', $this->config);
            exec("sudo chmod g+x " . $this->web_root_path); //enable execute permission to project directory
            if($this->os == 'linux'){
                $this->runInLinux();
            }elseif($this->os == 'mac'){
                $this->runInMac();
            }
        }else{ // not installed
            if($this->os == 'linux'){

                // install script of unit and unit-php in linux
                $this->installInLinux();
            }elseif($this->os == 'mac'){

                // install script of unit and unit-php in mac
                $this->installInMac();
            }elseif ($this->os == 'win'){
                // TODO: install script on windows
            }
        }
        exit;
    }
}

$server = new Server();
$server->server();
