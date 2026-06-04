<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');
if (!isLoggedIn()) {
	$_SESSION['msg'] = "You must log in first";
	header('location: index.php');
}else{


?>
<!DOCTYPE HTML>
<html>
<head>
<title>Add Template</title>

<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<!-- Custom CSS -->
<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<!-- font CSS -->
<!-- font-awesome icons -->
<link rel="icon" type="image/x-icon" href="/images/favicon.png">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.css">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.min.css">
<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 

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
		<!--left-fixed -navigation-->
		 	<?php 
            include_once('../../includes/radiographer-sidebar.php');
            include_once('../../includes/radiographer-heading.php');
            
            ?>
		<!-- //header-ends -->
		<!-- main content start-->
		<div id="page-wrapper">
			<div class="main-page">
				<div class="forms">
					<h3 class="title1">Add Template</h3>
					<div class="form-grids row widget-shadow" data-example-id="basic-forms"> 
						<div style="background:#01152a;" class="form-title">
							<h4 style="color:#fff;">Add a New Template:</h4>
						</div>
						<div class="form-body">
					<form method="post" enctype="multipart/form-data">
					<?php
						// If submit button is clicked
						if (isset($_POST['submit']))
						{
						// get name from the form when submitted
						$name = $_POST['name'];			
						 $user = $_SESSION['user']['username'];


						if (isset($_FILES['pdf_file']['name']))
						{
						// If the ‘pdf_file’ field has an attachment
							$file_name = $_FILES['pdf_file']['name'];
							$file_tmp = $_FILES['pdf_file']['tmp_name'];
							
							// Move the uploaded pdf file into the pdf folder
							move_uploaded_file($file_tmp,"./Templates/".$file_name);
							
							// Insert the submitted data from the form into the table
							$insertquery =
							"INSERT INTO Templates(Name,Author,temp_file) VALUES('$name','$user','$file_name')";
							
							// Execute insert query
							$iquery = mysqli_query($con, $insertquery);	

								if ($iquery)
							{							
					             ?>											
								<div class=	"alert alert-success alert-dismissible fade show text-center">
									<a class="close" data-dismiss="alert" aria-label="close">
									×
									</a>
									<strong>Success!</strong> Data submitted successfully.
									echo "<script>alert('Template has been added.');</script>"; 
                                    echo "<script>window.location.href = '#'</script>"
								</div>
								<?php
								}
								else
								{
								?>
								<div class=	"alert alert-danger alert-dismissible fade show text-center">
									<a class="close" data-dismiss="alert" aria-label="close">
									×
									</a>
									echo "<script>alert('Failed, Try Again.');</script>";
									<strong>Failed!</strong> Try Again!
								</div>
								<?php
								}
							}
							else
							{
							?>
								<div class=
								"alert alert-danger alert-dismissible fade show text-center">
								<a class="close" data-dismiss="alert" aria-label="close">
									×
								</a>
								<strong>Failed!</strong> File must be uploaded in htm format!
								</div>
							<?php
							}// end if
						}// end if
					?>
					
					<div class="form-input py-2">
					    
					    <div class="form-group">
				            <input placeholder="Name of Template"  name="name" id="name" required="true" class="form-control">
		                  
						</div>
						
						<div class="form-group">
							<input type="file" name="pdf_file"
								class="form-control" accept=".htm" required/>
						</div>
						<div class="form-group">
							<input type="submit"
								class="btnRegister" name="submit" value="Submit">
						</div>
					</div>
				</form> 
						</div>
						
					</div>
				
				
			</div>
		</div>
		 <?php include_once('includes/footer.php');?>
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
<?php } ?>