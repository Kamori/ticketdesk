<?php

class ticket {

	// instance vars
    private $attributes;
	private $db;

	
	public function __construct($dbh=NULL) {
        if(!$dbh){
            global $dbh;
        }
        $this->db = $dbh;
   	}

    static public function requiredAttributes(){
        $attributes = array('client', 'subject', 'description', 'creator');
        return $attributes;
    }
    static public function availableAttributes(){
        $attributes = array('client', 'subject', 'description', 'category',
                        'subcategory', 'status', 'creator', 'assignee');
        return $attributes;
    }

    private function _validateTicket(array $details){
        $attributes = $this->requiredAttributes();
        foreach($attributes as $attribute){
            if(!array_key_exists($attribute, $details)){
                return false;
            }
        }
        return True;
    }

    private function _create(array $details){
        if(!$this->_validateTicket($details)){
            return False;
        }
        $attributes = $this->availableAttributes();
        foreach($attributes as $attribute){
            if(!array_key_exists($attribute, $details)){
                $details[$attribute] = NULL;
                print $details[$attribute];
            }
        }
        $this->attributes = $details;
        return $details;
    }

    public function getAttribute($attribute){
        return $this->attributes[$attribute];
    }

    public function createTicket(array $attributes)
    {
        if(!$this->_create($attributes)){
            return False;
        }
        # TODO: Update schema / change ticket2 to valid ticket table
        $results = $this->db->prepare("INSERT INTO ticket2 (clientid, creator, subject, description, category,
                                      subcategory, status, assignee) VALUES (:client, :creator, :subject, :description,
                                      :category, :subcategory, :status, :assignee);", $this->attributes);
        return $results;
    }





    ##### Below this is only for reference and not part of  the class refactor





   	/**
    	 * Adds the ticket to the database
   	 */
   	public function addTicket($isClosed) {
		$prep_stmt = "insert into tickets (clientNumber,user,categoryid,subcategoryid,comments,transferYn,transferDeptId,openDate,parentTicketId,assignedUser)" .
		 	     "values(?,?,?,?,?,?,?,NOW(),?,?)";
		$parentTicketId = 0;
		if ($insert_stmt = $this->mysqli->prepare($prep_stmt)) {
			$insert_stmt->bind_param('isiisiiis',$this->clientNumber,
						$this->user,
						$this->categoryId,
						$this->subCategoryId,
						$this->comments,
						$this->transferYn,
						$this->transferDeptId,
						$parentTicketId,
						$this->user);
			if (! $insert_stmt->execute()) {	
				return false;
			} else {
				$this->id = $this->mysqli->insert_id;
				$status = 'Open';
				if ($isClosed == true) { $status = 'Closed'; }				
				$prep_stmt = "insert into ticketstatus (status,statusdate,ticketid) values(?,NOW(),?)";
				if ($insert_stmt = $this->mysqli->prepare($prep_stmt)) {
					$insert_stmt->bind_param('si',$status, $this->id);
					
					if (! $insert_stmt->execute()) {	
						return false;
					} 
				}
			}
		} return true;   	
   	}
   	
    public static function fromTicketID($id){
		$ticket = ticket::getTicket($id);
		return $ticket;
	}
   	/**
   	 * get ticket by ticket Id
   	 */
   	public function getTicket($id) {
   		$this->id = $id;
		//$sql = "select * from tickets where id = " . $this->id ;
		$sql = "select
   			t.*,
			(select ts.status 
			 	from ticketstatus ts
			 		where ts.ticketid = t.id
					and ts.statusdate = (select max(ts2.statusdate)
				                            	from ticketstatus ts2
				                            	where ts2.ticketid = ts.ticketid)) as status
				from tickets t
					where id = " . $this->id;
		
		$result = $this->mysqli->query($sql);
		
		if ($result->num_rows > 0) {
			// output data of each row
			while($row = $result->fetch_assoc()) {
		       		$this->clientNumber = $row['clientnumber'];
		       		$this->user = $row['user'];
		       		$this->categoryId = $row['categoryid'];
		       		$this->subCategoryId = $row['subcategoryid'];
		       		$this->comments = $row['comments'];
		       		$this->transferYn = $row['transferyn'];
		       		$this->transferDeptId = $row['transferdeptid'];
		       		$this->openDate = $row['opendate'];
		       		$this->parentTicketId = $row['parentticketid'];
		       		$this->assignedUser = $row['assigneduser'];
		       		$this->status = $row['status'];	
			}
		} else {
			echo "No ticket with that id...";
		}   		
   		
   	}
 
   	
   	/**
   	 * update the ticket info 
   	 */
   	public function updateTicket() {
   		$prep_stmt = "update tickets 
   				set clientnumber = ?,
   				categoryid = ?,
   				subcategoryid = ?,
   				transferyn = ?, 
   				transferdeptid = ?,
   				parentticketid = ?,
   				assigneduser = ?
   					where id = ?";
		if ($update_stmt = $this->mysqli->prepare($prep_stmt)) {
			$update_stmt->bind_param('iiiiiisi', $this->clientNumber,
						$this->categoryId,
						$this->subCategoryId,
						$this->transferYn,
						$this->transferDeptId,
						$this->parentTicketId,
						$this->assignedUser,
						$this->id);
			if (! $update_stmt->execute()) {	
				return false;
			} else {
				//insert status update
				$sql = "insert into ticketstatus (ticketId,status,statusdate) values (?,?,now())";
				if ($insert_stmt = $this->mysqli->prepare($sql)) {
					$insert_stmt->bind_param('is',$this->id,$this->status);
					if (! $insert_stmt->execute()) {	
						return false;
					}
				}
			}
		}
		return true;   
   	} 
   	
   	/**
   	 * adds a ticket note to the ticket
   	 */
   	public function addNote($note) {
		//insert status update
		$sql = "insert into ticketnotes (ticketId,note,notedate,user) values (?,?,now(),?)";
		if ($insert_stmt = $this->mysqli->prepare($sql)) {
			$insert_stmt->bind_param('iss',$this->id,$note,$_SESSION['username']);
			if (! $insert_stmt->execute()) {	
				return false;
			}
		}
   	}
   	
   	/**
   	 * get all notes
   	 */
   	 public function getNotes() {
   	 	$sql = "select * from ticketnotes where ticketid =" . $this->id;
		$result = $this->mysqli->query($sql);
		
		if ($result->num_rows > 0) {
			// output data of each row
			echo '<table class="table"><th>Note</th><th>User</th><th>Date</th>';
			while($row = $result->fetch_assoc()) {
		       		echo '<tr><td>' .$row['note'] . 
		       		'</td><td>' . $row['user'] .
		       		'</td><td> ' . $row['notedate'] .
		       		'</td></tr>';
			}
			echo '</table>';
		} else {
			echo "No additional notes.";
		}  
   	 }

   	
   	/**
   	 * Gets the total number of tickets in the system
   	 *
   	 */
   	public static function getTicketCount() { 
   		$mysqli = dbConnect();
   		$sql = "select count(*) as numberOfTickets from tickets";
		$result = $mysqli->query($sql);
		
		if ($result->num_rows > 0) {
			// output data of each row
			while($row = $result->fetch_assoc()) {
		       		return $row['numberOfTickets'];
			}
		} else {
			echo "0";
		}  
		$mysqli->close();
   	
   	}
   	/**
   	 * gets the average number of tickets daily
   	 *
   	 */
   	 public static function getDailyAverage() { 
   	 	$numberOfTickets = ticket::getTicketCount();
   	 	$startDate = "";
   	 	
   		$mysqli = dbConnect();
   		$sql = "select min(opendate) as startDate from tickets";
		$result = $mysqli->query($sql);
		
		if ($result->num_rows > 0) {
			// output data of each row
			while($row = $result->fetch_assoc()) {
		       		$startDate = $row['startDate'];
			}
		} else {
			echo "0";
		} 
		$mysqli->close();
		$totalNumberOfDays = date('m/d/Y') - date($startDate);
		$average = round($numberOfTickets / $totalNumberOfDays);
		return $average;
   	
   	}
   	
   	
   	/**
   	 * displays lists of tickets
   	 *
   	 */
   	
   	public static function displayTickets($status) {
   		$mysqli = dbConnect();	
   		$sql = "select
   			t.id as ticketid,
			t.clientnumber, 
			t.comments, 
			t.assigneduser,
			(select ts.status 
			 	from ticketstatus ts
			 		where ts.ticketid = t.id
					and ts.statusdate = (select max(ts2.statusdate)
				                            	from ticketstatus ts2
			                            		where ts.ticketid = ts2.ticketid)) as status
				from tickets t
			 		 where (select ts.status 
			 			from ticketstatus ts
				 		where ts.ticketid = t.id
						and ts.statusdate = (select max(ts2.statusdate)
					                            	from ticketstatus ts2
				                            		where ts.ticketid = ts2.ticketid)) = '" . $status . "'";	
  		if ($status == 'all' ) {
			 $sql = "select
	   			t.id as ticketid,
				t.clientnumber, 
				t.comments, 
				t.assigneduser,
				(select ts.status 
				 	from ticketstatus ts
				 		where ts.ticketid = t.id
						and ts.statusdate = (select max(ts2.statusdate)
					                            	from ticketstatus ts2
				                            		where ts.ticketid = ts2.ticketid)) as status
					from tickets t";   	
		} elseif ($status == 'mine') {
			$sql = "select
	   			t.id as ticketid,
				t.clientnumber, 
				t.comments, 
				t.assigneduser,
				(select ts.status 
				 	from ticketstatus ts
				 		where ts.ticketid = t.id
						and ts.statusdate = (select max(ts2.statusdate)
					                            	from ticketstatus ts2
				                            		where ts.ticketid = ts2.ticketid)) as status
					from tickets t
						where t.assigneduser = '". $_SESSION['username'] ."'";
		}
				                            		                            			
		
				 
		$result = $mysqli->query($sql);
		echo '<table class="table"><th>Client#</th><th>Comments</th><th>Assigned</th><th>Status</th>';
		if ($result->num_rows > 0) {
			// output data of each row
			while($row = $result->fetch_assoc()) {
				if ($row['status'] == 'Closed') { $class = "btn btn-danger";}
				elseif ($row['status'] == 'Open') { $class = "btn btn-success";}
				elseif ($row['status'] == 'Waiting on Client') {$class = "btn btn-info";} 
				else { $class = "btn btn-warning"; }
		       		echo '<tr>
				       			<td>' .$row['clientnumber'] .'</td>
				       			<td>' . $row['comments']  .'</td>
				       			<td>' . $row['assigneduser'] . '</td>
				       			<td><form method="POST" action="./tickets.php">
				       				<input name="ticketId" value="' . $row['ticketid'] . '" type="text" hidden />
				       				<button type="submit" class="'.$class .'">'.$row['status'] .'</button></form>
				       			</td>
				       		</tr>';
			}
		} else {
			echo "No Tickets with status: " . $status;
		}
		echo '</table>'; 
		$mysqli->close();   	
   	
   	}
   	
   	/**
   	 * displays the most recent tickets
   	 */
   	public static function displayRecentTickets() {
   		$mysqli = dbConnect();
   		$sql = "select
   			t.id as ticketid,
			t.clientnumber, 
			t.comments, 
			t.assigneduser,
			(select ts.status 
			 	from ticketstatus ts
			 		where ts.ticketid = t.id
					and ts.statusdate = (select max(ts2.statusdate)
				                            	from ticketstatus ts2
			                            		where ts.ticketid = ts2.ticketid)) as status
				from tickets t
					order by opendate desc limit 5";
		$result = $mysqli->query($sql);
		echo '<table class="table"><th>Client#</th><th>Comments</th><th>Assigned</th><th>Status</th>';
		if ($result->num_rows > 0) {
			// output data of each row
			while($row = $result->fetch_assoc()) {
				$comments = $row['comments'];
				$comments = (strlen($comments) > 340) ? substr($comments, 0, 340) . '...' : $comments;
				if ($row['status'] == 'Closed') { $class = "btn btn-danger";}
				elseif ($row['status'] == 'Open') { $class = "btn btn-success";}
				elseif ($row['status'] == 'Waiting on Client') {$class = "btn btn-info";} 
				else { $class = "btn btn-warning"; }
		       		echo '<tr>
				       			<td>' .$row['clientnumber'] .'</td>
				       			<td>' . $comments  .'</td>
				       			<td>' . $row['assigneduser'] . '</td>
				       			<td><form method="POST" action="./tickets.php">
				       				<input name="ticketId" value="' . $row['ticketid'] . '" type="text" hidden />
				       				<button type="submit" class="'.$class .'">'.$row['status'] .'</button></form>
				       			</td>
				       		</tr>';
			}
		} else {
			echo "No Recent Tickets.";
		}
		echo '</table>'; 
		$mysqli->close();
   		
   	}
   	
	
	public function setId($id) {$this->id = $id;}
	public function setClientId($clientId) {$this->clientNumber = $clientId;}
	public function setUser($user) {$this->user = $user;}
	public function setStatus($status) {$this->status = $status;}
	public function setCategoryId($categoryId) {$this->categoryId = $categoryId;}
	public function setSubCategoryId($subCategoryId) {$this->subCategoryId = $subCategoryId;}
	public function setTransferYn($transferYn) {$this->transferYn = $transferYn;}
	public function setTransferDeptId($transferDeptId) {$this->transferDeptId = $transferDeptId;}
	public function setOpenDate($openDate) {$this->openDate = $openDate;}
	public function setParentTicketId($parentTicketId) {$this->parentTicketId = $parentTicketId;}
	public function setAssignedUser($assignedUser) {$this->assignedUser = $assignedUser;}
	
	public function getId() {return	 $this->id;}
	public function getClientId() {return $this->clientNumber;}
	public function getUser() {return $this->user;}
	public function getComments() {return $this->comments;}
	public function getStatus() {return $this->status;}
	public function getCategoryId() {return $this->categoryId;}
	public function getSubCategoryId() {return $this->subCategoryId;}
	public function getTransferYn() {return $this->transferYn;}
	public function getTransferDeptId() {return $this->transferDeptId;}
	public function getOpenDate() {return $this->openDate;}
	public function getParentTicketId() {return $this->parentTicketId;}
	public function getAssignedUser() {return $this->assignedUser;}
	public function getMysqli() {return $this->mysqli;}

} 

?>