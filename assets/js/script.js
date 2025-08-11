let selectedChatItem = null;
let currentClickedButton = null;
let decP = '';
let actionsProcessorLink = '/actions.php';


function registerUser() {
    // Get form elements
	const username = document.getElementById('usernameNA').value.trim();
    const email = document.getElementById('emailNA').value.trim();
    const password = document.getElementById('passwordNA').value;
    const confirmPassword = document.getElementById('confirmpasswordNA').value;
       
    // Validation checks

	if (!username) {
        showErrorInElement('loginErrorNA','Please enter your name. ');
        return;
    }
	else if (!email) {
        showErrorInElement('loginErrorNA','Please enter your email address.');
        return;
    }    
    else if (!password) {
        showErrorInElement('loginErrorNA','Please enter a password.');
        return;
    }
    else if (!confirmPassword) {
        showErrorInElement('loginErrorNA','Please confirm your password.');
        return;
    }
    
    // Email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showErrorInElement('loginErrorNA','Please enter a valid email address.');
        return;
    }
    
    // Password confirmation check
    if (password !== confirmPassword) {
        showErrorInElement('loginErrorNA','Passwords do not match.');
        return;
    }
    
    // Optional: Password strength validation
    if (password.length < 6) {
        showErrorInElement('loginErrorNA','Password must be at least 6 characters long.');
        return;
    }
    
	var result = false;
	httpCall({ email: email , newpass: password , username: username } ,
		(response) => {
			// Clear form after successful registration
			document.getElementById('usernameNA').value = '';
			document.getElementById('passwordNA').value = '';
			document.getElementById('confirmpasswordNA').value = '';
			document.getElementById('confirmpasswordNA').value = '';
		
			//Close modal
			closeModal('createAccountModal');
			result= true;
		},
		(response) => { //error response
			showErrorInElement('loginErrorNA',"Server error: " + response.status);
			result= false;
		}
	);

	return result;
}

function loadEditorContent(serverNoteId){
	var contentLoaded = false;

	try{
		httpCall({ otherActionType: 'loadContent' , noteId: serverNoteId },
			(response)=>{
				// Load the content into the editor
				if (response.data && response.data.length > 0) {
					// Assuming response.data contains the note content
					response.data.forEach((row) => {
						document.getElementById("message-editor").value = row.TND_DATA;
					});
				} else {
					// If no content, clear the editor
					document.getElementById("message-editor").value = '';
				}
				contentLoaded = true;
			},
			(response) => {
				alert("Failed to load note : " + response.message);
				contentLoaded = false;
			}
		);
		return contentLoaded;
	}
	catch (error) {
		alert("Error occurred while loading note content: " + error.message);
		return false;
	}
	
}

function updateNoteListButton(buttonElementObj, isEncrypted, title ){
	if ( buttonElementObj){
		if ( isEncrypted === "true"){
			buttonElementObj.innerHTML = '<i class="fa-solid fa-lock lock-icon"></i> ';

			if ( title) 
				buttonElementObj.innerHTML =  buttonElementObj.innerHTML + title;
		}
		else{
			if ( title)
				buttonElementObj.textContent = title;
		}

		buttonElementObj.dataset.isEncrypted = isEncrypted;
	}
}

function addNoteListElement(serverId, title, isEncrypted){
	const chatList = document.getElementById("chat-list");
	const newChatItem = document.createElement("li");

	// Create the title text and lock icon if encrypted
	updateNoteListButton(newChatItem, isEncrypted, title);

	newChatItem.addEventListener("click", function() {
		//document.getElementById("message-editor").value = title;
		if (!this.classList.contains("selected")) {
			if ( this.dataset.isEncrypted === "true"){
				currentClickedButton = this;
				openModal('passwordModal');
			}
			else{
				if (loadEditorContent(serverId)){
					if (selectedChatItem) {
						selectedChatItem.classList.remove("selected");
					}
					selectedChatItem = newChatItem;
					selectedChatItem.classList.add("selected");
					document.getElementById('message-editor').style.display='block';
					decP='';
				}
				else{
					alert("Failed to load text content");
				}
			}
		}
	});
	
	newChatItem.dataset.serverId = serverId; //response.rowId;
	chatList.appendChild(newChatItem);
}

function addNote() {
	const newChatInput = document.getElementById("new-chat-input");
	const newChatText = newChatInput.value.trim();
	
	const errorTextId = "addNoteError";
	
	if (newChatText) {
		ajaxCall({ title: newChatText },
			(response)=> {
				if (response.success) {						
					addNoteListElement(response.rowId,newChatText);
					newChatInput.value = "";
					closeModal('myModal');
				} else {
					showErrorInElement(errorTextId, response.message);
				}
			},
			() => {
				showErrorInElement(errorTextId, "Unknown error occurred 2 during note creation");
			}
		);
		
	}
	else{
		showErrorInElement(errorTextId, "Please enter title for note.");
	}
}

function onBtn_encryptNote_Click(){
	if(selectedChatItem) 
		openModal('passwordModalEnc'); 
	else 
		alert('Select note first');
}

function onBtn_deleteNote_Click(){
	if (selectedChatItem) {
		document.getElementById("confirmMessage").innerText = 'Are you sure you want to delete note ' + selectedChatItem.textContent;
		openModal('confirmModal');		
	}
	else{
		alert("No item selected");
	}
}

function onBtn_saveNote_Click(){
	if (selectedChatItem){
		if ( selectedChatItem.dataset.isEncrypted === 'true'){
			if ( decP && decP != ''){
				var textData = document.getElementById('message-editor');
				var nonEcryptedText = textData.value;
				try{
					var encryptedText = TextEncryption.encrypt(textData.value, decP);
					textData.value = encryptedText;

					if (saveNote('true')){
						//alert('Encrypted note data saved');
					}
					else{
						alert('Error saving encrypted note');
					}
				}
				catch(e){
					alert('Error saving encrypted note' + e.message);
				}

				textData.value = nonEcryptedText;
			}
		}
		else{
			saveNote('false');
		}
	}
	else{
		alert('Select note first');
	}
}

function saveNote(isEncrypted){
	if (selectedChatItem) {
		if ( selectedChatItem.textContent.length > 1 ){
			var textData = document.getElementById('message-editor').value;
			var result =false;

			httpCall({ otherActionType: 'saveNote', serverId: selectedChatItem.dataset.serverId, textData:textData, isEncrypt:isEncrypted },
				(response) => {
					if (response.success) {
						alert("Changes saved successfully for note " + selectedChatItem.textContent);
						result = true;
					} else {
						alert("Failed to save note : " + response.message);
						result= false;
					}
				},
				(message)=>{
					alert("Critical error: Failed to save note");
					result = false;
				}
			);
			
		}
	}
	else{
		alert("No note selected");
		result = false;
	}
	return result;
}

function deleteNote() {
	if (selectedChatItem) {
		ajaxCall({ otherActionType: 'deleteNote', serverId: selectedChatItem.dataset.serverId  },
			(response) => {
				if (response.success) {
					selectedChatItem.remove();
					selectedChatItem = null;
					closeModal('confirmModal');
					document.getElementById('message-editor').style.display='none';
					document.getElementById('message-editor').value='';
				} else {
					alert("Failed to delete note : " + response.message);
				}
			},
			()=>{
				alert("Critical error: Failed to delete note");
			}
		)
	}
	else{
		alert("No note selected");
	}
}

function loadNoteList(){

	ajaxCall({ otherActionType: 'listData' } ,
		(response)=>{
			if (response.success) {
				response.data.forEach((row) => {
					addNoteListElement(row.TND_ID,row.TND_TITLE,row.TND_IS_ENCRYPTED);
				});
			} else {
				console.log("An error occurred");
			}
		},
		() => {
			console.log("An error occurred");
		}
	);
}

function editNote() {
	if (selectedChatItem) {
		const newTitle = document.getElementById("newTitleInput");

		var result = false;
		httpCall({ otherActionType:'editNoteTitle' , serverId:selectedChatItem.dataset.serverId, titleData:newTitle.value } ,
			(response) => {

				//Update title in notes list
				updateNoteListButton(selectedChatItem, selectedChatItem.dataset.isEncrypted, newTitle.value);
				
				//Clear provided title data
				newTitle.value = '';

				//Close modal
				closeModal('editNoteModal');
				result= true;
			},
			(response) => { //error response
				showErrorInElement('loginErrorNA',"Server error: " + response.status);
				result= false;
			}
		);

		return result;
	}
	else{
		alert("No item selected");
	}
}

function encryptNote(){
	var textData = document.getElementById('message-editor');
	var pass = document.getElementById('enc_password');
	var nonEcryptedText = textData.value;
	var noteTitle = selectedChatItem.textContent;

	if (pass.value && textData.value){
		try{
			var encryptedText = TextEncryption.encrypt(textData.value, pass.value);
			textData.value = encryptedText;
			if (saveNote('true')){
				textData.value = '';
				textData.style.display='none';

				//Lock current selected note
				selectedChatItem.dataset.isEncrypted = 'true';
				selectedChatItem.classList.remove("selected");
				updateNoteListButton(selectedChatItem,"true",noteTitle);

				selectedChatItem = null;
				pass.value= '';
				closeModal('passwordModalEnc');
			}
			else{
				textData.value = nonEcryptedText;
			}

		}
		catch (e) {
			textData.value = nonEcryptedText;
			alert('Error encrypting note. ' + e.message);
		}

	}
	else{
		alert('You need to provide some password and note data');
	}
}

function decryptNote(){

	var textData = document.getElementById('message-editor');
	var pass = document.getElementById('dec_password');
	var contentLoaded =false;

	if (pass.value){
		// Load encrypted note content
		try{
			httpCall({ otherActionType: 'loadContent' , noteId: currentClickedButton.dataset.serverId },
				(response)=>{
					// Load the content into the editor
					if (response.data && response.data.length > 0) {
						// Assuming response.data contains the note content
						var encryptedText = response.data[0].TND_DATA;
						try{
							var decryptedText = TextEncryption.decrypt(encryptedText, pass.value);
							textData.value = decryptedText;
							textData.style.display='block';
							
							if (selectedChatItem) {
								selectedChatItem.classList.remove("selected");
							}

							selectedChatItem = currentClickedButton;
							//selectedChatItem.dataset.isEncrypted = 'false';
							selectedChatItem.classList.add("selected");
							decP = pass.value;
							pass.value = '';
							closeModal('passwordModal');
							contentLoaded = true;
						}
						catch(e){
							alert('Wrong password, decryption failed.' + e.message);
							contentLoaded = false;
						}						
					} 
					contentLoaded = true;
				},
				(response) => {
					alert("Failed to load note : " + response.message);
					contentLoaded = false;
				}
			);

			return contentLoaded;
		}
		catch (error) {
			alert("Error occurred while loading note content: " + error.message);
			return false;
		}
	}
	else{
		alert('Please enter password for decryption');
	}

}

function toggleTheme() {
	document.body.classList.toggle("light-theme");
}

function login() {
	
	const email = document.getElementById("email").value.trim();
	const password = document.getElementById("password").value.trim();
	if (email && password) {
		httpCall({ email: email, password: password }, 
			(successResponse) =>{
				if (successResponse.success) {
					localStorage.setItem('csrf_token', successResponse.csrf_token);
					document.getElementById("loggedInUser").firstChild.textContent = email + " ";
					loadNoteList();
					document.getElementById("email").value = '';
					document.getElementById("password").value = '';
					closeModal('loginModal');
				} else {
					showErrorInElement("loginError", successResponse.message );
				}
			},
			(message)=>{
				showErrorInElement("loginError", "An error occurred. " + message );
			}
		);

	} else {
		showErrorInElement("loginError", "Please enter email and password" );
	}
	
}

function logout(){
	ajaxCall({ otherActionType: 'logout' },
		(response) => {
			if (response.success) {						
				const chatList = document.getElementById("chat-list");
				const itemCount = chatList.children.length;
				if ( itemCount > 0){
					while (chatList.firstChild) {
						chatList.removeChild(chatList.firstChild);
					}
				}

				localStorage.clear();
				document.getElementById("loggedInUser").firstChild.textContent = "";
				openModal("loginModal");

			} else {
				alert( "Failed to logout" + response.message);
			}
		},
		() => {
			alert ("Unknown error during logout");
		}
	);
}

function sendResetLink() {
	const forgetEmail = document.getElementById("forget-email").value.trim();
	if (forgetEmail) {
		alert('Not yet implemented');
		/*
		$.ajax({
			url: actionsProcessorLink, // Replace with your actual API endpoint
			type: 'POST',
			data: JSON.stringify({ email: forgetEmail }),
			contentType: 'application/json',
			success: function(response) {
				if (response.success) {
					console.log("Reset link sent");
					closeForgetPasswordModal();
					alert("Reset link sent to your email");
				} else {
					console.log("Failed to send reset link");
					document.getElementById("forgetError").innerText = "Failed to send reset link";
				}
			},
			error: function() {
				console.log("An error occurred");
				document.getElementById("forgetError").innerText = "An error occurred";
			}
		});
		*/
	} else {
		console.log("Please enter your email");
		document.getElementById("forgetError").innerText = "Please enter your email";
	}
}

function closeModal(modalElementId) {
	console.log("Closing main modal");
	document.getElementById(modalElementId).style.display = "none";
}

function openModal(modalElementId) {
	console.log("Opening main modal");
	document.getElementById(modalElementId).style.display = "block";
}

function showErrorInElement(elementId, message){
	console.log(message);
	document.getElementById(elementId).innerText = message;
	setTimeout(() => {
		document.getElementById(elementId).innerText = '';
	}, 4000);
}




function ajaxCall(jsonParametersArray, successFunc, errorFunc){
	$.ajax({
		url: actionsProcessorLink,
		type: 'POST',
		data: JSON.stringify(jsonParametersArray),
		contentType: 'application/json',
		success: function(response) {
			successFunc(response);
		},
		error: function() {
			errorFunc();
		}
	});
}

function httpCall(jsonParametersArray, successFunc, errorFunc){
	// Perform login request to server
	const xhr = new XMLHttpRequest();
	
	// Make synchronous request (false parameter makes it synchronous)
	xhr.open('POST', actionsProcessorLink, false);
	xhr.setRequestHeader('Content-Type', 'application/json');
	
	// Send the request
	xhr.send(JSON.stringify(jsonParametersArray));
	
	// Check if request was successful
	if (xhr.status === 200) {

		const response = JSON.parse(xhr.responseText);
		
		if (response.success) {
			successFunc(response);
		} else {
			errorFunc(response);
		}
		
	} else {
		return errorFunc({ message : "Error with httpCall"});
	}
}

function simpleHash(str) {
	let hash = 0;
	if (str.length === 0) return hash;
	for (let i = 0; i < str.length; i++) {
	  const char = str.charCodeAt(i);
	  hash = ((hash << 5) - hash) + char;
	  hash = hash & hash; // Convert to 32bit integer
	}
	return Math.abs(hash).toString(16);
  }
  
function xorEncrypt(text, key) {
	let result = '';
	for (let i = 0; i < text.length; i++) {
		result += String.fromCharCode(
		text.charCodeAt(i) ^ key.charCodeAt(i % key.length)
		);
	}
	return result;
}
  
function stringToBase64(str) {
	return btoa(unescape(encodeURIComponent(str)));
}
  
function base64ToString(base64) {
	return decodeURIComponent(escape(atob(base64)));
}

  // Simple encryption class
  var TextEncryption = {
	encrypt: function(text, password) {
	  try {
		// Create a more complex key from password
		var hashedPassword = simpleHash(password + 'salt123');
		var key = hashedPassword + password + hashedPassword.split('').reverse().join('');
		
		// Add random prefix for additional security
		var randomPrefix = Math.random().toString(36).substring(7);
		var textWithPrefix = randomPrefix + '|' + text;
		
		// XOR encrypt
		var encrypted = xorEncrypt(textWithPrefix, key);
		
		// Base64 encode
		return stringToBase64(encrypted);
	  } catch (error) {
		throw new Error('Encryption failed: ' + error.message);
	  }
	},
	
	decrypt: function(encryptedData, password) {
	  try {
		// Create the same key from password
		var hashedPassword = simpleHash(password + 'salt123');
		var key = hashedPassword + password + hashedPassword.split('').reverse().join('');
		
		// Base64 decode
		var encrypted = base64ToString(encryptedData);
		
		// XOR decrypt
		var decrypted = xorEncrypt(encrypted, key);
		
		// Remove random prefix
		var parts = decrypted.split('|');
		if (parts.length < 2) {
		  throw new Error('Invalid encrypted data');
		}
		
		return parts.slice(1).join('|'); // Rejoin in case original text had |
	  } catch (error) {
		throw new Error('Decryption failed: ' + error.message);
	  }
	}
  };
