<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');
if (!isLoggedIn()) {
    $_SESSION['msg'] = "You must log in first";
    header('location: index.php');
} else {

    // Number of records per page
    $recordsPerPage = 10;

    // Get the current page from the URL or set a default page
    if (isset($_GET['page'])) {
        $currentPage = $_GET['page'];
    } else {
        $currentPage = 1;
    }

    // Handle date filter
    if (isset($_POST['startDate']) && isset($_POST['endDate'])) {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
        $filterCondition = "AND CreationDate BETWEEN '$startDate' AND '$endDate'";
    } else {
        $filterCondition = "";
    }

    // Calculate the offset after the filterCondition is set
    $offset = ($currentPage - 1) * $recordsPerPage;

    // Your existing SQL query
    $query = "SELECT * FROM patients WHERE patient_name LIKE '%$sdata%' $filterCondition ORDER BY CreationDate DESC LIMIT $offset, $recordsPerPage";
    $result = mysqli_query($con, $query);

    // Total records in the database
    $totalRecords = mysqli_num_rows(mysqli_query($con, "SELECT * FROM patients"));

    // Total pages
    $totalPages = ceil($totalRecords / $recordsPerPage);

    // ... Continue with the rest of your code ...


  ?>
<!DOCTYPE HTML>
<html>
<head>
<title>Patient List</title>

<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<!-- Custom CSS -->

<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.css">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.min.css">
<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 

<!-- font CSS -->
<!-- font-awesome icons -->
<link href="css/font-awesome.css" rel="stylesheet"> 
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.css">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.min.css">	
<!-- //font-awesome icons -->
 <!-- js-->
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/modernizr.custom.js"></script>
<!--webfonts-->
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>
<!--//webfonts--> 
<!--animate-->
<link href="../../extensions/css/animate.css" rel="stylesheet" type="text/css" media="all">
<script src="../../extensions/js/wow.min.js"></script>
	<script>
		 new WOW().init();
	</script>
<!--//end-animate-->
<!-- Metis Menu -->
<script src="../../extensions/js/metisMenu.min.js"></script>
<script src="../../extensions/js/custom.js"></script>
<link href="../../extensions/css/custom.css" rel="stylesheet">
<!--//Metis Menu -->
</head> 
<body class="cbp-spmenu-push">

	<!--Calender Pop up Modal    -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailsModalLabel">Booking Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="eventDetails"></div>
            </div>
        </div>
    </div>
</div>

<!--End of calender pop up modal-->
    

    
<!-- The Modal from diary -->

<div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Add Booking</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                 <form method="post" action="../../includes/insert.php" enctype="multipart/form-data">
                      
                    <div style="display:flex; width: 100%;">
                 
                    <div style="width: 100%;" class="form-group">
                        <label for="name">Name</label>
                        <select placeholder="First Name and Last Name" type="text" class="form-control" id="Name" name="Name" value="<?php if (isset($_POST['Name'])) { echo htmlentities($_POST['Name']); } ?>" required="true">
                            <option value="">Select Patient</option>
                            <?php
                            $query = mysqli_query($con, "SELECT * FROM patients ORDER BY CreationDate DESC");
                            while ($row = mysqli_fetch_array($query)) {
                                ?>
                                <option value="<?php echo $row['patient_name']; ?>"><?php echo $row['patient_name']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    </div>
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 50%;"
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="homeAddress">Modality</label>
                        <select type="text" class="form-control" id="modality" name="modality" value="<?php if (isset($_POST['modality'])) { echo htmlentities($_POST['modality']); } ?>" >
		                      	<option value="">Select Modality</option>
		                      	<?php $query=mysqli_query($con,"select * from modalities ORDER BY modality_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['modality_name'];?>"><?php echo $row['modality_name'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                    <div style="width: 50%; class="form-group">
                        <label for="dob">Study</label>
                        <select type="text" class="form-control" id="study" name="study" value="<?php if (isset($_POST['study'])) { echo htmlentities($_POST['study']); } ?>" >
		                      	<option value="">Study</option>
		                      	<?php $query=mysqli_query($con,"select * from clinic_scans ORDER BY scan_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['scan_name'];?>"><?php echo $row['scan_name'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                    <div style="width: 50%; padding: 0 16px 0 0;" class="form-group">
                        <label for="homeAddress">Scanned By</label>
                        <select type="text" class="form-control" id="scanned_by" name="scanned_by" value="<?php if (isset($_POST['scanned_by'])) { echo htmlentities($_POST['scanned_by']); } ?>">
                            <option value="">Scanned By</option>
                            <?php
                            $query = mysqli_query($con, "SELECT * FROM users WHERE user_type = 'admin' ORDER BY username ASC");
                            while ($row = mysqli_fetch_array($query)) {
                                ?>
                                <option value="<?php echo $row['username']; ?>"><?php echo $row['username']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    
                   
                    </div>
                    
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 80%;
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="phone_number">Room</label>
                      <select type="text" class="form-control" id="room" name="room" value="<?php if (isset($_POST['room'])) { echo htmlentities($_POST['room']); } ?>">
		                      	<option value="">Select Room</option>
		                      	<?php $query=mysqli_query($con,"select * from scan_rooms ORDER BY room_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['room_name'];?>"><?php echo $row['room_name'];?></option>
		                       <?php } ?> 
		           </select>                 
		           </div>
                    
                    <div class="form-group">
                        <label for="request form">Request Form</label>
                        <input type="file" name="filename"
								class="form-control" accept=".pdf"/>
                    </div>
                    
                    </div>
                    
                    
                    <div style="display:flex; width: 100%;">
                        <div style="width: 50%;
                      padding: 0 16px 0 0;" class="form-group" class="form-group">
                            <label for="Start Time">Start Time</label>
                            <input type="datetime-local" class="form-control appointment_time" id="start" name="start" value="<?php if (isset($_POST['start'])) { echo htmlentities($_POST['start']); } ?>" >
                        </div>
                        <div style="width: 100%;" class="form-group">
                            <label for="End Time">End Time</label>
                            <input type="datetime-local" class="form-control" id="end" name="end" value="<?php if (isset($_POST['end'])) { echo htmlentities($_POST['end']); } ?>" >
                        </div>
                    
                    </div>
                    
                   
                    
                 
                   <div class="modal-footer">
                <button id="submitForm" type="submit" name="submitForm" style="background-color:#01152a;border: none;color: white;padding: 7px 15px;border-radius: 16px;" type="button" >Add Booking</button>
                <button style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px;"  data-dismiss="modal">Close</button>

            </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--End of modal from Diary-->



<!--Modal for adding a doctor-->
    
<!-- The Modal from diary -->

<div class="modal fade" id="addDoctorButton" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Add Booking</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                 <form method="post" action="../../includes/insert.php" enctype="multipart/form-data">
                      
                    <div style="display:flex; width: 100%;">
                 
                    <div style="width: 100%;" class="form-group">
                        <label for="name">Name</label>
                        <select placeholder="First Name and Last Name" type="text" class="form-control" id="Name" name="Name" value="<?php if (isset($_POST['Name'])) { echo htmlentities($_POST['Name']); } ?>" required="true">
		                      	<option value="">Select Patient</option>
		                      	<?php $query=mysqli_query($con,"select * from patients ORDER BY patient_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['patient_name'];?>"><?php echo $row['patient_name'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    </div>
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 50%;"
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="homeAddress">Modality</label>
                        <select type="text" class="form-control" id="modality" name="modality" value="<?php if (isset($_POST['modality'])) { echo htmlentities($_POST['modality']); } ?>" >
		                      	<option value="">Select Modality</option>
		                      	<?php $query=mysqli_query($con,"select * from modalities ORDER BY modality_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['modality_name'];?>"><?php echo $row['modality_name'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                    <div style="width: 50%; class="form-group">
                        <label for="dob">Study</label>
                        <select type="text" class="form-control" id="study" name="study" value="<?php if (isset($_POST['study'])) { echo htmlentities($_POST['study']); } ?>" >
		                      	<option value="">Study</option>
		                      	<?php $query=mysqli_query($con,"select * from clinic_scans ORDER BY scan_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['scan_name'];?>"><?php echo $row['scan_name'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                    <div style="width: 50%;"
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="homeAddress">Scanned By</label>
                        <select type="text" class="form-control" id="scanned_by" name="scanned_by" value="<?php if (isset($_POST['scanned_by'])) { echo htmlentities($_POST['scanned_by']); } ?>" >
		                      	<option value="">Scanned By</option>
		                      	<?php $query=mysqli_query($con,"select * from users ORDER BY username ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['username'];?>"><?php echo $row['username'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                   
                    </div>
                    
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 80%;
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="phone_number">Room</label>
                      <select type="text" class="form-control" id="room" name="room" value="<?php if (isset($_POST['room'])) { echo htmlentities($_POST['room']); } ?>">
		                      	<option value="">Select Room</option>
		                      	<?php $query=mysqli_query($con,"select * from scan_rooms ORDER BY room_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['room_name'];?>"><?php echo $row['room_name'];?></option>
		                       <?php } ?> 
		           </select>                 
		           </div>
                    
                    <div class="form-group">
                        <label for="request form">Request Form</label>
                        <input type="file" name="filename"
								class="form-control" accept=".pdf" required/>
                    </div>
                    
                    </div>
                    
                    
                    <div style="display:flex; width: 100%;">
                        <div style="width: 50%;
                      padding: 0 16px 0 0;" class="form-group" class="form-group">
                            <label for="Start Time">Start Time</label>
                            <input type="datetime-local" class="form-control appointment_time" id="start" name="start" value="<?php if (isset($_POST['start'])) { echo htmlentities($_POST['start']); } ?>" >
                        </div>
                        <div style="width: 100%;" class="form-group">
                            <label for="End Time">End Time</label>
                            <input type="datetime-local" class="form-control" id="end" name="end" value="<?php if (isset($_POST['end'])) { echo htmlentities($_POST['end']); } ?>" >
                        </div>
                    
                    </div>
                    
                    
                   
                    
                 
                   <div class="modal-footer">
                <button id="submitForm" type="submit" name="submitForm" style="background-color:#01152a;border: none;color: white;padding: 7px 15px;border-radius: 16px;" type="button" >Add Booking</button>
                <button style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px;"  data-dismiss="modal">Close</button>

            </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--End of modal from Diary-->


<!--End of modal for adding a doctor-->


<!--Modal from Booking Button -->

<div class="modal fade" id="prebookingModal" tabindex="-1" role="dialog" aria-labelledby="prebookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="prebookingModalLabel">Booking Form</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                 <form method="post" action="../../includes/insert.php" enctype="multipart/form-data">
                      
                    <div style="display:flex; width: 100%;">
                 
                    <div style="width: 100%;" class="form-group">
                        <label for="name">Name</label>
                        <select placeholder="First Name and Last Name" type="text" class="form-control" id="Name" name="Name" value="<?php if (isset($_POST['Name'])) { echo htmlentities($_POST['Name']); } ?>" required="true">
                            <option value="">Select Patient</option>
                            <?php
                            $query = mysqli_query($con, "SELECT patient_name FROM patients ORDER BY CreationDate DESC ");
                            while ($row = mysqli_fetch_array($query)) {
                                ?>
                                <option value="<?php echo $row['patient_name']; ?>"><?php echo $row['patient_name']; ?></option>
                                <?php
                            }
                            ?>
                           
                        </select>
                    </div>
                    </div>
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 50%;"
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="homeAddress">Modality</label>
                        <select type="text" class="form-control" id="modality" name="modality" value="<?php if (isset($_POST['modality'])) { echo htmlentities($_POST['modality']); } ?>" >
		                      	<option value="">Select Modality</option>
		                      	<?php $query=mysqli_query($con,"select * from modalities ORDER BY modality_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['modality_name'];?>"><?php echo $row['modality_name'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                    <div style="width: 50%; class="form-group">
                        <label for="dob">Study</label>
                        <select type="text" class="form-control" id="study" name="study" value="<?php if (isset($_POST['study'])) { echo htmlentities($_POST['study']); } ?>" >
		                      	<option value="">Study</option>
		                      	<?php $query=mysqli_query($con,"select * from clinic_scans ORDER BY scan_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['scan_name'];?>"><?php echo $row['scan_name'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                    <div style="width: 50%; padding: 0 16px 0 0;" class="form-group">
    <label for="homeAddress">Scanned By</label>
    <select type="text" class="form-control" id="scanned_by" name="scanned_by" value="<?php if (isset($_POST['scanned_by'])) { echo htmlentities($_POST['scanned_by']); } ?>">
        <option value="">Scanned By</option>
        <?php
        $query = mysqli_query($con, "SELECT * FROM users WHERE user_type = 'admin' ORDER BY username ASC");
        while ($row = mysqli_fetch_array($query)) {
            ?>
            <option value="<?php echo $row['username']; ?>"><?php echo $row['username']; ?></option>
            <?php
        }
        ?>
    </select>
</div>
                    
                   
                    </div>
                    
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 80%;
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="phone_number">Room</label>
                      <select type="text" class="form-control" id="room" name="room" value="<?php if (isset($_POST['room'])) { echo htmlentities($_POST['room']); } ?>">
		                      	<option value="">Select Room</option>
		                      	<?php $query=mysqli_query($con,"select * from scan_rooms ORDER BY room_name ASC") ;
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['room_name'];?>"><?php echo $row['room_name'];?></option>
		                       <?php } ?> 
		           </select>                 
		           </div>
                    
                    <div class="form-group">
                        <label for="request form">Request Form</label>
                        <input type="file" name="filename"
								class="form-control" accept=".pdf"/>
                    </div>
                    
                    </div>
                    
                    
                    <div style="display:flex; width: 100%;">
                        <div style="width: 50%;
                      padding: 0 16px 0 0;" class="form-group" class="form-group">
                            <label for="Start Time">Start Time</label>
                            <input type="datetime-local" class="form-control appointment_time" id="start" name="start" value="<?php if (isset($_POST['start'])) { echo htmlentities($_POST['start']); } ?>" >
                        </div>
                        <div style="width: 100%;" class="form-group">
                            <label for="End Time">End Time</label>
                            <input type="datetime-local" class="form-control" id="end" name="end" value="<?php if (isset($_POST['end'])) { echo htmlentities($_POST['end']); } ?>" >
                        </div>
                    
                    </div>
                    
                     <!--<div style="display:flex; width: 100%;">-->
                     <!--   <div style="width: 100%;" class="form-group">-->
                     <!--       <label for="End Time">Status</label>-->
                     <!--      <ul style="list-style: none; padding: 0;">-->
                     <!--           <li style="display: inline-block; margin-left: 20px;">-->
                     <!--               <input type="checkbox" id="item1" name="color" value="green">-->
                     <!--               <label for="item1">Not Scanned</label>-->
                     <!--           </li>-->
                     <!--           <li style="display: inline-block; margin-left: 20px;">-->
                     <!--               <input type="checkbox" id="item2" name="color" value="blue">-->
                     <!--               <label for="item2">Scanned</label>-->
                     <!--           </li>-->
                     <!--           <li style="display: inline-block; margin-left: 20px;">-->
                     <!--               <input type="checkbox" id="item3" name="color" value="red">-->
                     <!--               <label for="item3">Waiting for Results</label>-->
                     <!--           </li>-->
                     <!--       </ul>-->

                     <!--   </div>-->
                    
                    </div>
                   
                    
                 
                   <div class="modal-footer">
                <button id="submitForm" type="submit" name="submitForm" style="background-color:#01152a;border: none;color: white;padding: 7px 15px;border-radius: 16px;" type="button" >Add Booking</button>
                <button style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px;"  data-dismiss="modal">Close</button>

            </div>
                </form>
            </div>
             </div>
    </div>
</div>

<!--End of modal from booking button-->


<!-- Modal for View Patient from the view next patient button -->
<div class="modal fade" id="viewPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPatientModalLabel">Details of Patient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table  class="table "> 
							<thead> <tr> 
								<th style="background: #01152a;color: #fff;">#</th> 
								<th style="background: #01152a;color: #fff;">Name</th>
								<th style="background: #01152a;color: #fff;">Study</th> 
							    <th style="background: #01152a;color: #fff;">Time</th>
								<th style="background: #01152a;color: #fff;">Action</th> </tr> </thead> 
								<tbody>
									<?php
									$ret=mysqli_query($con,"select *from events where status='Not Scanned'");
									$cnt=1;
									while ($row=mysqli_fetch_array($ret)) {

									?>

						 <tr> 
						 	<th scope="row"><?php echo $cnt;?></th> 
						 	 
						 	<td><?php  echo $row['Name'];?></td>
						 	<td><?php  echo $row['study'];?></td>
						 	<td><?php  echo $row['start_event'];?></td> 
						 	<td style="display:flex;">
						 		<button style="margin-right: 4px; border-radius: 20px;width: 55px;height: 32px;background-color: #ed1b24;color: #fff;
                                    border: none;"> <a style="color: #fff;" href="view-booking.php?viewid=<?php echo $row['id'];?>"><i class="fa fa-eye" aria-hidden="true"></i></a>
								</button>  
								
						 </td> 
						 </tr>   
						 	<?php $cnt=$cnt+1;}?>
						 </tbody> 
						</table> 
            </div>
            <div class="modal-footer">
                <button style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px;"  data-dismiss="modal">All My Patients</button>
                <!--<button style="background-color:#01152a;border: none;color: white;padding: 7px 15px;border-radius: 16px;" type="button" >All Attended Patients</button>-->
            </div>
        </div>
    </div>
</div>
<!-- End of Modal for View Patient from the view next patient button -->


<!-- Modal for Adding Patient from add patient button-->

<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPatientModalLabel">Details of Patient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="width: 100%;" class="modal-body">
               <form method="post" action="../../includes/save_patient.php">
                    <div style="display:flex; width: 100%;">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input list="titles" type="text" class="form-control" id="title" name="title" value="<?php if (isset($_POST['title'])) { echo htmlentities($_POST['title']); } ?>" >
                        <datalist id="titles">
                                      <option value="Mr">
                                      <option value="Mrs">
                                      <option value="Ms">
                                      <option value="Miss">
                                      <option value="Dr">
                                      <option value="Eng">
                                    </datalist>
                    </div>
                    <div style="width: 100%;padding: 0 0 0 16px;" class="form-group">
                        <label for="name">Name</label>
                        <input placeholder="First Name and Lastname" type="text" class="form-control" id="patient_name" name="patient_name" value="<?php if (isset($_POST['patient_name'])) { echo htmlentities($_POST['patient_name']); } ?>" >
                        <span id="nameCheckMessage" style="color: red;"></span>
                                    <?php echo display_error(); ?>	
                    </div>
                    </div>
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 100%;
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="homeAddress">Home Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php if (isset($_POST['address'])) { echo htmlentities($_POST['address']); } ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php if (isset($_POST['date_of_birth'])) { echo htmlentities($_POST['date_of_birth']); } ?>" >
                    </div>
                    
                    </div>
                    
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 100%;
                      padding: 0 16px 0 0;" class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php if (isset($_POST['phone_number'])) { echo htmlentities($_POST['phone_number']); } ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Gender</label>
                        <select placeholder="Gender" name="gender" id="gender" class="form-control" value="<?php if (isset($_POST['gender'])) { echo htmlentities($_POST['gender']); } ?>">
		                      	<?php $query=mysqli_query($con,"select * from Genders");
								              while($row=mysqli_fetch_array($query))
								              {
								              ?>
		                       <option value="<?php echo $row['GenderName'];?>"><?php echo $row['GenderName'];?></option>
		                       <?php } ?> 
		                      </select>
                    </div>
                    
                    </div>
                    
                    
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email_address" name="email_address" value="<?php if (isset($_POST['email_address'])) { echo htmlentities($_POST['email_address']); } ?>" >
                    </div>
                    
                    <div style="display:flex; width: 100%;">
                        <div style="width: 50%;
                      padding: 0 16px 0 0;" class="form-group" class="form-group">
                            <label for="employerName">Name of Employer</label>
                            <input type="text" class="form-control" id="employer_name" name="employer_name" value="<?php if (isset($_POST['employer_name'])) { echo htmlentities($_POST['employer_name']); } ?>" >
                        </div>
                        <div style="width: 100%;" class="form-group">
                            <label for="businessPhone">Business Phone</label>
                            <input type="tel" class="form-control" id="business_phone" name="business_phone" value="<?php if (isset($_POST['business_phone'])) { echo htmlentities($_POST['business_phone']); } ?>" >
                        </div>
                    
                    </div>
                    
                    <div style="display:flex; width: 100%;">
                   <div style="width: 50%;" class="form-group">
                        <label for="referringDoctor">Referring Doctor</label>
                        <select name="referring_doctor" id="referring_doctor" class="form-control" value="<?php if (isset($_POST['referring_doctor'])) { echo htmlentities($_POST['referring_doctor']); } ?>">
                            <option value="">Select Doctor</option>
                            <?php
                            $query = mysqli_query($con, "select * from referring_doctor");
                            while ($row = mysqli_fetch_array($query)) {
                                ?>
                                <option value="<?php echo $row['name']; ?>"><?php echo $row['name']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                       
                    </div>
                    
                    <div style="width: 100%;" class="form-group">
                             <a href="add_doctor.php"> <button type="button" id="addDoctorButton" style="background-color: #01152a; border: none; color: white; padding: 7px 15px; border-radius: 16px;margin: 20px;" data-toggle="modal" data-target="#addDoctorModal">Add a New Doctor</button></a>
                        </div>
                    
                     </div>
                    
                   
                    
                 
                   <div class="modal-footer">
                <button id="submitpatientform" type="submit" name="submitpatientform" style="background-color: #01152a; border: none; color: white; padding: 7px 15px; border-radius: 16px;" type="button" data-loading-text="Submitting..." onclick="showLoadingSpinner()">Add Patient</button>                    
                <button id=submitbilling type="submit" style="background-color: #01152a; border: none; color: white; padding: 7px 15px; border-radius: 16px;" type="button">Proceed to Billing</button>
                    <button type="reset" style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px;">Reset</button> <!-- Add the "Reset" button -->
                    <button style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px;" data-dismiss="modal">Close</button>

            </div>
                </form>
            </div>
           
        </div>
    </div>
</div>

<!--End of Add patient modal from button-->


	<div class="main-content">
		<!--left-fixed -navigation-->
		<?php 
            include_once('../../includes/radiographer-heading.php');
            include_once('../../includes/radiographer-sidebar.php');
            
            ?>
		<!-- //header-ends -->
		<!-- main content start-->
		
		<div id="page-wrapper">
			<div class="main-page">
			    
			    
				<div class="tables">
					<h3 class="title1">Patient List</h3>
					
					 <?php
                            if(isset($_POST['search']))
                            { 
                            
                            $sdata=$_POST['searchdata'];
                              ?>
                         <h4 align="center">Result against "<?php echo $sdata;?>" keyword </h4> 

						<table class="table table-bordered"> <thead> <tr> <th>#</th> 
						<th>Patient ID</th> 
						<th>Name</th>
						<th>Mobile Number</th> 
						<th>Referred by</th>
						<th>Created on</th>
						<th>Action</th> 
						</tr> </thead> 
						<tbody>
                            <?php
                            $ret=mysqli_query($con,"select *from  patients where  patient_name like '%$sdata%'");
                            $num=mysqli_num_rows($ret);
                            if($num>0){
                            	 $cnt = $offset + 1;
                        while ($row = mysqli_fetch_array($ret)) {
                            
                            ?>
                            
                            

						 <tr> <th scope="row"><?php echo $cnt;?></th> 
						 <td><?php  echo $row['id'];?></td> 
						 <td><?php  echo $row['patient_name'];?></td>
						 <td><?php  echo $row['phone_number'];?></td>
						 <td><?php  echo $row['referring_doctor'];?></td>
						 <td><?php  echo $row['CreationDate'];?></td>
						 
						 <td>	<a href="view-booking.php"></a>
						 		<button style="margin-right: 4px; border-radius: 20px;width: 55px;height: 32px;background-color: #ed1b24;color: #fff;border: none;"> 
        						<a style="color: #fff;"href=view-patients.php?editid=<?php echo $row['id'];?>">
        							<i class="fa fa-eye" aria-hidden="true"></i>
        						</a>
								</button>  
								
								
						        
						       
						        <button style="border-radius: 20px;width: 67px;height: 32px;background-color: #01152a;color: #fff; border: none;">
                                                <a style="color: #fff;" href="#" onclick="deleteItem(<?php echo $row["id"]; ?>)">

                                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                                </a>
                                </button>
						        
						        </td> </tr>   <?php 
                        $cnt=$cnt+1;
                        } } else { ?>
                          <tr>
                            <td colspan="8"> No record found against this search</td>
                        
                          </tr>
                           
                      <?php } }?></tbody> 
                    </table> 
					
				
				
				<!--Table begins here-->
				
				
				
					<div class="table-responsive bs-example widget-shadow">
					
					
					<div style="margin:20px 0px 8% 0px">
					<form method="post" action="all-patients.php">
                        <div class="form-row">
                            <div class="col-md-4 mb-3">
                                <label for="startDate">Start Date:</label>
                                <input type="date" class="form-control" name="startDate" id="startDate" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="endDate">End Date:</label>
                                <input type="date" class="form-control" name="endDate" id="endDate" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                    
						</div>
						
						
						<table class="table table-bordered"> 
							<thead>
							 <tr> 
							 	<th style="background: #01152a;color: #fff;">#</th> 
							 	<th style="background: #01152a;color: #fff;">Patient Name</th> 
							 	<th style="background: #01152a;color: #fff;">Mobile</th> 
							 	<th style="background: #01152a;color: #fff;">Referred By</th>
							 	<th style="background: #01152a;color: #fff;">Action</th> </tr> 
							 	
							 	</thead> 
							 	
							 	<tbody>
							 	
						 <?php
						 
                            $cnt = $offset + 1;
                            while ($row = mysqli_fetch_array($result)) {
                          ?>  
						
						

						 <tr><th scope="row"><?php echo $cnt;?></th> 
						 	<td><?php  echo $row['patient_name'];?></td> 
						 	<td><?php  echo $row['phone_number'];?></td>
						 	<td><?php  echo $row['referring_doctor'];?></td> 

						 	

						 	<td style="display:flex;">
						 		<a href="view-booking.php"></a>
						 		<button style="margin-right: 4px; border-radius: 20px;width: 55px;height: 32px;background-color: #ed1b24;color: #fff;border: none;"> 
        						<a style="color: #fff;"href=view-patients.php?editid=<?php echo $row['id'];?>">
        							<i class="fa fa-eye" aria-hidden="true"></i>
        						</a>
								</button>  
								
												        
						       <button style="border-radius: 20px;width: 67px;height: 32px;background-color: #01152a;color: #fff; border: none;">
                                                <a style="color: #fff;" href="#" onclick="deleteItem(<?php echo $row["id"]; ?>)">

                                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                                </a>
                                </button>
						        
						        
						 </td>
						 	</tr>   

						 	<?php 
                               $cnt=$cnt+1;
                              }?>

						</tbody> 
						</table> 
						
				<!-- Pagination links -->
<div class="pagination">
    <ul class="pagination">
        <?php
        // Previous button
        if ($currentPage > 1) {
            echo "<li class='page-item'><a class='page-link' href='all-patients.php?page=" . ($currentPage - 1) . "'>Previous</a></li>";
        }

        // First page
        echo "<li class='page-item'><a class='page-link' href='all-patients.php?page=1'>1</a></li>";

        // Current page
        echo "<li class='page-item active'><a class='page-link' href='#'>$currentPage</a></li>";

        // Next 5 pages
        for ($i = $currentPage + 1; $i <= min($totalPages, $currentPage + 5); $i++) {
            echo "<li class='page-item'><a class='page-link' href='all-patients.php?page=$i'>$i</a></li>";
        }

        // Last page
        if ($currentPage < $totalPages - 5) {
            echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
            echo "<li class='page-item'><a class='page-link' href='all-patients.php?page=$totalPages'>$totalPages</a></li>";
        } elseif ($currentPage < $totalPages) {
            echo "<li class='page-item'><a class='page-link' href='all-patients.php?page=$totalPages'>$totalPages</a></li>";
        }

        // Next button
        if ($currentPage < $totalPages) {
            echo "<li class='page-item'><a class='page-link' href='all-patients.php?page=" . ($currentPage + 1) . "'>Next</a></li>";
        }
        ?>
    </ul>
</div>

					</div>
				</div>
				
			</div>
			
			
		</div>
		
		<!--footer-->
		 <?php include_once('includes/footer.php');?>
        <!--//footer-->
	</div>
	<!-- Classie -->
		<script src="../../extensions/js/classie.js"></script>
		<script>
			var menuLeft = document.getElementById( 'cbp-spmenu-s1' ),
				showLeftPush = document.getElementById( 'showLeftPush' ),
				body = document.body;
				
			showLeftPush.onclick = function() {
				classie.toggle( this, 'active' );
				classie.toggle( body, 'cbp-spmenu-push-toright' );
				classie.toggle( menuLeft, 'cbp-spmenu-open' );
				disableOther( 'showLeftPush' );
			};
			
			function disableOther( button ) {
				if( button !== 'showLeftPush' ) {
					classie.toggle( showLeftPush, 'disabled' );
				}
			}
		</script>
		
			<script>
// Add an auto-hide behavior for success and error messages
setTimeout(function() {
    document.querySelector(".success-message").style.display = "none";
    document.querySelector(".error-message").style.display = "none";
}, 3000); // 3000 milliseconds (3 seconds)
</script>
	<script>
    function deleteItem(id) {
        // Use JavaScript to create a confirmation dialog
        var confirmation = confirm("Are you sure you want to delete this item?");
        
        if (confirmation) {
            // If the user confirms, redirect to the delete page with the item ID
            window.location.href = "delete_patient.php?id=" + id;
        }
    }
</script>
	<!--scrolling js-->
	<script src="../../extensions/js/jquery.nicescroll.js"></script>
	<script src="../../extensions/js/scripts.js"></script>
	<!--//scrolling js-->
	<!-- Bootstrap Core JavaScript -->
	<script src="../../extensions/js/bootstrap.js"> </script>

	<script>
			var menuLeft = document.getElementById( 'cbp-spmenu-s1' ),
				showLeftPush = document.getElementById( 'showLeftPush' ),
				body = document.body;
				
			showLeftPush.onclick = function() {
				classie.toggle( this, 'active' );
				classie.toggle( body, 'cbp-spmenu-push-toright' );
				classie.toggle( menuLeft, 'cbp-spmenu-open' );
				disableOther( 'showLeftPush' );
			};
			

			function disableOther( button ) {
				if( button !== 'showLeftPush' ) {
					classie.toggle( showLeftPush, 'disabled' );
				}
			}
		</script>
	<!--scrolling js-->
	<script src="../../extensions/js/jquery.nicescroll.js"></script>
	<script src="../../extensions/js/scripts.js"></script>
	<!--//scrolling js-->
	<!-- Bootstrap Core JavaScript -->
   <script src="../../extensions/js/bootstrap.js"> </script>
   <script type="text/javascript">
    window.onload = setupRefresh;
    function setupRefresh()
    {
        setInterval("refreshBlock();",30000);
    }
    
    function refreshBlock()
    {
       $('#block1').load("index.html");
    }
    
   
   
    <script>
    
    // Add this script to your existing JavaScript code
document.getElementById("viewPatientsButton").addEventListener("click", function() {
  modal.style.display = "block";
});

    // Get the modal element
    var modal = document.getElementById("myModal");

    // Get the button that opens the modal
    var btn = document.getElementById("viewPatientsButton"); // Updated to select the button by its ID

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // Function to open the modal
    function openModal() {
        modal.style.display = "block";
    }

    // Function to close the modal
    function closeModal() {
        modal.style.display = "none";
    }

    // Event listener to open the modal when the button is clicked
    btn.addEventListener("click", openModal); // Updated to use the "openModal" function

    // Event listener to close the modal when the close button is clicked
    span.addEventListener("click", closeModal);

    // Close the modal if the user clicks outside of it
    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    
    

</script>

	<script>
    $(document).ready(function () {
        // Show the Add Patient modal when the button is clicked
        $('#addPatientButton').click(function () {
            $('#addPatientModal').modal('show');
        });

        // Hide the modal when it's closed
        $('#addPatientModal').on('hidden.bs.modal', function () {
            $(this).removeClass('show');
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Show the prebooking modal when the button is clicked
        $('#prebookingButton').click(function () {
            $('#prebookingModal').modal('show');
        });

        // Hide the modal when it's closed
        $('#prebookingModal').on('hidden.bs.modal', function () {
            $(this).removeClass('show');
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Show the Add Patient modal when the button is clicked
        $('#viewPatientModal').click(function () {
            $('#viewPatientModal').modal('show');
        });

        // Hide the modal when it's closed
        $('#viewPatientModal').on('hidden.bs.modal', function () {
            $(this).removeClass('show');
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Show the Add Patient modal when the button is clicked
        $('#addDoctorButton').click(function () {
            $('#addDoctorButton').modal('show');
        });

        // Hide the modal when it's closed
        $('#addDoctorButton').on('hidden.bs.modal', function () {
            $(this).removeClass('show');
        });
    });
</script>
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const nameInput = document.getElementById("Name");
  const messageElement = document.getElementById("nameCheckMessage");

  nameInput.addEventListener("input", function () {
    const name = nameInput.value.trim();
    if (name !== "") {
      checkIfUserExists(name);
    } else {
      messageElement.innerHTML = ""; // Use innerHTML to include HTML content
    }
  });

  function checkIfUserExists(name) {
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
      if (xhr.readyState === XMLHttpRequest.DONE) {
        if (xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          if (response.exists) {
            messageElement.innerHTML = '<i class="fas fa-times" style="color: red;"></i> A patient with the same name already exists.';
          } else {
            messageElement.innerHTML = '<i class="fas fa-check" style="color: green;"></i> ';
          }
        }
      }
    };
    xhr.open("GET", "check-user-exists.php?name=" + encodeURIComponent(name), true);
    xhr.send();
  }
});
</script>

<script>
    $(document).ready(function() {
  $("#patientForm").submit(function(event) {
    event.preventDefault(); // Prevent the default form submission

    // Validate the form
    if (!$("#patientForm").validate().form()) {
      return; // If the form is invalid, do not submit it
    }

    // Add a spinner or loading indicator to the submit button
    $("#submitForm").attr("disabled", true).addClass("loading");

    // Serialize the form data
    var formData = new FormData(this);

    // Send the data to the server using AJAX
    $.ajax({
      url: "process_form.php", // Replace with the PHP script that handles the form submission
      type: "POST",
      data: formData,
      processData: false, // Use false here
      contentType: false, // Use false here
      success: function(response) {
        // Remove the spinner or loading indicator from the submit button
        $("#submitForm").removeAttr("disabled").removeClass("loading");

        // Handle the server response here (e.g., display a success message)
        alert(response);

        // Optionally, close the modal after successful submission
        closeModal();
      },
      error: function() {
        // Remove the spinner or loading indicator from the submit button
        $("#submitForm").removeAttr("disabled").removeClass("loading");

        // Handle errors if the submission fails
        alert("An error occurred.");
      }
    });
  });

  function closeModal() {
    // Close the modal
    var modal = $("#myModal");
    modal.css("display", "none");
  }
});
</script>

<div class="success-message" id="successMessage">
    <?php
    if (isset($_SESSION['success_message'])) {
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']); // Clear the message from the session
    }
    ?>
</div>


<script>


</script>

<script>
    // JavaScript to close the success message after 3 seconds
    const successMessage = document.getElementById('successMessage');

    if (successMessage) {
        setTimeout(function() {
            successMessage.classList.add('hidden');
        }, 3000); // Hide the message after 3 seconds (3000 milliseconds)
    }
</script>



<script>
    function showLoadingSpinner() {
        // Show the loading spinner
        document.getElementById("loadingSpinner").style.display = "block";
    }
</script>



<script>
    var select = document.getElementById('Name');
    var offset = 0; // Initial offset

    select.addEventListener('change', function () {
        var selectedValue = select.options[select.selectedIndex].value;

        if (selectedValue === 'load_more') {
            // Make an AJAX request to fetch more names
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        var moreNames = JSON.parse(xhr.responseText);

                        // Append the new names to the dropdown
                        moreNames.forEach(function (name) {
                            var option = document.createElement('option');
                            option.value = name;
                            option.text = name;
                            select.add(option);
                        });

                        // Update the offset for the next request
                        offset += moreNames.length;

                        // Remove the "Load More" option if there are no more names
                        if (moreNames.length < 10) {
                            select.remove(select.selectedIndex);
                        }
                    } else {
                        console.error('AJAX request failed');
                    }
                }
            };

            xhr.open('POST', 'fetch_more_names.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('offset=' + offset);
        }
    });
</script>
</body>
</html>
<?php } ?>