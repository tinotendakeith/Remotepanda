<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');
if (!isLoggedIn()) {
	$_SESSION['msg'] = "You must log in first";
	header('location: index.php');
}else{

if(isset($_POST['submit']))
  {
    	$title=$_POST['title'];
    $Name=$_POST['Name'];
    $Email=$_POST['Email'];
    $MobileNumber=$_POST['MobileNumber'];
    $busphone=$_POST['busphone'];
    $dob= $_POST['dob'];
    $homeaddress= $_POST['homeaddress'];
    $NameofEmployer= $_POST['NameofEmployer'];
    $Referringdoc= $_POST['Referringdoc'];
    $feestitle= $_POST['feestitle'];
    $feesname= $_POST['feesname'];
    $feesphone= $_POST['feesphone'];
    $feesbusphone= $_POST['feesbusphone'];
    $feesEmployer= $_POST['feesEmployer'];
    $feeshomeaddress= $_POST['feeshomeaddress'];
    $medicalaid= $_POST['medicalaid'];
    $medicalaidno= $_POST['medicalaidno'];
    $suffix= $_POST['suffix'];
    $scannedbefore= $_POST['scannedbefore'];
   
    $eid=$_GET['editid'];
     
    $query=mysqli_query($con, "update patients set title='$title',Name='$Name',Email='$Email',MobileNumber='$MobileNumber',busphone='$busphone',dob='$dob',homeaddress='$homeaddress',NameofEmployer='$NameofEmployer',Referringdoc='$Referringdoc',feestitle='$feestitle',feesname='$feesname',feesphone='$feesphone',feesbusphone='$feesbusphone',feesEmployer='$feesEmployer',feeshomeaddress='$feeshomeaddress',medicalaid='$medicalaid',medicalaidno='$medicalaidno',suffix='$suffix',scannedbefore='$scannedbefore'  where ID='$eid' ");
    if ($query) {
    $msg="Service has been Updated.";
  }
  else
    {
      $msg="Something Went Wrong. Please try again";
    }
    
    $emp_id = $_POST['emp_id'];
            
            $sql = "DELETE FROM patients WHERE emp_id = $emp_id" ;
            mysql_select_db('radpandaco_database1');
            $retval = mysql_query( $sql, $con );
            
            if(! $retval ) {
               die('Could not delete data: ' . mysql_error());
            }
            
            echo "Deleted data successfully\n";
            
            mysql_close($conn);
         }else {

  
}
  ?>
<!DOCTYPE HTML>
<html>
<head>
<title>View Patient</title>

<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">

<!-- Custom CSS -->
<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 
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
	<div class="main-content">
		<?php 
            include_once('../../includes/radiographer-sidebar.php');
            include_once('../../includes/radiographer-heading.php');
            
            ?>
		<!-- //header-ends -->
		<!-- main content start-->
		<div id="page-wrapper">
		    <div class="tables">
					
					
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
						<th>Referring Doc</th>
						<th>Created on</th>
						<th>Action</th> 
						</tr> </thead> 
						<tbody>
                            <?php
                            $ret=mysqli_query($con,"select *from  patients where  patient_name like '%$sdata%'");
                            $num=mysqli_num_rows($ret);
                            if($num>0){
                            $cnt=1;
                            while ($row=mysqli_fetch_array($ret)) {
                            
                            ?>

						 <tr> <th scope="row"><?php echo $cnt;?></th> 
						 <td><?php  echo $row['id'];?></td> 
						 <td><?php  echo $row['patient_name'];?></td>
						 <td><?php  echo $row['phone_number'];?></td>
						 <td><?php  echo $row['referring_doctor	'];?></td>
						 <td><?php  echo $row['CreationDate'];?></td>
						 
						 <td>	<a href="view-booking.php"></a>
						 		<button style="margin-right: 4px; border-radius: 20px;width: 55px;height: 32px;background-color: #ed1b24;color: #fff;border: none;"> 
        						<a style="color: #fff;"href=view-patients.php?editid=<?php echo $row['id'];?>">
        							<i class="fa fa-eye" aria-hidden="true"></i>
        						</a>
								</button>  
								
								<button style="border-radius: 20px;width: 67px;height: 32px;background-color: #01152a;color: #fff; border: none;">
						 	    <a style="color: #fff;" href="add-patient-services.php?addid=<?php echo $row['id'];?>">
						 	    <i class="fa fa-plus" aria-hidden="true"></i>
						 	    </a>
						        </button>
						        
						        <a href="delete.php?id=<?php echo $row["id"]; ?>">Delete</a></td> </tr>   <?php 
                        $cnt=$cnt+1;
                        } } else { ?>
                          <tr>
                            <td colspan="8"> No record found against this search</td>
                        
                          </tr>
                           
                      <?php } }?></tbody> 
                    </table> 
			<div class="main-page">
			    <?php              
			                    $Name=$row['Name'];
                                 $cid=$_GET['editid'];
                                $ret=mysqli_query($con,"select * from  study where study_id='$cid'");
                                $cnt=1;
                                while ($row=mysqli_fetch_array($ret)) {
                                
                                ?> 
                                
                <div style="background-color: #ffffff;width: 93%;height: px;border-radius: 34px;margin-left: 20px;box-shadow: #dee4e9 2px 3px 12px; display: flex;  ">

						<div style="width:80%; padding: 37px;">
							<h3 style="margin-bottom: 23px;"><?php echo $row['title']; ?><span><?php echo $row['patient_name']; ?></span></h3>
                            <table style="width:100%; margin-bottom:15px;">
                              <tr>
                                <th>PERSONAL CONTACT: </th>
                                <td><?php echo $row['phone_number'];?></td>
                              </tr>
                              <tr>
                                <th>DATE OF BIRTH:</th>
                                <td><?php echo $row['date_of_birth'];?></td>
                              </tr>
                              <tr>
                                <th>EMAIL:</th>
                                <td><?php echo $row['email_address'];?></td>
                              </tr>
                              
                              <tr>
                                <th>BUSNESS CONTACT:</th>
                                <td><?php echo $row['business_phone'];?></td>
                              </tr>
                              
                              <tr>
                                <th>HOME ADDRESS:</th>
                                <td><?php echo $row['address'];?></td>
                              </tr>
                              <tr>
                                <th>EMPLOYER:</th>
                                <td><?php echo $row['employer_name'];?></td>
                              </tr>
                              <tr>
                                <th>REFERRING DOCTOR:</th>
                                <td><?php echo $row['referring_doctor'];?></td>
                              </tr>
                            </table>

							<a href="new-appointment.php"><button style="background-color: #ed1b24;border: none;color: white;padding: 7px 15px;border-radius: 16px;">Book Patient</button></a>
							<a href="add-customer.php"><button style="background-color:#01152a;border: none;color: white;padding: 7px 15px;border-radius: 16px;">Edit Details</button></a>
							
							
						</div>
						
						<div style="width:100%; padding: 37px; height:100%; background:#02244a; margin:20px; border-radius:20px;">
							<h3 style="margin-bottom: 23px;color:#fff;">Scan History</h3>
                            <?php
                                $Name=$row['Name'];
                            ?>
							<table style="height:150px; color:#fff;"  class="table "> 
							<thead> <tr> 
								<th style="background: #ed1b24;color: #fff;">#</th> 
							
								<th style="background: #ed1b24;color:#fff;">Scan Type</th>
								<th style="background: #ed1b24;color:#fff;">Status</th>
							    <th style="background: #ed1b24;color:#fff;">Time</th>
								<th style="background: #ed1b24;color:#fff;">Action</th> </tr> </thead> 
								<tbody>
									<?php
									$ret=mysqli_query($con,"select *from pdf_data where username='$Name'");
									$cnt=1;
									while ($row=mysqli_fetch_array($ret)) {

									?>

						 <tr> 
						 	<th scope="row"><?php echo $cnt;?></th> 
						 	 
						 	<td><?php  echo $row['Services'];?></td>
							<td><?php  echo $row['status'];?></td>
						 	<td><?php  echo $row['Time'];?></td> 
						 	<td style="display:flex;">
						 		<button style="margin-right: 4px; border-radius: 20px;width: 55px;height: 32px;background-color: #ed1b24;color: #fff;
                                    border: none;"> <a style="color: #fff;" href="view-appointment.php?viewid=<?php echo $row['id'];?>"><i class="fa fa-eye" aria-hidden="true"></i></a>
								</button>  
								
						 </td> 
						 </tr>   
						 	<?php $cnt=$cnt+1;}?>
						 </tbody> 
						</table> 

						</div>
						
					</div>                
                                
            
            
            <div>
                 <?php              
                                $cid=$_GET['editid'];
                                $ret=mysqli_query($con,"select * from  patients where id='$cid'");
                                $cnt=1;
                                while ($row=mysqli_fetch_array($ret)) {
                                
                                ?> 
                  
             <div style="display: flex; width: 94%; margin-top:60px; margin-bottom:60px;   ">     
             
                <div style="background-color: #ffffff;width: 93%;height: px;border-radius: 34px;margin-left: 20px;box-shadow: #dee4e9 2px 3px 12px; display: flex;  ">
                        
						<div style="padding: 37px;">
							<h3 style="margin-bottom: 23px;"><?php echo $row['medicalaid']; ?></h3>
                            <table style="width:100%; margin-bottom:15px;">
                              <tr>
                                <th style="padding-right: 20px;">MEMBERSHIP NUMBER: </th>
                                <td><?php echo $row['medicalaidno'];?></td>
                              </tr>
                              <tr>
                                <th>MEMBER'S FULL NAME: </th>
                                <td><?php echo $row['feesname'];?></td>
                              </tr>
                              <tr>
                                <th>CONTACT: </th>
                                <td><?php echo $row['feesphone'];?></td>
                              </tr>
                              
                              <tr>
                                <th>DATE OF BIRTH: </th>
                                <td><?php echo $row['feesdob'];?></td>
                              </tr>
                              
                              <tr>
                                <th>HOME ADDRESS: </th>
                                <td><?php echo $row['feeshomeaddress'];?></td>
                              </tr>
                              <tr>
                                <th>EMPLOYER: </th>
                                <td><?php echo $row['feesEmployer'];?></td>
                              </tr>
                              <tr>
                                <th>SUFFIX: </th>
                                <td><?php echo $row['suffix'];?></td>
                              </tr>
                            </table>

						
							
						</div>
					
						
							<?php $cnt=$cnt+1;}?>
						
					</div>   
					
					
					<div style="background-color:#02244a;width: 93%;height: px;border-radius: 34px;margin-left: 20px;box-shadow: #dee4e9 2px 3px 12px; display: flex;  ">

						
						
				
					</div>   
				</div> 
				
				</div>
                
			 <?php } ?>
					
		
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
	<!--scrolling js-->
	<script src="../../extensions/js/jquery.nicescroll.js"></script>
	<script src="../../extensions/js/scripts.js"></script>
	<!--//scrolling js-->
	<!-- Bootstrap Core JavaScript -->
   <script src="../../extensions/js/bootstrap.js"> </script>
</body>
</html>
<?php } ?>