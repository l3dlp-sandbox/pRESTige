<?php

/**
 * (c) 2019 Om Talsania
 * MIT License
 */

//error_reporting(E_ALL);
error_reporting(0);

set_time_limit(600);

$echolog[] = "";

define("REL_PATH", "../.."); //prod
//define("REL_PATH", "../../../.."); //dev

include('../index-auth.php');
$auth_val = authenticate('../');

$auth = !($auth_val) ? false : true;
define("ADMIN_MODE", $auth); //set to true to allow unsafe operations, set back to false when finished


define("PYTHON_OUT", "logs");
define("PYTHON_PID", "python.pid.config");

$python_ver = !empty($_POST["version"]) ? $_POST["version"] : ( !empty($_REQUEST["version"]) ? $_REQUEST["version"] : "3.6" );
if($python_ver == "2.7") $python_ver = "";
$pypy_ver = !empty($_POST["pypy_version"]) ? $_POST["pypy_version"] : ( !empty($_REQUEST["pypy_version"]) ? $_REQUEST["pypy_version"] : "7.1.1-beta" );

define("PYTHON_VER", $python_ver . "-" . $pypy_ver);

//define("PYTHON_ARCH", "x" . substr(php_uname("m"), -2)); //x86 or x64
define("PYTHON_ARCH", "x86_64"); //x86 or x64

define("PYTHON_FILE", "pypy" . PYTHON_VER . "-linux_" . PYTHON_ARCH . "-portable.tar.bz2");

//$url = 'https://bitbucket.org/squeaky/portable-pypy/downloads/pypy-7.1.1-linux_x86_64-portable.tar.bz2';
define("PYTHON_URL", "https://bitbucket.org/squeaky/portable-pypy/downloads/" .PYTHON_FILE);

define("PYTHON_DIR", __DIR__."/../python");

$python_host = !empty($_POST["host"]) ? $_POST["host"] : ( !empty($_REQUEST["host"]) ? $_REQUEST["host"] :  "localhost");
$python_port = (int)(!empty($_POST["port"]) ? $_POST["port"] : ( !empty($_REQUEST["port"]) ? $_REQUEST["port"] :  "49999"));

define("PYTHON_HOST", $python_host);
define("PYTHON_PORT", $python_port);



function python_install() {
	global $echolog;
	if(file_exists(PYTHON_DIR)) {
		python_error(405);
		$echolog[] = "Python is already installed.";
		return;
	}

	if(!file_exists(__DIR__.'/'.PYTHON_FILE)) {		
		$echolog[] = "Downloading Python from " . PYTHON_URL . ":";

		//CURL
		
		$fp = fopen(PYTHON_FILE, "w");
		flock($fp, LOCK_EX);
		$curl = curl_init(PYTHON_URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_FILE, $fp);

		$resp = curl_exec($curl);
		curl_close($curl);
		flock($fp, LOCK_UN);
		fclose($fp);
		$echolog[] = $resp === true ? "Done." : "Failed. Error: curl_error($curl)";
		

		/*
		passthru("curl -O -L " . PYTHON_URL,$resp);
		*/

		if($resp === 0){
		} else {
			if(file_exists(__DIR__.'/'.PYTHON_FILE)){
				unlink(__DIR__.'/'.PYTHON_FILE);
			}
		}
		
		
	
	}
	$echolog[] = "Installing Python:";
	
	if(file_exists(__DIR__ . "/python")){
	} else {
		passthru("mkdir python", $ret0);
	}

	//if(file_exists(__DIR__.'/'."pypy" . PYTHON_VER . "-linux-" . PYTHON_ARCH . "-portable"))
	passthru("tar -xjvf " . PYTHON_FILE . " -C python 2>&1", $ret1);
	if($ret1 === 0){
	} else {
		$echolog[] = "Could not complete extracting the bundle.";
	}
	//$extracted_dir = "/pypy" . PYTHON_VER . "-linux-" . PYTHON_ARCH . "-portable";
	$extracted_dir = "/python/py*";
	$cmd2 = "mv " . __DIR__ . $extracted_dir  . " " . PYTHON_DIR;	
	passthru($cmd2, $ret2);
	if($ret2 === 0){
		passthru("touch " . PYTHON_PID, $ret);		
		$echolog[] = $ret === 0 ? "Done." : "Failed. Error: $ret. Try putting python folder via (S)FTP, so that " . __DIR__ . "/python/bin/pypy exists.";
	} else {
		$echolog[] = "Could not move the bundle to desired location." . "Failed. Error: $ret. Try putting python folder via (S)FTP, so that " . __DIR__ . "/python/bin/pypy exists.";
	}

	$cmd4 = PYTHON_DIR . "/bin/pypy -m ensurepip";
	passthru($cmd4, $ret4);
	if($ret4 === 0){

	} else {
		$echolog[] = "Could not install pip. Please use the web terminal and execute python/bin/pypy -m ensurepip";
	}
	
//passthru("rm -f " . PYTHON_FILE, $ret);
	

}

function python_uninstall() {
	global $echolog;	
	if(!file_exists(PYTHON_DIR)) {
		python_error(503);
		$echolog[] = "Python is not yet installed. Please go to Administration panel to install it.";
		return;
	}
	$echolog[] = "Unnstalling Python:";
	passthru("rm -rfv " . PYTHON_DIR . " " . PYTHON_PID . "", $ret);
	passthru("rm -rfv python_modules", $ret);
	passthru("rm -rfv .pip", $ret);
	passthru("rm -rfv ". PYTHON_OUT ."", $ret);
	$echolog[] = $ret === 0 ? "Done." : "Failed. Error: $ret";
}

function python_start($file) {
	global $echolog;	
	if(!file_exists(PYTHON_DIR)) {
		python_error(503);
		$echolog[] = "Python is not yet installed. Please go to Administration panel to install it.";
		return;
	}
	$python_pid = intval(file_get_contents(PYTHON_PID));
	if($python_pid > 0) {
		python_error(405);
		$echolog[] = "Python is already running";
		return;
	}
	$file = escapeshellarg($file);
	$start = '/workspace';
	$startlen = strlen($start);
	$pos = strpos($file, '/workspace');
	$sub = substr($file, $pos + $startlen);
	$displayFile = "{{WORKSPACE}}" . $sub;
	$echolog[] = "Starting: python $displayFile";
	$python_pid = exec("PORT=" . PYTHON_PORT . " " . PYTHON_DIR . "/bin/pypy $file >" . PYTHON_OUT . " 2>&1 & echo $!");
	if($python_pid > 0){ 
		$echolog[] = "Done. PID=$python_pid"; 
	}
	else {
		python_error();
		$echolog[] = "Failed.";
	}
	file_put_contents(PYTHON_PID, $python_pid, LOCK_EX);
	sleep(1); //Wait for python to spin up
	$echolog[] = file_get_contents(PYTHON_OUT);
}

function python_stop() {
	global $echolog;	
	if(!file_exists(PYTHON_DIR)) {
		python_error(503);
		$echolog[] = "Python is not yet installed. Please go to Administration panel to install it.";
		return;
	}
	$python_pid = intval(file_get_contents(PYTHON_PID));
	if($python_pid === 0) {
		python_error(503);
		$echolog[] = "Python is not yet running. Please go to Administration panel to start it.";
		return;
	}
	$echolog[] = "Stopping Python with PID=$python_pid";
	$ret = -1;
	passthru("kill $python_pid", $ret);
	if($ret === 0){
		$echolog[] = "Done";
	} else {
		python_error();
		//$echolog[] = "Failed. Error: $ret";
	}
	file_put_contents(PYTHON_PID, '', LOCK_EX);
}

function python_pip($cmd, $prefix) {
	global $echolog;	
	if(!file_exists(PYTHON_DIR)) {
		python_error(403);
		$echolog[] = "Python is not yet installed. Please go to Administration panel to install it.";
		return;
	}
	
	$prefixbase = " --prefix " . __DIR__ . "/" . REL_PATH . "/ide/workspace/";
	
	if($prefix) {
		$prefixpassed = $prefix;
		if(endsWith($prefix, ".py")){
			$exp = explode("/", $prefix);
			array_pop($exp);
			$stripped = implode("/", $exp);
			$prefixpassed = $stripped;
		}
		$prefixcmd = $prefixbase . $prefixpassed;	
	} else {
		$prefixcmd = $prefixbase . "python";
	}
	
	$cmd = escapeshellcmd(PYTHON_DIR . "/bin/pip3 " /* . $prefixcmd */  . " -- $cmd");
	
	$echolog[] = "Running: $cmd";
	$ret = -1;
	exec($cmd, $out, $ret);
	if($ret === 0){
		$echolog[] = $out;
		$echolog[] = "Done";
	} else {
		python_error();
		$echolog[] = "Failed. Error: $ret. See <a href=\"pip-debug.log\">pip-debug.log</a>";
	}
	return;

}

function python_serve($path = "") {
	
	global $echolog;	
	if(!file_exists(PYTHON_DIR)) {
		//python_head();
		python_error(503);
		$echolog[] = "Python is not yet installed. Please go to Administration panel to install it.";
		//python_foot();
		return;
	}
	$python_pid = intval(file_get_contents(PYTHON_PID));
	if($python_pid === 0) {
		//python_head();
		python_error(405);
		$echolog[] = "Python is not yet running. Please go to Administration panel to start it.";
		//python_foot();
		return;
	}
		
	$curl = curl_init("http://" . PYTHON_HOST . ":" . PYTHON_PORT . "/$path");
	curl_setopt($curl, CURLOPT_HEADER, 1);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $headers = array();
        foreach(getallheaders() as $key => $value) {
                $headers[] = $key . ": " . $value;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
        if($_SERVER["REQUEST_METHOD"] === "POST") {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
        }
        if($_SERVER["REQUEST_METHOD"] === "PUT") {
		$putData = @file_get_contents('php://input');
		//curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($putData));
	}
	
 	$resp = curl_exec($curl);

	if($resp === false) {
		//python_head();
		python_error();
		$echolog[] = "Error requesting $path: " . curl_error($curl);
		return;
		//python_foot();
	} else {
		list($head, $body) = explode("\r\n\r\n", $resp, 2);

		$headarr = explode("\n", $head);
		foreach($headarr as $headval) {
			if($headval == "Transfer-Encoding: chunked") continue;
			header($headval);
		}
		echo $body;
	}
	 	
	curl_close($curl);
	
	
	exit();
}


function python_status() {
	global $echolog;	
	$result = array();
	if(!file_exists(PYTHON_DIR)) {
		$result["installed"] = false;
		$result["installationStatus"] = "Not Installed";
	} else {
		$result["installed"] = true;
		$result["installationStatus"] = "Installed";
	}
	$python_pid = intval(file_get_contents(PYTHON_PID));
	if($python_pid > 0) {
		$result["running"] = true;
		$result["processStatus"] = "Running";
	} else {
		$result["running"] = false;
		$result["processStatus"] = "Stopped";
	}
	echo json_encode($result);
	exit();
}


function python_head() {
	$echolog[] = '<!DOCTYPE html><html><head><title>Python.php</title><meta charset="utf-8"><body style="font-family:Helvetica,sans-serif;"><h1>Python.php</h1><pre>';
}

function python_foot() {
	$echolog[] = '</pre><p><a href="https://github.com/niutech/python.php" target="_blank">Powered by python.php</a></p></body></html>';
}

function python_api_head(){
	header('Content-Type: application/json');
}

function python_error($code){
	if (empty($code)) $code = 500;
	http_response_code($code);
}

function python_success($code){
	if (empty($code)) $code = 200;
	http_response_code($code);
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}


function python_dispatch() {
	global $echolog;	
	if(ADMIN_MODE) {
		
			
		
		
		
		

		//python_head();
		python_api_head();


		if($_FILES['file-0']){
			//print_r($_FILES['file-0']);
			file_put_contents($_FILES['file-0']['name'], $_FILES['file-0']);
			$echolog[] = "Successfully uploaded " . $_FILES['file-0']['name'];
			array_shift($echolog);
			echo json_encode($echolog);
			exit();
		};
			

		
		if($install = isset($_GET['install']) ? ($_GET['install']) : (isset($_POST['install']) ? ($_POST['install']) :  false)) {
			python_install();
		} elseif($uninstall = isset($_GET['uninstall']) ? ($_GET['uninstall']) : (isset($_POST['uninstall']) ? ($_POST['uninstall']) :  false)) {
			python_uninstall();
		} elseif($start = isset($_GET['start']) ? ($_GET['start']) : (isset($_POST['start']) ? ($_POST['start']) :  false)) {
			$serve_path = __DIR__ . '/' . REL_PATH . '/ide/workspace/' . $start;
			python_start($serve_path);
		} elseif($stop = isset($_GET['stop']) ? ($_GET['stop']) : (isset($_POST['stop']) ? ($_POST['stop']) :  false)) {
			python_stop();
		} elseif($pip = isset($_GET['pip']) ? ($_GET['pip']) : (isset($_POST['pip']) ? ($_POST['pip']) :  false)) {
			$prefix = isset($_GET['prefix']) ? ($_GET['prefix']) : (isset($_POST['prefix']) ? ($_POST['prefix']) :  false);
			python_pip($pip, $prefix);
		} elseif($pythonstatus = isset($_GET['status']) ? ($_GET['status']) : (isset($_POST['status']) ? ($_POST['status']) :  false)) {
			python_status();
		} else {
		 	$echolog[] = "You are in Admin Mode. Switch back to normal mode to serve your python app.";
		}		
		//python_foot();
	} else {
		python_api_head();

		if($path = isset($_GET['path']) ? ($_GET['path']) : (isset($_POST['path']) ? ($_POST['path']) :  false)) {
			python_serve($path);
		} else {
			python_serve();
		}
		
	}
	array_shift($echolog);
	echo json_encode($echolog);
}

python_dispatch();