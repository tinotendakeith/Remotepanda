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
            <form method="post" action="../../includes/save_patient.php" onsubmit="checkPatientExists();">
                    <div style="display:flex; width: 100%;">
                    
                    <div style="width: 100%;padding: 0 0 0 0;" class="form-group">
                        <label for="name">Name</label>
                        <input placeholder="First Name and Lastname" type="text" class="form-control" id="patient_name" name="patient_name" value="<?php if (isset($_POST['patient_name'])) { echo htmlentities($_POST['patient_name']); } ?>" oninput="checkPatientExists()">
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
                        <button style="background-color: #01152a; border: none; color: white; padding: 7px 15px; border-radius: 16px;margin: 20px;" type="button" id="addDoctorButton" data-toggle="modal" data-target="#addDoctorModal">
                        Add a New Doctor
                        </button>
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
<!-- Add this script section to your HTML file -->
<script>
  var debounceTimer;

  function checkPatientExists() {
    // Clear previous debounce timer
    clearTimeout(debounceTimer);

    // Get the patient_name value from the input field
    var patientName = document.getElementById('patient_name').value;

    // Set a new debounce timer to wait for a brief moment of inactivity
    debounceTimer = setTimeout(function () {
      // Make an AJAX request to the server to check if the patient exists
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '../../includes/check_patient.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
          // Update the content of the nameCheckMessage span based on the response
          var nameCheckMessage = document.getElementById('nameCheckMessage');
          if (xhr.responseText.trim() == 'exists') {
            // Patient exists, show a red X
            nameCheckMessage.innerHTML = '<span style="color: red;">&#10008; Patient already exists!</span>';
          } else {
            // Patient doesn't exist, show a green checkmark
            nameCheckMessage.innerHTML = '<span style="color: green;">&#10004; Patient does not exist.</span>';
          }
        }
      };
      xhr.send('patient_name=' + patientName);
    }, 300); // Adjust the debounce time as needed
  }
</script>
<!--End of Add patient modal from button-->