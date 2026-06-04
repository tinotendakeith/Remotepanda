  <!-- Modal for adding a doctor -->
  <div class="modal fade" id="addDoctorModal" tabindex="-1" role="dialog" aria-labelledby="addDoctorModalLabel" aria-hidden="true">
  <!-- Add your modal content for adding a doctor here -->
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addPatientModalLabel">Details of Doctor</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      <form id="addDoctorForm" method="post" action="">
							
						<?php echo display_error(); ?>		

							 <div style="display:flex;">
									 <div style="width: 50%;margin-right: 16px;" class="form-group"> 
									 	<label for="exampleInputPassword1">Name</label> 
									 	<input id="name" class="form-control" type="text" name="name"> 
									 </div>

									 <div style="width: 50%;" class="form-group"> 
									 	<label for="exampleInputPassword1">Specialty</label> 
									 	<input id="speciality" class="form-control" type="text" name="specialty"> 
									 </div> 
								</div>
								
							 <div style="display:flex;">
									 <div style="width: 50%;margin-right: 16px;" class="form-group"> 
									 	<label for="exampleInputPassword1">Address</label> 
									 	<input id="address" class="form-control" type="text" name="address"> 
									 </div>

									 <div style="width: 50%;" class="form-group"> 
									 	<label for="exampleInputPassword1">Phone Number</label> 
									 	<input id="phone_number" class="form-control" type="text" name="phone_number"> 
									 </div> 
								</div>
                                
                                <div style="display:flex;">
									 <div style="width: 50%;margin-right: 16px;" class="form-group"> 
									 	<label for="exampleInputPassword1">Email Address</label> 
									 	<input id="email_address" class="form-control" type="text" name="email_address"> 
									 </div>

								  
								</div>
								
							 	<div style="display:float !important;">
									
                                 <button type="button" class="btn btn-primary" onclick="submitForm()">+Add Doctor</button>
								</div> 
 
							</form> 
            </div>
           
        </div>
    </div>
  <!-- ... -->
</div>


