<?php/* WWW - PHP micro-frameworkIndex gateway API handlerIndex gateway API handler takes all the input from GET, POST, FILES, SESSION and COOKIE variables and sends an API command to WWW_API. By default it returns JSON formatted data. This script also loads WWW_Logger, WWW_Limiter and WWW_Database for additional functionality. Sending www-command as an input variable (GET, POST and so on) will attempt to execute that command through API and return appropriate JSON encoded data. Other returned data formats are also possible to be used, if set by www-return-data-type, such as xml, text or serializedarray.Author and support: Kristo Vaher - kristo@waher.net*/// This functions file is not required, but can be used for system wide functions// If you want to include additional libraries, do so hererequire(__ROOT__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'functions.php');// API keys file is required when an API profile other than 'public' is used to make a requestrequire(__ROOT__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'api.keys.php');$config['api-keys']=$apiKeys;// State class is used by API and Factory created objects to keep track of request staterequire(__ROOT__.DIRECTORY_SEPARATOR.'engine'.DIRECTORY_SEPARATOR.'class.www-state.php');$state=new WWW_State($config);// If database name is set then database controller is loadedif($config['database-name']!=''){	// Including the required class and creating the object	require(__ROOT__.DIRECTORY_SEPARATOR.'engine'.DIRECTORY_SEPARATOR.'class.www-database.php');	$databaseConnection=new WWW_Database();		// Assigning database variables and creating the connection	$databaseConnection->type=$config['database-type'];	$databaseConnection->host=$config['database-host'];	$databaseConnection->username=$config['database-username'];	$databaseConnection->password=$config['database-password'];	$databaseConnection->database=$config['database-name'];	$databaseConnection->connect();		// Passing the database to State object	$state->setDatabaseConnection($databaseConnection);		// If Logger is defined, then database connection is passed to Logger as well	if(isset($logger)){		$logger->setDatabaseConnection($databaseConnection);	}	}// Since $_GET is not naturally parsed due to Apache redirect, it is re-created for use here$request=parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);parse_str($request,$_GET);// API is used to process all requests and it handles caching and API validationsrequire(__ROOT__.DIRECTORY_SEPARATOR.'engine'.DIRECTORY_SEPARATOR.'class.www-api.php');$api=new WWW_API($state);// All the data sent by the client is stored in this variable$inputData=array();// All the data sent by client is added hereif(isset($_POST) && !empty($_POST)){ $inputData+=$_POST; }if(isset($_GET) && !empty($_GET)){ $inputData+=$_GET; }if(isset($_FILES) && !empty($_FILES)){ $inputData+=$_FILES; }if(isset($_COOKIE) && !empty($_COOKIE)){ $inputData+=$_COOKIE; }if(isset($_SESSION) && !empty($_SESSION)){ $inputData+=$_SESSION; }// API requires the main command to be set, otherwise an error is returnedif(isset($inputData['www-command'])){	$inputData['www-command']=$inputData['www-command'];} else {	// This will simply make API return a message that it was unable to solve the command	// Instead of throwing an error here it is better for API to return data in the format the user expects	$inputData['www-command']='';}// Setting the data type that is used, can be 'html','json','txt'. File extension is basically accepted.if(isset($inputData['www-return-data-type'])){	$inputData['www-return-data-type']=$inputData['www-return-data-type'];} else {	// API Handler always uses JSON as the default data type	$inputData['www-return-data-type']='json';}// Output defines whether the result is considered an 'echo/print' and if Content-type header is returned// It is useful to turn output off when you only request specific string as a resultif(isset($inputData['www-output'])){	$inputData['www-output']=$inputData['www-output'];} else {	$inputData['www-output']=1;}// By default the input data is serialized with JSON, but other methods are possible// This is used for API validation and hash checks onlyif(isset($inputData['www-serializer'])){	$inputData['www-serializer']=$inputData['www-serializer'];} else {	$inputData['www-serializer']='json';}// If profile is set and is not public, then API profile hash validation will be taken into accountif(isset($inputData['www-profile']) && $inputData['www-profile']!='public'){	// Validation hash is required, since profile is set	if(isset($inputData['www-hash'])){			// This profile has to be set in /resources/api.keys.php or an error is thrown		$inputData['www-profile']=$inputData['www-profile'];				// API command is executed with all the data that was sent by the client, along with other www-* settings		$api->command($inputData['www-command'],$inputData+array('www-serializer'=>$inputData['www-serializer'],'www-output'=>$inputData['www-output'],'www-return-data-type'=>$inputData['www-return-data-type'],'www-profile'=>$inputData['www-profile'],'www-hash'=>$inputData['www-hash']));		} else {			// Request is logged and can be used for performance review later		if(isset($logger)){			$logger->profile=$inputData['www-profile'];			$logger->writeLog('403');		}				// Returning 403 data		header('HTTP/1.1 403 Forbidden');		echo '<h1>HTTP/1.1 403 Forbidden</h1>';		echo '<h2>API hash is not provided</h2>';		die();			}	} else {	// Public profile is used when profile is not set	$inputData['www-profile']='public';		// API command is executed with all the data that was sent by the client, along with other www-* settings	$api->command($inputData['www-command'],$inputData+array('www-serializer'=>$inputData['www-serializer'],'www-output'=>$inputData['www-output'],'www-return-data-type'=>$inputData['www-return-data-type']));	}?>