<?php
session_start();

include('includes/dbconnection.php');
if (strlen($_SESSION['ODABSaid']==0)) {
  header('location:logout.php');
  } else{

if(isset($_POST['submit']))
  {
    // get the post records
$title = $_POST['title'];
$Name = $_POST['Name'];
$Email = $_POST['Email'];
$dob = $_POST['dob'];  
$MobileNumber = $_POST['MobileNumber'];
$homephone = $_POST['homephone'];
$idnumber = $_POST['idnumber'];
$homeaddress = $_POST['homeaddress'];
$busaddress = $_POST['busaddress'];
$busphone = $_POST['busphone'];
$Referringdoc = $_POST['Referringdoc'];
$Referringinst = $_POST['Referringinst'];
$feestitle = $_POST['feestitle'];
$feesname = $_POST['feesname'];  
$feesdob = $_POST['feesdob'];
$feesidnumber = $_POST['feesidnumber'];
$feesemail = $_POST['feesemail'];
$feesphone = $_POST['feesphone'];
$feeshomeaddress = $_POST['feeshomeaddress'];
$feesbusaddress = $_POST['feesbusaddress'];
$medicalaid = $_POST['medicalaid'];
$medicalaidno = $_POST['medicalaidno'];
$suffix = $_POST['suffix'];
$scanrequired = $_POST['scanrequired'];
$scannedbefore = $_POST['scannedbefore'];


$sql = "INSERT INTO `tblcustomers` (`title`,`Name`, `Email`, `dob`, `MobileNumber`, `homephone`, `idnumber`, `homeaddress`, `busaddress`, `busphone`, `Referringdoc`, `Referringinst`, `feestitle`, `feesname`, `feesdob`, `feesidnumber`, `feesemail` , `feesphone`, `feeshomeaddress`, `feesbusaddress`, `medicalaid`, `medicalaidno`, `suffix`, `scanrequired`, `scannedbefore`)

VALUES ('$title', '$Name', '$Email' , '$dob', '$MobileNumber', '$homephone', '$idnumber', '$homeaddress', '$busaddress', '$busphone', '$Referringdoc', '$Referringinst', '$feestitle', '$feesname', '$feesdob', '$feesidnumber', '$feesemail', '$feesphone', '$feeshomeaddress', '$feesbusaddress', '$medicalaid', '$medicalaidno', '$suffix', '$scanrequired', '$scannedbefore')";
    
    if ($query) {
echo "<script>alert('Customer has been added.');</script>"; 
echo "<script>window.location.href = 'add-customer.php'</script>"; 
 } else {
echo "<script>alert('Something Went Wrong. Please try again.');</script>";  	
} }
  ?>
<!DOCTYPE HTML>
<html>
<head>
<title>Radpanda | Add Customers</title>

<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
 <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">


  <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png"/>
  <link rel="icon" type="image/png" sizes="32x32" href="favicon.png"/>
  <link rel="icon" type="image/png" sizes="16x16" href="favicon.png"/>
  <link rel="mask-icon" href="safari-pinned-tab.svg" color="#00b4b6"/>
<!-- Custom CSS -->
<link href="css/style.css" rel='stylesheet' type='text/css' />
<!-- font CSS -->
<!-- font-awesome icons -->
<link href="css/font-awesome.css" rel="stylesheet"> 
<!-- //font-awesome icons -->
 <!-- js-->
<script src="js/jquery-1.11.1.min.js"></script>
<script src="js/modernizr.custom.js"></script>
<!--webfonts-->
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>
<!--//webfonts--> 
<!--animate-->
<link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
<script src="js/wow.min.js"></script>
	<script>
		 new WOW().init();
	</script>
<!--//end-animate-->
<!-- Metis Menu -->
<script src="js/metisMenu.min.js"></script>
<script src="js/custom.js"></script>
<link href="css/custom.css" rel="stylesheet">

<style type="text/css">
	.card-header-title {
    flex-grow: 1;
    font-weight: 700;
}
.card-header-icon, .card-header-title {
    display: flex;
    align-items: center;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    padding-left: 1rem;
    padding-right: 1rem;
}

.card-header {
    --tw-border-opaCity: 1;
    border-color: rgba(243,244,246,var(--tw-border-opaCity));
    border-bottom-width: 1px;
    display: flex;
    align-items: stretch;
}

.icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 1.5rem;
    width: 1.5rem;
}

.icon i {
    display: inline-flex;
}


</style>
<!--//Metis Menu -->
</head> 
<body style="color:#052f5d;" class="cbp-spmenu-push">
	<div class="main-content">
		<!--left-fixed -navigation-->
		 <?php include_once('includes/sidebar.php');?>
		<!--left-fixed -navigation-->
		<!-- header-starts -->
	 <?php include_once('includes/header.php');?>
		<!-- //header-ends -->
		<!-- main content start-->
		<div id="page-wrapper">
			<div class="main-page">
				<div class="forms">
					<h3 style="color:#052f5d;" class="title1">Add Patient</h3>
					<div class="form-grids row widget-shadow" data-example-id="basic-forms"> 
						<div style="padding: 1em 2em;background-color: #ed1b24;border-bottom: 1px solid #ed1b24;" class="form-title">
							<h4 style="color:#fff">Hospital Patient:</h4>
						</div>
						<div class="form-body">
							<form method="post">
								

							<header class="card-header">
        <p class="card-header-title">
          <span class="icon"><i class="mdi mdi-ballot"></i></span>
          Details of Patient
        </p>
      </header>
      	
							<div style="display:flex;">
							 <div style="width: 12%; padding-left: 0px !important;  padding: 0px 20px;" class="form-group"> 
							 	<label for="exampleInputEmail1">Title</label> 
							 	<input list="titles" style="border: 1px solid #052f5d80;" type="text" class="form-control" id="title" name="name" placeholder="title" value="" required="true"> 
							 	 <datalist id="titles">
              <option value="Mr">
              <option value="Mrs">
              <option value="Miss">
              <option value="Dr">
              <option value="Eng">
            </datalist>
							 </div> 

							 <div style="width: 75%; padding-left: 0px !important; padding: 0px 20px;" class="form-group"> 
							 	<label for="exampleInputEmail1">Name</label> 
							 	<input style="border: 1px solid #052f5d80;" type="text" class="form-control" id="Name" name="name" placeholder="Full Name" value="" required="true"> 
							 </div>

							 <div style="width: 30%; padding-left: 0px !important;" class="form-group"> 
							 	<label for="exampleInputEmail1">Date of Birth</label> 
							 	<input style="border: 1px solid #052f5d80;" class="form-control" id="dob" type="date" name="dob" value="" required="true"> 
							 </div>
							</div>		

							<div style="display:flex;">
							 <div style="width: 70%; padding-left: 0px !important;padding: 0px 20px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Home Address</label> 
							 	<input style="border: 1px solid #052f5d80;" type="text" id="homeaddress" name="homeaddress" class="form-control" placeholder="Enter Home Address" value="" required="true"> 
							 </div> 

							 <div style="width: 30%; padding-left: 0px !important;
							 " class="form-group"> 
							 	<label for="exampleInputPassword1">ID Number</label> 
							 	<input style="border: 1px solid #052f5d80;" type="text" id="idnumber" name="idnumber" class="form-control" placeholder="ID Number" value="" required="true"> 
							 </div>
							</div>

							 <!-- <div class="form-group"> 
							 	<label for="exampleInputEmail1">Home Address</label> 
							 	<input style="border: 1px solid #052f5d80;" type="text" class="form-control" id="mobilenum" name="mobilenum" placeholder="Home Address" value="" required="true" maxlength="10" pattern="[0-9]+"> 
							 </div>  -->

							 
							

							<div style="display:flex;">
							 <div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Email</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="Email" name="email" class="form-control" placeholder="Email" value="" required="true"> 
							 </div> 

							 <div style="width: 50%; padding-left: 0px !important;

							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Phone Number</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="MobileNumber" name="phone" class="form-control" placeholder="Phone Number" value="" required="true"> 
							 </div>
							</div>

							<div style="display:flex;">
							 <div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Name of Employer</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="email" name="email" class="form-control" placeholder="Name of Employer" value="" required="true"> 
							 </div> 

							 <div style="width: 50%; padding-left: 0px !important;

							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Business Address</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="busaddress" name="email" class="form-control" placeholder="Enter Business Address" value="" required="true"> 
							 </div>
							</div>

							<div style="display:flex;">
							 <div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Telephone Home</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="homephone" name="email" class="form-control" placeholder="Telephone Home" value="" required="true"> 
							 </div> 

							 <div style="width: 50%; padding-left: 0px !important;

							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Business Phone</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="busphone" name="email" class="form-control" placeholder="Enter Business Phone" value="" required="true"> 
							 </div>
							</div>


							<!-- Referring Doctor -->
		 <header class="card-header">
        <p class="card-header-title">
          <span class="icon"><i class="mdi mdi-ballot"></i></span>
          Referring Doctor
        </p>
      </header>

						 <div style="display:flex;">
							<div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
						  " class="form-group"> 
								<label for="exampleInputPassword1">Doctor's Name</label> 
							 	<input style="border: 1px solid #052f5d80;" type="name" id="Referringdoc" name="Referringdoc" class="form-control" placeholder="Referring Doctor's name" value="" required="true"> 
							 </div> 

							 <div style="width: 50%; padding-left: 0px !important;

							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Referring Hospital</label> 
							 	<input style="border: 1px solid #052f5d80;" type="Referringinst" id="Referringinst" name="Referringinst" class="form-control" placeholder="Referring Hospital" value="" required="true"> 
							 </div>
							</div>

							<!-- Person Responsible for fees or Medical Aid Society -->

			<header class="card-header">
        <p class="card-header-title">
          <span class="icon"><i class="mdi mdi-ballot"></i></span>
          Person Responsible for fees or Medical Aid Society
        </p>
      </header>

							<div style="display:flex;">
							 <div style="width: 12%; padding-left: 0px !important;  padding: 0px 20px;" class="form-group"> 
							 	<label for="exampleInputEmail1">Title</label> 
							 	<input list="titles" style="border: 1px solid #052f5d80;" type="text" class="form-control" id="feestitle" name="title" placeholder="title" value="" required="true"> 
							 	 <datalist id="titles">
              <option value="Mr">
              <option value="Mrs">
              <option value="Miss">
              <option value="Dr">
              <option value="Eng">
            </datalist>
							 </div> 

							 <div style="width: 75%; padding-left: 0px !important; padding: 0px 20px;" class="form-group"> 
							 	<label for="exampleInputEmail1">Name</label> 
							 	<input style="border: 1px solid #052f5d80;" type="text" class="form-control" id="feesname" name="name" placeholder="Full Name" value="" required="true"> 
							 </div>

							 <div style="width: 30%; padding-left: 0px !important;" class="form-group"> 
							 <label for="exampleInputPassword1">ID Number</label> 
							 	<input style="border: 1px solid #052f5d80;" type="text" id="feesidnumber" name="idnumber" class="form-control" placeholder="ID Number" value="" required="true"> 
							 </div>
							</div>				


  													<div style="display:flex;">
							 <div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Email</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="feesemail" name="email" class="form-control" placeholder="Email" value="" required="true"> 
							 </div> 

							 <div style="width: 50%; padding-left: 0px !important;

							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Phone Number</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="feesphone" name="contactno" class="form-control" placeholder="Phone Number" value="" required="true"> 
							 </div>
							</div>

							 <div style="display:flex;">
							 <div style="width: 70%; padding-left: 0px !important;padding: 0px 20px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Home Address</label> 
							 	<input style="border: 1px solid #052f5d80;" type="text" id="feeshomeaddress" name="homeaddress" class="form-control" placeholder="Enter Home Address" value="" required="true"> 
							 </div> 

							 <div style="width: 30%; padding-left: 0px !important;
							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Date of Birth</label> 
							 	<input style="border: 1px solid #052f5d80;" type="date" id="feesdob" name="feesdob" class="form-control" placeholder="" value="" required="true"> 
							 </div>
							</div>

							 <div style="display:flex;">
							 <div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Name of Employer </label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="" name="email" class="form-control" placeholder="Name of Employer" value="" required="true"> 
							 </div> 

							 <div style="width: 50%; padding-left: 0px !important;

							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Business Address</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="feesbusaddress" name="address" class="form-control" placeholder="Enter Business Address" value="" required="true"> 
							 </div>
							</div>

							<div style="display:flex;">
							 <div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Telephone Home</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="feeshomephone" name="phone" class="form-control" placeholder="Telephone Home" value="" required="true"> 
							 </div> 

							 <div style="width: 50%; padding-left: 0px !important;

							 " class="form-group"> 
							 	<label for="exampleInputPassword1">Business Phone</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="feesbusphone" name="phone" class="form-control" placeholder="Enter Business Phone" value="" required="true"> 
							 </div>
							</div>

							 <div class="radio">

                               <p style="padding-top: 20px; font-size: 15px"> <strong>Have you been scanned here before:</strong> <label>
                                    <input style="border: 1px solid #052f5d80;" type="radio" name="scannedbefore" id="scannedbefore" value="Yes" checked="true">
                                    Yes
                                </label>
                                <label>
                                    <input style="border: 1px solid #052f5d80;"type="radio" name="scannedbefore" id="scannedbefore" value="No">
                                    No
                                </label>
                                </p>
                 </div>

                 <div style="display:flex;">
							 <div style="width: 50%; padding-left: 0px !important;     padding: 0px 40px;
" class="form-group"> 
							 	<label for="exampleInputPassword1">Scan Required</label> 
							 	<input style="border: 1px solid #052f5d80;" type="email" id="scanrequired" name="scanrequired" class="form-control" placeholder="" value="" required="true"> 
							 	<datalist id="titles">
              <option value="Pregnancy Scan">
              <option value="KUB">
              
            </datalist>
							 </div> 

							</div>					
							  <button style="background-color: #ed1b24;" type="submit" name="submit" class="btn btn-default">Add</button> </form> 
						</div>
						
					</div>
				
				
			</div>
		</div>
		 <?php include_once('includes/footer.php');?>
	</div>
	<!-- Classie -->
		<script src="js/classie.js"></script>
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
	<script src="js/jquery.nicescroll.js"></script>
	<script src="js/scripts.js"></script>
	<!--//scrolling js-->
	<!-- Bootstrap Core JavaScript -->
   <script src="js/bootstrap.js"> </script>
<!--//Download more free projects at www.mayurik.com-->
</body>
</html>
<?php } ?>