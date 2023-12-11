<?php
class Server
{
    public $os, $config, $web_root_path;
    public $port = "8080";
    public $php = "8.1";
    
    public $worker = 20;
	public $routes = "";
    public $debug = false;
    public $phpini = true; // false if to use default php.ini or path to php.ini
    public $live_site = ""; // live site link, used load live site images to local
    public $configPath = "";

    function __construct()
    {
        $this->initalSetup();
        // get project root directory path
        $this->web_root_path = dirname(__FILE__);
        $this->getOs();
        $this->getRoutes();
        $this->server();
    }

    public function initalSetup(){
        $this->configPath = dirname(__FILE__).'/.server';
        if(!is_dir($this->configPath)){
            mkdir($this->configPath,0755, true);
        }
        if($this->phpini != false){
            $this->phpini = $this->configPath.'/php.ini';
            if(!file_exists($this->phpini)){
                file_put_contents($this->phpini,'');
            }
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

    public function getRoutes(){
        $route_content = '<?php

            if (php_sapi_name() !== "cli-server") {
                die("this is only for the php development server");
            }
            elseif (preg_match("/\.(?:png|jpg|jpeg|gif)$/", $_SERVER["REQUEST_URI"])) {';
        if($this->live_site != ''){
            $route_content.='header("Location: '.$this->live_site.'".$_SERVER["REQUEST_URI"], true, 301);
            return false;';
        }
        $route_content.='
            }
            // if needed, fix also "PATH_INFO" and "PHP_SELF" variables here...

            // require the entry point
            require "index.php";
        ?>';
        $this->routes = $this->configPath.'/routes.php';
        file_put_contents($this->routes,$route_content);
    }

    public function startWpServer(){
        $command = "PHP_CLI_SERVER_WORKERS=".$this->worker;
        $command .=" ENV=local";
        $php = exec('which php'.$this->php);
        $wp = exec('which wp');
        if(!$wp){
            exec("curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar");
            rename('wp-cli.phar',$this->configPath.'/wp-cli.phar');
            exec("chmod +x ".$this->configPath.'/wp-cli.phar');
            $wp = $this->configPath.'/wp-cli.phar';
        }

        if($this->debug == true){
            $debug = "-dxdebug.mode=debug -dxdebug.start_with_request=yes";
        }else{
            $debug = "";
        }
        $command = $command ." ".$php." ".$debug." ".$wp." --port=".$this->port;
        $command.=" --color";
        $command.=" --debug=bootstrap";
        $command.=" --skip-plugins";
        $command.=" --skip-themes";
        $command.=" server";
        // $command.= " server.php";
        // exec($command.' server.php');

        if($this->phpini != false){
            $command.= " --config=".$this->phpini;
        }
        // $command.= " --docroot= '".__DIR__."'";
        exec($command);
    }

    public function startServer(){
        $command = "PHP_CLI_SERVER_WORKERS=".$this->worker;
        $command.=" ENV=local";
        $php = exec('which php'.$this->php);
        $command = $command ." ".$php." -S localhost:".$this->port;
        if($this->phpini != false){
            $command = $command." -c ".$this->phpini;
        }

        //exec($command.' '.$this->routes);
        exec($command);
    }

    /***
     * @return void
     * config file for running in linux
     *
     */
    public function runServer(){
        if(file_exists(__DIR__.'/wp-load.php')){
            // THIS IS WP
            $this->startWpServer();
        }else{
            // THIS IS NOT WP
            $this->startServer();
        }
	    
    }

    public function server(){
        // check if apachectl is installed or not
        $output = exec("which php".$this->php);
        if($output){ //installed
	        $this->runServer();
        }else{ // not installed
            echo "Install php version ".$this->php."\n";
        }
        exit;
    }
}

$server = new Server();
