<?php 
include_once 'include/functions.php';
include_once 'include/connect.php';
sec_session_start(); 

if(login_check(dbConnect()) == true) {
	include_once('include/navbar.php');
	
        // Add your protected page content here!
?>


<script>
// set active menu bar 
$("#dashboard").removeClass("active");
$("#reports").removeClass("active");
$("#system").removeClass("active");
$('#tickets').addClass("active");

</script> 

<body>
<div id="content">

<?php
if (isset($_GET['ticketId'])){
	$rTicket = $_GET['ticketId'];
} elseif (isset($_POST['ticketId'])){
	$rTicket = $_POST['ticketId'];
} else {
	$rTicket = NULL;
}

error_log($rTicket);
if (isset($_POST['updateTicket'])) {
	$ticket = new ticket();
	$ticket->getTicket($rTicket);
	$ticket->setAssignedUser($_POST['assignedUser']);
	$ticket->setStatus($_POST['status']);
	if ($_POST['ticketNote'] != "") {
		$ticket->addNote($_POST['ticketNote']);
	}
	if ($_POST['previousAssignedUser'] != $_POST['assignedUser']) {
		$user = user::withUserName($_SESSION['username']);
		
		$system = new system();
		$settingName = 'system email';
		$system->getSystemSetting($settingName);
		$fromEmail = $system->getValue();
		
		$message = "You've been assigned a ticket!";
		$to = $user->getEmail();
		$subject="Ticketdesk - Ticket Assignment";
		$from = $fromEmail;
		$body ='Hi ' .$ticket->getAssignedUser() . ', <br/> <br/>Ticket id: '. $ticket->getId() .' has been assigned to you Click <a href="http://www.devmonkeyz.com/ticketdesk/tickets.php?ticketId='. $ticket->getId() . '">here</a> to view the ticket<br/>';
		$headers = "From: " . strip_tags($from) . "\r\n";
		$headers .= "Reply-To: ". strip_tags($from) . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		mail($to,$subject,$body,$headers);
	}
	if ($ticket->updateTicket()) {
	
		//echo 'ticket saved';
	} else {
		echo 'update failed: ' . $ticket->getMysqli()->error;
	}
	
	
/*
echo 'clientId = '. $ticket->getclientId();
echo '<br>user = '. $ticket->getUser();
echo '<br>category = '. $ticket->getCategoryId();
echo '<br>sub category = '. $ticket->getSubCategoryId();
echo '<br>TransferYn = '. $ticket->getTransferYn();
echo '<br>TransferDeptId = '. $ticket->getTransferDeptId();
echo '<br>OpenDate = '. $ticket->getOpenDate();
echo '<br>Assigned User = '. $ticket->getAssignedUser();
echo '<br>';
	*/
	
	// add note
	
}
?>

	<div id="displayTickets" class="panel panel-default">
        	<div class="panel-heading">Tickets</div>
        	<?php
        	if (isset($rTicket)) {
        	
	        	
        	}

        	
        	if ($rTicket == 'all') {
        	
        		echo 'All tickets';
        		ticket::displayTickets('all');
        		
        	}
        	elseif ($rTicket == 'mine') {
        		echo 'My Tickets'; 
        		ticket::displayTickets('mine');
        	}
        	elseif ($rTicket == 'open') {
        		echo 'Open tickets';
        		ticket::displayTickets('Open');
        		
        	}
		elseif ($rTicket == 'woa') {
			echo 'Waiting on agent';
			ticket::displayTickets('Waiting on agent');
		}
		elseif ($rTicket == 'woc') {
			echo 'Waiting on client';
			ticket::displayTickets('Waiting on client');
			}
		elseif ($rTicket == 'closed') {
			echo 'closed';
			ticket::displayTickets('Closed');
		}
		else {
			echo '<div class="panel-body">';
        		$ticket = new ticket();
        		if (isset($rTicket)) {$ticket->getTicket($rTicket);}

        		$category = new category();
        		$category->getCategory($ticket->getCategoryId());
        		$subcat = new subCategory();
        		$subcat->getSubCategory($ticket->getSubCategoryId());        		        	        		
        		
        		// display and update notes...

        		echo '<div class="well">
				<form class="form" method="POST">
					<input type="text" name="ticketId" value="' .$ticket->getId() . '" hidden />
					<input type="text" name="previousAssignedUser" value="' .$ticket->getAssignedUser() . '" hidden />
			                <div class="form-group">
			                    <label for="ticketNumber" class="col-sm-2 control-label">Ticket#:</label>
			                    <div class="col-sm-10"> 
			                    	<input class="form-control" name="ticketNumber" type="text" disabled value="' . 
			                    	$ticket->getId() . '" />
			                    </div>
			                </div>
			                
			                <div class="form-group">
			                    <label for="clientNumber" class="col-sm-2 control-label">Client#:</label>
			                    <div class="col-sm-10"> 
			                    	<input class="form-control" name="clientNumber" type="text"  value="' . 
			                    	$ticket->getClientId() . '" />
			                    </div>
			                </div>	

			                <div class="form-group">
			                    <label for="comments" class="col-sm-2 control-label">Description:</label>
			                    <div class="col-sm-10"> 
			                    	<input class="form-control" name="comments" disabled type="text"  value="' . 
			                    	$ticket->getComments() . '" />
			                    </div>
			                </div>	

			                <div class="form-group">
			                    <label for="user" class="col-sm-2 control-label">Created By:</label>
			                    <div class="col-sm-10"> 
			                    	<input class="form-control" name="user" disabled type="text"  value="' . 
			                    	$ticket->getUser() . '" />
			                    </div>
			                </div>	


			                <div class="form-group">
			                    <label for="category" class="col-sm-2 control-label">Category:</label>
			                    <div class="col-sm-10"> 
			                    	<input class="form-control" name="category" disabled type="text"  value="' . 
			                    	$category->getName() . '" />
			                    </div>
			                </div>
			                
			                <div class="form-group">
			                    <label for="subCategory" class="col-sm-2 control-label">Sub Category:</label>
			                    <div class="col-sm-10"> 
			                    	<input class="form-control" name="subCategory" disabled type="text"  value="' . 
			                    	$subcat->getName() . '" />
			                    </div>
			                </div>	
			                
			                <div class="form-group">
			                    <label for="status" class="col-sm-2 control-label">Status:</label>
			                    <div class="col-sm-10"> 
			                        <select class="form-control" name="status">
			        			<option value="'.$ticket->getStatus().'">'.$ticket->getStatus().'</option>
			        			<option value="Closed">Closed</option>
			        			<option value="Open">Open</option>
			        			<option value="Waiting on Client">Waiting On Client</option>
			        			<option value="Waiting on Agent">Waiting On Agent</option>
			        			<option value="Waiting on 3rd Party">Waiting On 3rd Party</option>				        			
		        			</select>			                    	
			                    </div>
			                </div>			                			                				                		                		                
				
			                <div class="form-group">
			                    <label for="ticketNote" class="col-sm-2 control-label">Add Notes:</label>
			                    <div class="col-sm-10">
			                        <textarea class="form-control" rows="5" name="ticketNote" id="ticketNote"></textarea>
			                    </div>
			                </div>
			                
		        			
			                
			                <div class="form-group">
			                    <label for="assignedUser" class="col-sm-2 control-label">Assigned User:</label>
			                    <div class="col-sm-10">
			        		<select class="form-control" name="assignedUser">			        			
			        			<option value="' . $ticket->getAssignedUser(). '">' . $ticket->getAssignedUser() . '</option>';
			   				user::displayUserOptionList();
			   						
			   			echo'	 
			        		</select>
			                    </div>
			                </div>	
			                

					<button name="updateTicket" class="btn btn-primary" type="submit">Update</button>

			                    




					
	        	</div>
	        		
	        	</form>
	        	</div>';
	        	$ticket->getNotes();
		}
		
        	
        	?>
        </div>      

</div>
</body>

<?php
// end protected content
} else { 
        echo 'You are not authorized to access this page redirecting you to the <a href="./index.php">login page</a>.';
        echo '<META http-equiv="refresh" content="2;URL=./index.php">';        
}

?>