<?php
header('Content-Type: application/json');

// Start the session
session_start();
$dbFilename = 'db';

function logToFile($message){
	$txt = 'Call from' . ',' . $_SERVER['REMOTE_ADDR'] . ',' . date("d/m/Y") . ',' . date("h:i:sa") . ',' . $message;
	$myfile = file_put_contents('temp.log', $txt.PHP_EOL , FILE_APPEND);
}

function runQuery($sqlQuery){
	global $dbFilename;
	$myPDO = new PDO('sqlite:' . dirname(__FILE__) . '/' . $dbFilename);
	$tableData = $myPDO->query($sqlQuery);	
	$myPDO = null; //Disconnect
	
	return $tableData;
}

function runPreparedStatementQuery($sqlQuery, $params = []) {
	global $dbFilename;
	
	try {
		$myPDO = new PDO('sqlite:' . dirname(__FILE__) . '/' . $dbFilename);
		$myPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		if (empty($params)) {
			// For queries without parameters
			$tableData = $myPDO->query($sqlQuery);
		} else {
			// For queries with parameters (prepared statements)
			$stmt = $myPDO->prepare($sqlQuery);
			$stmt->execute($params);
			$tableData = $stmt;
		}
		
		$myPDO = null;
		return $tableData;
		
	} catch (PDOException $e) {
		echo "Database Error:<br>";
		var_dump([
			'error_message' => $e->getMessage(),
			'error_code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'sql_query' => $sqlQuery,
			'parameters' => $params
		]);
		
		$myPDO = null;
		return false;
	}
 }


 function runInsertQuery($sqlQuery){
	global $dbFilename;
	$myPDO = new PDO('sqlite:' . dirname(__FILE__) . '/' . $dbFilename);
	$tableData = $myPDO->query($sqlQuery);
	$lastRow = $myPDO->lastInsertId();
	$myPDO = null; //Disconnect
	
	return $lastRow;
}

function logout(){
	// Unset all session variables
	$_SESSION = array();

	// Destroy the session
	session_destroy();

	// Delete the session cookie
	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time() - 42000, '/');
	}

	echo json_encode(['success' => true, 'message' => 'Logout Successfull']);
}


try {

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		// Get the email and password from the request body
		$data = json_decode(file_get_contents('php://input'), true);

		$dateTime = date('Y-m-d H:i:s P T');
		
		if ( isset($data['title']) ){
			//Check if title is not empty
			if( trim($data['title']) == '' ){
				echo json_encode(['success' => false, 'message' => 'Title value is required']);
				exit;
			}
			
			//Check if title is already present
			$query = 'select * from TBL_USER_DATA , TBL_NOTES_DATA where TUD_ID = TND_TUD_ID_FK and TUD_EMAIL = "' . $_SESSION['email'] . '" and TND_TITLE = "' . $data['title'] . '"';
			$result = runQuery($query)->fetchAll(PDO::FETCH_ASSOC);
			if ( $result ){
				if ( count($result) > 0 ){
					echo json_encode(['success' => false, 'message' => 'Title already exist']);
					exit;
				}
			}
			

			$query = 'insert into TBL_NOTES_DATA(TND_TUD_ID_FK,TND_TITLE,TND_ADD_DATE) values ( (select TUD_ID from TBL_USER_DATA where TUD_EMAIL = "'
					  . $_SESSION['email'] . '"), "' . $data['title'] . '","' . $dateTime . '")';
			$result = runInsertQuery($query);
			
			echo json_encode(['success' => true, 'message' => "Success" , rowId => $result]);
			exit;
		}
		elseif ( isset($data['otherActionType']) ){
			
			// Get all notes list of titles and ids for current logged in user
			if ( isset($_SESSION['email']) && isset($_SESSION['userSid'] ) ){
				if ( $data['otherActionType'] == 'listData' ){ //Return all notes list data
					$query = 'select TND_ID,TND_TITLE, (CASE WHEN TND_IS_ENCRYPTED IS NULL OR TND_IS_ENCRYPTED = "" THEN "false" ELSE TND_IS_ENCRYPTED END) TND_IS_ENCRYPTED  from TBL_NOTES_DATA where TND_TUD_ID_FK = ' . $_SESSION['userSid'];
					$result = runQuery($query)->fetchAll(PDO::FETCH_ASSOC);
					if ( $result ){
						if ( count($result) > 0 ){
							echo json_encode(['success' => true, 'message' => "Unknown action type" , 'data' => $result ]);			
							exit;
						}
					}
					
					echo json_encode(['success' => true, 'message' => "Unknown action type" , 'data' => '' ]);			
					exit;
				}
				elseif ( $data['otherActionType'] == 'saveNote' ){

					$result = runPreparedStatementQuery("update TBL_NOTES_DATA set TND_DATA = ?, TND_LAST_UPDATE = ?, TND_IS_ENCRYPTED = ? where TND_TUD_ID_FK = ? and TND_ID = ? "
						, [$data['textData'], $dateTime ,$data['isEncrypt'], $_SESSION['userSid'], $data['serverId']]);

					if ($result === false){
						echo json_encode(['success' => false, 'message' => "Unknown server side error" , 'data' => '' ]);
						exit;
					}
					
					echo json_encode(['success' => true, 'message' => "Saved Successfully" , 'data' => '' ]);
					exit;
				}
				elseif ( $data['otherActionType'] == 'editNoteTitle' ){
					
					$result = runPreparedStatementQuery("update TBL_NOTES_DATA set TND_TITLE = ? where TND_ID = ?"
						, [$data['titleData'], $data['serverId']]);

					if ($result === false){
						echo json_encode(['success' => false, 'message' => "Unknown database error" , 'data' => '' ]);
						exit;
					}
					
					echo json_encode(['success' => true, 'message' => "Note edited" . $data['titleData'] . ' ' . $data['serverId'] , 'data' => '' ]);
					
					exit;
				}
				elseif ( $data['otherActionType'] == 'deleteNote' ){
					$query = 'delete from TBL_NOTES_DATA where TND_TUD_ID_FK=' . $_SESSION['userSid'] . ' and TND_ID=' . $data['serverId'];
					$result = runQuery($query);

					if ( $result)
						echo json_encode(['success' => true, 'message' => "Deletion Successfull" , 'data' => '' ]);	
					exit;
				}
				elseif ( $data['otherActionType'] == 'loadContent' && isset($data['noteId']) ){
					$query = 'select TND_DATA from TBL_NOTES_DATA where TND_TUD_ID_FK=' . $_SESSION['userSid'] . ' and TND_ID=' . $data['noteId'];
					$result = runQuery($query)->fetchAll(PDO::FETCH_ASSOC);;
					
					if ( $result)
						echo json_encode(['success' => true, 'message' => "Unknown action type" , 'data' => $result ]);
					exit;
				}
				elseif ($data['otherActionType'] == 'logout'){
					logout();
				}
				else{
					echo json_encode(['success' => false, 'message' => "Unknown action type" ]);			
					exit;
				}
			}
			else{
				echo json_encode(['success' => false, 'message' => "Session error" ]);			
				exit;
			}
		}
		elseif ( isset($data['email']) && isset($data['newpass'] ) && isset($data['username']) ){
			//Handle new user account creation

			//Check if user exists
			$result = runPreparedStatementQuery("SELECT * FROM TBL_USER_DATA WHERE TUD_EMAIL = ? ", [$data['email']])->fetchAll(PDO::FETCH_ASSOC);
			
			if ($result){
				if ( count($result) > 0 ){
					echo json_encode(['success' => false, 'message' => 'Email already exist']);
					exit;
				}
			}
			else{
				$username = $data['username'];
				$email = $data['email'];
				$passHash = password_hash($data['newpass'], PASSWORD_DEFAULT);
				$creationDate = date('Y-m-d H:i:s T');

				$sql = "INSERT INTO TBL_USER_DATA (TUD_NAME, TUD_EMAIL, TUD_PASSWORD, TUD_CREATION_DATE_TIME) VALUES (?, ?, ?, ?)";
    			$params = [$username,$email, $passHash, $creationDate];
				
				$result = runPreparedStatementQuery($sql, $params);
				if ($result){
					echo json_encode(['success' => true, 'message' => 'User with email ' + $email + ' created successfully']);
					exit;
				}
				else{
					echo json_encode(['success' => false, 'message' => 'Database Error: Account creation failed.']);
					exit;
				}
			}

			echo json_encode(['success' => false, 'message' => 'Unknown account creation error']);
			exit;

		}
		elseif ( isset($data['email']) && isset($data['password']) ){ //Handle login of user
			$email = $data['email'] ;
			$password = $data['password'];
			$loginDate = date('Y-m-d H:i:s T');

			// Validate the input
			if (empty($email) || empty($password)) {
				echo json_encode(['success' => false, 'message' => 'Email and password are required']);
				exit;
			}
			
			//Check if user exists
			$result = runPreparedStatementQuery("SELECT * FROM TBL_USER_DATA WHERE TUD_EMAIL = ?", 
						[$email])->fetchAll(PDO::FETCH_ASSOC);
			
			if ( $result){
				if ( count($result) > 0 ){
					if (password_verify($password,$result[0]["TUD_PASSWORD"])){
						// Generate a CSRF token
						$csrfToken = bin2hex(random_bytes(32));
						$_SESSION['csrf_token'] = $csrfToken;
						$_SESSION['email'] = $email;
						$_SESSION['userSid'] = $result[0]["TUD_ID"];

						//Update last login time
						$result = runPreparedStatementQuery("update TBL_USER_DATA set TUD_LAST_LOGIN=? where TUD_ID=?", [$loginDate,$result[0]["TUD_ID"]]);

						echo json_encode(['success' => true, 'message' => 'Login successful', 'csrf_token' => $csrfToken]);
						exit;
					}
				}
			}
			
			echo json_encode(['success' => false, 'message' => 'Email or password incorrect! ']);
			exit;
						
		}
    } 
	else {
        // Invalid request method
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (PDOException $e) {
    // Handle any errors
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>