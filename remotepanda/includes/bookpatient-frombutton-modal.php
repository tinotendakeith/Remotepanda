<!--Modal from Booking Button -->

<div class="modal fade" id="prebookingModal" tabindex="-1" role="dialog" aria-labelledby="prebookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="prebookingModalLabel">Boodking Form</h4>
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
                    
                    <div style="width: 50%; padding: 0 16px 0 0;" class="form-group">
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
                    
                    <div style="width: 50%;" class="form-group">
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
    <label for="scanned_by">Scanned By</label>
    <select type="text" class="form-control" id="scanned_by" name="scanned_by" value="<?php if (isset($_POST['scanned_by'])) { echo htmlentities($_POST['scanned_by']); } ?>">
        <option value="">Scanned By</option>
        <?php
        $query = mysqli_query($con, "SELECT * FROM users WHERE user_type = 'radiographer' ORDER BY username ASC");
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