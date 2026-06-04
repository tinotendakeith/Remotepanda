<style>
    .nav > li > a {
  position: relative;
  display: block;
  padding: 2px 15px;
}

.icho{
  background: #ed1b24;
    padding-top: 11px !important;
    color: #fff !important;
    padding-bottom: 10px !important; 
}

 /* Modal styles */
.modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.4);
}

.modal-content {
  background-color: #fff;
    margin: 6% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 146%;
    max-width: 105%;
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
}

/* Close button */
.close {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 20px;
  font-weight: bold;
  cursor: pointer;
}
</style>
  <div class=" sidebar" role="navigation">
            <div class="navbar-collapse">
        <nav style="background-color:#01152a; height:100%; margin-top: 11px" class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left" id="cbp-spmenu-s1">
          <ul class="nav" id="side-menu">
            <li>
              <a class="icho" href="index.php"><i class="fa fa-home nav_icon"></i>Dashboard</a>
            </li>
            
            
             
            
             <li>
              <a href="all-patients.php"><i class="fa fa-users nav_icon"></i>Patients<span class="fa arrow"></span> </a>
              <ul style="background: #032244;" class="nav nav-second-level collapse">
                  <li>
                  <a id="addPatientButton2" href="#">Add Patient</a>
                </li>
                <li>
                  <a href="all-patients.php">All Patient</a>
                </li>
                <li>
                  <a href="today.php">Todays Patients</a>
                </li>
                <li>
                  <a href="weekly.php">Weekly Patients</a>
                </li>
                
              </ul>
              <!-- /nav-second-level -->
            </li>
            
            
            
            <li>
              <a href="all-booking.php"><i class="fa fa-check-square-o nav_icon"></i>Bookings<span class="fa arrow"></span></a>
              <ul class="nav nav-second-level collapse">
                <li>
                  <a id="prebookingButton2"  href="#">Add Bookings</a>
                </li>
                <li>
                  <a href="all-booking.php">All Bookings</a>
                </li>
              
                
              </ul>
              <!-- //nav-second-level -->
            </li>
            
           <li>
              <a href="add_doctor.php"><i class="fa fa-check-square-o nav_icon"></i>Referring Doctors<span class="fa arrow"></span></a>
              <ul class="nav nav-second-level collapse">
                <li>
                  <a id=""  href="add_doctor.php">Add Doctor</a>
                </li>
                <li>
                  <a href="add_doctor.php">Manage Doctors</a>
                </li>
              
                
              </ul>
              <!-- //nav-second-level -->
            </li>
            
            <li>
              <a href="add_scan.php"><i class="fa fa-check-square-o nav_icon"></i>Scans<span class="fa arrow"></span></a>
              <ul class="nav nav-second-level collapse">
                <li>
                  <a id=""  href="add_scan.php">Manage Scans</a>
                </li>
               
              </ul>
              <!-- //nav-second-level -->
            </li>
           
 <!-- <li>-->
 <!--             <a href="accepted-appointment.php" class="chart-nav"><i class="fa fa-user-md  -->
 <!--nav_icon"></i>Doctor Check</a>-->
 <!--           </li>-->
          
  
            <!--  <li>-->
            <!--  <a href="#"><i class="fa fa-check-square-o nav_icon"></i>Reports<span class="fa arrow"></span></a>-->
            <!--  <ul class="nav nav-second-level collapse">-->
            <!--     <li><a href="bwdates-reports-ds.php"> Durationwise</a></li>-->
                   
            <!--        <li><a href="sales-reports.php">Sales Reports</a></li>-->
            <!--  </ul>-->
              
            <!--</li>-->

    <!--<li>-->
    <!--          <a href="invoices.php" class="chart-nav"><i class="fa fa-file-text-o nav_icon"></i>Invoices</a>-->
    <!--        </li>-->
            
         
          
          

          </ul>
          <div class="clearfix"> </div>
          <!-- //sidebar-collapse -->
        </nav>
      </div>
    </div>
    
   
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
        $('#addPatientButton2').click(function () {
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
        // Show the Add Patient modal when the button is clicked
        $('#prebookingButton2').click(function () {
            $('#prebookingModal').modal('show');
        });

        // Hide the modal when it's closed
        $('#prebookingModal').on('hidden.bs.modal', function () {
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

        // Serialize the form data
        var formData = new FormData(this);

        // Send the data to the server using AJAX
        $.ajax({
            url: "process_form.php", // Replace with the PHP script that handles the form submission
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log(response); // Add this line for debugging
                // Handle the server response here (e.g., display a success message)
                alert(response);
                // Optionally, close the modal after successful submission
                closeModal();
            },
            error: function() {
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