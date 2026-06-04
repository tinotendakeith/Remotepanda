<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');
if (!isLoggedIn()) {
	$_SESSION['msg'] = "You must log in first";
	header('location: index.php');
}
else{


  ?>
<!DOCTYPE HTML>
<html>
<head>
<title>All Bookings</title>

<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<!-- Custom CSS -->
<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.css">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.min.css">
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
<link rel="icon" type="image/x-icon" href="../../extensions//images/favicon.png">

<!--//Metis Menu -->
</head> 
<body class="cbp-spmenu-push">
	<div class="main-content">
		<!--left-fixed -navigation-->
			<?php 
            include_once('../../includes/reception-sidebar.php');
            include_once('../../includes/reception-header.php');
            
            ?>
		<!-- //header-ends -->
		<!-- main content start-->
		<div id="page-wrapper">
			<div class="main-page">
			    
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
				<div class="tables">
					<h3 class="title1">All Bookings</h3>
					
					
				
					<div class="table-responsive bs-example widget-shadow">
						<h4>All Bookings:</h4>
						<table class="table table-bordered"> <thead> <tr> 
						<th>#</th> 
						<th>Patient Name</th>
						<th>Scan</th> 
						<th>Booking Date</th>
						<th>Action</th> 
						</tr> 
						</thead> 
						<tbody>
                                <?php
                                $ret=mysqli_query($con,"select *from  pdf_data");
                                $cnt=1;
                                while ($row=mysqli_fetch_array($ret)) {
                                
                                ?>

						 <tr> <th scope="row"><?php echo $cnt;?></th> 
						 <td><?php  echo $row['username'];?></td>
						 <td><?php  echo $row['Services'];?></td>
						 <td><?php  echo $row['Time'];?></td> 
						 <td><a href="view-appointment.php?viewid=<?php echo $row['ID'];?>">View</a>
						 </td> </tr>   <?php 
$cnt=$cnt+1;
}?></tbody> </table> 
					</div>
				</div>
			</div>
		</div>
	
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
	<!--scrolling js-->
	<script src="../../extensions/js/jquery.nicescroll.js"></script>
	<script src="../../extensions/js/scripts.js"></script>
	<!--//scrolling js-->
	<!-- Bootstrap Core JavaScript -->
	<script src="../../extensions/js/bootstrap.js"> </script>
</body>
</html>
<?php }  ?>