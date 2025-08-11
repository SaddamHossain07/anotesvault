<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANotesVault | Secure & Encrypted Online Note-Taking</title>

    <meta name="description" content="Secure your thoughts with ANotesVault. Store, manage, and encrypt your personal notes with security. Your private, online vault for confidential information.">
    <meta name="keywords" content="ANotesVault, secure notes, encrypted notes, online notepad, private journal, digital vault, secure note-taking, encrypted text editor, private notes app, notepad plus plus, notepad++">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://anotesvault.com/">

    <meta property="og:title" content="ANotesVault | Secure & Encrypted Online Note-Taking">
    <meta property="og:description" content="Secure your thoughts with ANotesVault. Store, manage, and encrypt your personal notes with client side encryption.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://anotesvault.com/">
    <meta property="og:image" content="logo.png">
    <meta property="og:site_name" content="ANotesVault">

    <meta name="twitter:card" content="summary_large_image">
    <!-- <meta name="twitter:site" content="@YOUR_TWITTER_HANDLE">
    <meta name="twitter:creator" content="@YOUR_TWITTER_HANDLE"> -->
    <meta name="twitter:title" content="ANotesVault | Secure & Encrypted Online Note-Taking">
    <meta name="twitter:description" content="Secure your thoughts with ANotesVault. Store, manage, and encrypt your personal notes with military-grade security.">
    <meta name="twitter:image" content="logo.png">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>

<?php

session_start();


function runQuery($sqlQuery){
	//$myPDO = new PDO('sqlite:cred.sqlite');
	$myPDO = new PDO("sqlite:". dirname( dirname(__FILE__) ) . "/testNotepad/db");
	$tableData = $myPDO->query($sqlQuery);	
	$myPDO = null; //Disconnect
	
	return $tableData;
}

if ( !isset($_SESSION['email']) ) 
	echo '<body onload="openModal(' . "'loginModal'" .')" >';
else
	echo '<body onload="loadNoteList()" >';
?>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
            <img src="favicon.ico" alt="Logo">
                <h3>ANotesVault</h3>
            </div>
            <div class="controls">
                <button onclick="openModal('myModal')" title="Add Note"><i class="fa-solid fa-plus"></i></button>
                <button onclick="onBtn_saveNote_Click()" title="Save Note"><i class="fa-solid fa-floppy-disk"></i></button>
                <button onclick="onBtn_deleteNote_Click()" title="Delete Selected Note"><i class="fa-solid fa-trash"></i></button>
                <button onclick="openModal('editNoteModal')" title="Edit Selected Note Title"><i class="fa-solid fa-edit"></i></button>
				<button onclick="onBtn_encryptNote_Click()" title="Encrypt Selected Note (Client Side Encryption)"><i class="fa-solid fa-lock"></i></button>
                <button onclick="toggleTheme()">Toggle Theme</button>
            </div>
			<h3>All Notes</h3>
            <div class="chats">
                <ul id="chat-list"></ul>
            </div>
            <div class="user" id="loggedInUser"><?php if ( isset($_SESSION['email'])) echo $_SESSION['email']; ?>
                <button type="button" class="btn btn-primary" onclick="logout()" style="margin-left: 0;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>
        <main class="main-content">
            <textarea class="editor" style="display: none;" id="message-editor" placeholder="Type your message here..." ></textarea>
        </main>
    </div>
	
	
	<!-- Messagebox Modal -->
	<div id="confirmModal" class="modal">
		<div class="modal-content">
			<span class="close" onclick="closeModal('confirmModal')">&times;</span>
			<h2>Confirmation</h2>
			<br>
			<p id="confirmMessage"></p>
			<br>
			<button onclick="deleteNote()">Yes</button>
			<button onclick="closeModal('confirmModal')">No</button>
		</div>
	</div>
	
	
    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <!-- <span class="close" onclick="closeLoginModal()">&times;</span> -->
            <h2>Login</h2>
            <input type="email" id="email" placeholder="Email" required>
            <input type="password" id="password" placeholder="Password" required>
            <button onclick="login()">Login</button>
            <button onclick="openModal('createAccountModal');">Create Account</button>
            <a href="https://github.com/yourusername/yourrepository" target="_blank" class="github-link">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
                </svg>
                View on GitHub
            </a>
            <!-- <a href="#" onclick="openModal('forgetPasswordModal')">Forget Password</a> -->
            <div id="loginError" class="error"></div>
        </div>
    </div>
    <!-- Create New Account Modal -->
    <div id="createAccountModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createAccountModal')">&times;</span>
            <h2>Create Account</h2>
            <input type="text" id="usernameNA" placeholder="Your Name" required>
            <input type="email" id="emailNA" placeholder="Email" required>
            <input type="password" id="passwordNA" placeholder="Password" required>
            <input type="password" id="confirmpasswordNA" placeholder="Confirm Password" required>
            <button onclick="registerUser()">Create Account</button>
            <!-- <a href="#" onclick="openModal('forgetPasswordModal')">Forget Password</a> -->
            <div id="loginErrorNA" class="error"></div>
        </div>
    </div>
    <!-- Forget Password Modal -->
    <div id="forgetPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('forgetPasswordModal')">&times;</span>
            <h2>Forget Password</h2>
            <input type="email" id="forget-email" placeholder="Email" required>
            <button onclick="sendResetLink()">Send Reset Link</button>
            <div id="forgetError" class="error"></div>
        </div>
    </div>
    <!-- Add note Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('myModal')">&times;</span>
            <h2>Add New Note</h2>
            <input type="text" id="new-chat-input" placeholder="Enter new note title..." maxlength="20">
            <button onclick="addNote()">Add Note</button>
			<div id="addNoteError" class="error"></div>
        </div>
    </div>
    <!-- Edit note Modal -->
    <div id="editNoteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editNoteModal')">&times;</span>
            <h2>Edit Note Title</h2>
            <input type="text" id="newTitleInput" placeholder="Enter new title..." maxlength="20">
            <button onclick="editNote()">Change Title</button>
			<div id="editNoteError" class="error"></div>
        </div>
    </div>
    <!-- Password Modal for Decryption -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('dec_password').value='';closeModal('passwordModal'); ">&times;</span>
            <h2>Decrypt Note</h2>
            <br>
            <p> Enter password to decrypt and load content of your selected note </p>
            <br>
            <input type="password" id="dec_password" placeholder="Password" required>
            <button onclick="decryptNote();">Decrypt</button>
            <div id="decryptError" class="error"></div>
        </div>
    </div>
    <!-- Password Modal for Encryption -->
    <div id="passwordModalEnc" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('enc_password').value='';closeModal('passwordModalEnc'); ">&times;</span>
            <h2>Encrypt Note</h2>
            <br>
            <p> Enter password to encrypt note content of your selected note </p>
            <br>
            <input type="password" id="enc_password" placeholder="Password" required>
            <button onclick="encryptNote()">Encrypt</button>
            <div id="encryptError" class="error"></div>
        </div>
    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/script.js"></script>
	
</body>
</html>