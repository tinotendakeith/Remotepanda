
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