<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$msg = ''; // ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¦ Prevent undefined variable warnings


include('../../includes/dbconnection.php');
include('../../functions.php');
include('../../includes/remote_reporting_service.php');
include('../../includes/typist_workflow_service.php');
include('../../includes/report_template_helper.php');
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
   
    $eid = isset($_GET['editid']) ? (int) $_GET['editid'] : 0;
     
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
<script src="../../extensions/js/tinymce/js/tinymce/tinymce.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.css">
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.min.css">
<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<!-- Custom CSS -->
<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 
<!-- //font-awesome icons -->
 <!-- js-->
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/modernizr.custom.js"></script>
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
<!--webfonts-->
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>
<!--//webfonts--> 
<!--animate-->
<link href="../../extensions/css/animate.css" rel="stylesheet" type="text/css" media="all">
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
<style type="text/css">
  /* Optional CSS for styling the modal */


.modal {
  display: none;
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.4);
}

.modal-content {
  background-color: #fefefe;
  margin: 15% auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
}

.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}

</style>
<!--//Metis Menu -->
</head> 
<body class="cbp-spmenu-push">



<div class="success-message" id="successMessage">
    <?php
    if (isset($_SESSION['success_message'])) {
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']); // Clear the message from the session
    }
    ?>
</div>

<script>
    // JavaScript to close the success message after 3 seconds
    const successMessage = document.getElementById('successMessage');

    if (successMessage) {
        setTimeout(function() {
            successMessage.classList.add('hidden');
        }, 3000); // Hide the message after 3 seconds (3000 milliseconds)
    }
</script>


    <div class="main-content">
    <?php
            include_once('../../includes/radiographer-heading.php');
            include_once('../../includes/radiographer-sidebar.php');
    ?>

<!-- Modal -->
<div id="editPatientModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <form id="editPatientForm" action="process_edit_patient.php" method="post">
      <!-- Your form fields for editing patient information -->
    </form>
  </div>
</div>
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
                            $num=($ret instanceof mysqli_result) ? mysqli_num_rows($ret) : 0;
                            if($num>0){
                            $cnt=1;
                            while ($ret instanceof mysqli_result && $row=mysqli_fetch_array($ret)) {
                            
                            ?>

                         <tr> <th scope="row"><?php echo $cnt;?></th> 
                         <td><?php  echo $row['id'];?></td> 
                         <td><?php  echo $row['patient_name'];?></td>
                         <td><?php  echo $row['phone_number'];?></td>
                         <td><?php  echo $row['referring_doctor '];?></td>
                         <td><?php  echo $row['CreationDate'];?></td>
                         
                         <td>   <a href="view-booking.php"></a>
                                <button  style="margin-right: 4px; border-radius: 20px;width: 55px;height: 32px;background-color: #ed1b24;color: #fff;border: none;"> 
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
    include '../../includes/dbconnection.php'; // Include your database connection file
    
    rp_remote_reporting_ensure_schema($con);
    $currentReporter = rp_remote_reporting_current_user();

    // Retrieve patient details using the passed accession number
    if (isset($_GET['accession'])) {
        $accession = trim((string)$_GET['accession']);
        $row = rp_remote_reporting_get_case($con, $accession, $currentReporter);
        
        if ($row) {
            rp_remote_reporting_mark_case_opened($con, (string)($row['studyint'] ?? ''), $currentReporter);
            $row = rp_remote_reporting_get_case($con, $accession, $currentReporter) ?: $row;
    ?>
                                        
         <style>
.rp-study-shell{width:93%;margin:0 0 20px 20px;background:#ffffff;border-radius:24px;box-shadow:#dee4e9 2px 3px 12px;display:grid;grid-template-columns:1.4fr 1fr;gap:24px;padding:26px 28px}
.rp-study-title{font-size:34px;font-weight:700;letter-spacing:.2px;color:#0f172a;margin:0 0 14px 0;text-transform:uppercase}
.rp-facts{display:grid;grid-template-columns:220px 1fr;gap:8px 14px}
.rp-facts .k{font-weight:700;color:#0f172a}
.rp-facts .v{color:#0f172a}
.rp-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;align-items:center}
.rp-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;font-weight:600;cursor:pointer;text-decoration:none}
.rp-btn-primary{background:#ed1b24;border-color:#ed1b24;color:#fff}
.rp-btn-success{background:#16a34a;border-color:#16a34a;color:#fff}
.rp-btn-ghost{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.rp-status{font-size:13px;color:#334155;font-weight:600}
.rp-right-card{background:#0a2a57;border-radius:18px;padding:18px;color:#e2e8f0;min-height:250px}
.rp-right-title{font-size:18px;font-weight:700;margin:0 0 10px 0;color:#fff}
.rp-mini-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.rp-mini{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);border-radius:12px;padding:10px}
.rp-mini .k{font-size:11px;text-transform:uppercase;letter-spacing:.6px;opacity:.8}
.rp-mini .v{font-size:15px;font-weight:700;color:#fff}
.rp-request-wrap{margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.2)}
.rp-request-link{display:inline-flex;align-items:center;gap:6px;background:#0ea5e9;color:#fff;border-radius:10px;padding:8px 12px;text-decoration:none;font-weight:600}
.rp-report-card{width:93%;margin:0 0 24px 20px;background:#fff;border:1px solid #dbeafe;border-radius:20px;box-shadow:#dee4e9 2px 3px 12px;padding:22px 26px}
.rp-report-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:12px}
.rp-report-title{font-size:22px;font-weight:700;color:#0f172a;margin:0}
.rp-report-sub{font-size:13px;color:#475569;margin:4px 0 0 0}
.rp-report-textarea{width:100%;min-height:220px;border:1px solid #cbd5e1;border-radius:12px;padding:14px;font-size:14px;line-height:1.5;color:#0f172a;resize:vertical}
.rp-report-footer{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px}
.rp-btn-final{background:#16a34a;border-color:#16a34a;color:#fff}
.rp-method-card{padding:16px 20px}
.rp-method-toggle{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.rp-method-toggle button{border:1px solid #bfdbfe;background:#f8fbff;color:#0f2f5f;border-radius:999px;padding:10px 15px;font-weight:800}
.rp-method-toggle button.active{background:#001f3f;color:#fff;border-color:#001f3f}
.rp-method-toggle small{color:#64748b;font-weight:600}
.rp-method-panel{display:none}
.rp-method-panel.active{display:block}
.rp-direct-grid{display:grid;grid-template-columns:270px minmax(0,1fr);gap:16px;align-items:start}
.rp-template-rail{border:1px solid #dbeafe;border-radius:16px;background:#f8fbff;padding:14px;max-height:560px;overflow:auto}
.rp-template-rail h4{margin:0 0 8px 0;color:#0f172a;font-size:16px;font-weight:800}
.rp-template-search{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:9px 10px;margin:8px 0 10px 0}
.rp-template-list{display:flex;flex-direction:column;gap:8px;margin-bottom:12px}
.rp-template-item{border:1px solid #bfdbfe;background:#fff;color:#0f172a;text-align:left;border-radius:12px;padding:10px;cursor:pointer}
.rp-template-item:hover,.rp-template-item.active{border-color:#2563eb;background:#eff6ff}
.rp-template-item strong{display:block;font-size:13px}
.rp-template-item small{display:block;color:#64748b;font-size:11px;margin-top:3px;word-break:break-all}
.rp-template-empty{border:1px dashed #cbd5e1;border-radius:12px;padding:12px;color:#64748b;background:#fff}
.rp-template-tools{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px 0}
.rp-template-link{display:inline-flex;align-items:center;justify-content:center;border:1px solid #bfdbfe;border-radius:999px;padding:8px 11px;font-weight:800;color:#1d4ed8;background:#fff}
.rp-template-tags{display:flex;gap:6px;flex-wrap:wrap}
.rp-template-chip{border:1px solid #bfdbfe;background:#fff;color:#1e3a8a;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:800}
.rp-editor-area{min-width:0}
.rp-editor-hint{color:#64748b;font-size:12px;margin:0 0 8px 0}
.rp-order-pill{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;background:#e0f2fe;color:#075985;font-size:12px;font-weight:700}
.rp-order-pill.warn{background:#fff7ed;color:#9a3412}
.rp-order-pill.ok{background:#dcfce7;color:#166534}
.rp-order-pill.bad{background:#fee2e2;color:#991b1b}
.rp-report-state{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:flex-end}
.rp-return-note{margin-top:10px;border:1px solid #dbeafe;background:#f8fbff;border-radius:12px;padding:10px 12px;color:#334155;font-size:13px}
.rp-typist-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}
.rp-typist-panel{border:1px solid #dbeafe;border-radius:14px;background:#f8fbff;padding:14px}
.rp-typist-panel h4{margin:0 0 8px 0;color:#0f172a;font-size:16px;font-weight:700}
.rp-typist-list{display:grid;gap:8px;margin:0;padding:0;list-style:none}
.rp-typist-item{border:1px solid #e2e8f0;border-radius:10px;background:#fff;padding:9px 10px;color:#0f172a;font-size:13px}
.rp-typist-item small{display:block;color:#64748b;margin-top:3px}
.rp-typist-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:center}
.rp-typist-note{width:100%;min-height:74px;border:1px solid #cbd5e1;border-radius:10px;padding:10px;resize:vertical}
.rp-typist-draft{white-space:pre-wrap;background:#fff;border:1px solid #dbeafe;border-radius:10px;padding:10px;min-height:90px;color:#0f172a}
.rp-btn-warning{background:#f59e0b;border-color:#f59e0b;color:#fff}
.rp-confirm-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.48);z-index:100000;align-items:center;justify-content:center;padding:24px}
.rp-confirm-dialog{width:min(460px,calc(100vw - 40px));background:#fff;border-radius:14px;box-shadow:0 22px 70px rgba(2,6,23,.25);padding:22px 18px 18px}
.rp-confirm-title{margin:0 0 10px 0;color:#0f172a;font-size:20px;font-weight:700}
.rp-confirm-body{margin:0;color:#334155;font-size:15px;line-height:1.45}
.rp-confirm-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.rp-confirm-btn{border:none;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer}
.rp-confirm-later{background:#e8edf4;color:#0f172a}
.rp-confirm-primary{background:#061f3d;color:#fff}
@media(max-width:1200px){.rp-study-shell{grid-template-columns:1fr}.rp-facts{grid-template-columns:170px 1fr}.rp-direct-grid{grid-template-columns:1fr}}
@media(max-width:900px){.rp-typist-grid{grid-template-columns:1fr}}
</style>

<?php
$requestFile = '';
$eventRecord = array();
$eventLookup = mysqli_real_escape_string($con, (string)$accession);
$eventsTableCheck = mysqli_query($con, "SHOW TABLES LIKE 'events'");
if ($eventsTableCheck && mysqli_num_rows($eventsTableCheck) > 0) {
    $eventQuery = mysqli_query($con, "SELECT * FROM events WHERE accession_number = '$eventLookup' OR id = '$eventLookup' ORDER BY id DESC LIMIT 1");
    if ($eventQuery && ($eventRow = mysqli_fetch_assoc($eventQuery))) {
        $eventRecord = $eventRow;
        $requestFile = trim((string)($eventRow['filename'] ?? ''));
    }
}

$patientDob = isset($row['date_of_birth']) ? trim((string)$row['date_of_birth']) : '';
$patientAge = '';
$reportDraft = trim((string)($row['final_report_text'] ?? ''));
if ($reportDraft === '') {
    $reportDraft = trim((string)($row['radiologist_notes'] ?? ''));
}
if ($reportDraft === '') {
    $reportDraft = trim((string)($row['textarea'] ?? ''));
}
$orderStatus = trim((string)($row['report_order_status'] ?? ''));
$orderUid = trim((string)($row['order_uid'] ?? ''));
$returnStatus = trim((string)($row['return_status'] ?? ''));
$returnError = trim((string)($row['return_last_error'] ?? ''));
$returnAttempts = (int)($row['return_attempts'] ?? 0);
$returnLabel = '';
$returnClass = 'warn';
if ($returnStatus !== '') {
    $returnLabel = 'Return: ' . strtoupper($returnStatus);
    if (in_array(strtolower($returnStatus), array('sent', 'returned'), true)) {
        $returnClass = 'ok';
    } elseif ($returnError !== '' || in_array(strtolower($returnStatus), array('failed', 'error'), true)) {
        $returnClass = 'bad';
    }
} elseif ($orderStatus === 'reported') {
    $returnLabel = 'Return: QUEUED';
} elseif ($orderStatus === 'returned') {
    $returnLabel = 'Return: PICKED UP';
    $returnClass = 'ok';
}
$isFinalized = in_array(strtolower($orderStatus), array('reported', 'returned'), true) || strtolower((string)($row['status'] ?? '')) === 'finalized';
$typistState = rp_typist_workflow_get_case_state($con, (string)($row['studyint'] ?? ''));
$typistDraft = is_array($typistState['latest_draft'] ?? null) ? $typistState['latest_draft'] : null;
$typistDraftText = trim((string)($typistDraft['draft_text'] ?? ''));
$typistDraftStatus = trim((string)($typistDraft['status'] ?? ''));
$dictationCount = count($typistState['dictations'] ?? array());
$currentReporterUsername = is_array($currentReporter) ? trim((string)($currentReporter['username'] ?? '')) : trim((string)$currentReporter);
$templatePlaceholderCatalog = rp_template_placeholder_catalog();
$templateContext = rp_template_context_from_records($row, $eventRecord, $currentReporter);
$templateContext['study_id'] = rp_template_value($row['studyint'] ?? ($row['accession_number'] ?? ($templateContext['study_id'] ?? '')));
$templateContext['radiologist_name'] = rp_template_value($currentReporterUsername);
$templateDir = __DIR__ . DIRECTORY_SEPARATOR . 'Templates';
if (!is_dir($templateDir)) {
    @mkdir($templateDir, 0777, true);
}
$reportTemplates = array();
$templateOwnerColumns = false;
$templatesTableCheck = mysqli_query($con, "SHOW TABLES LIKE 'Templates'");
if ($templatesTableCheck && mysqli_num_rows($templatesTableCheck) > 0) {
    $ownerColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM Templates LIKE 'owner_type'");
    $ownerUserColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM Templates LIKE 'owner_username'");
    if ($ownerColumnCheck && $ownerUserColumnCheck && mysqli_num_rows($ownerColumnCheck) > 0 && mysqli_num_rows($ownerUserColumnCheck) > 0) {
        $templateOwnerColumns = true;
    }
    $currentReporterEsc = mysqli_real_escape_string($con, $currentReporterUsername);
    if ($templateOwnerColumns) {
        $templateSql = "SELECT tempID, Name, temp_file FROM Templates WHERE (owner_type = 'radiologist' AND owner_username = '$currentReporterEsc') OR Author = '$currentReporterEsc' ORDER BY Name ASC";
    } else {
        $templateSql = "SELECT tempID, Name, temp_file FROM Templates WHERE Author = '$currentReporterEsc' ORDER BY Name ASC";
    }
    $templateRes = mysqli_query($con, $templateSql);
    if ($templateRes) {
        while ($templateRow = mysqli_fetch_assoc($templateRes)) {
            $file = basename((string)($templateRow['temp_file'] ?? ''));
            if ($file !== '' && is_file($templateDir . DIRECTORY_SEPARATOR . $file)) {
                $templateRow['temp_file'] = $file;
                $reportTemplates[] = $templateRow;
            }
        }
    }
}
if ($patientDob !== '') {
    try {
        $dobObj = new DateTime($patientDob);
        $todayObj = new DateTime('today');
        $patientAge = $dobObj->diff($todayObj)->y . ' years';
    } catch (Exception $e) {
        $patientAge = '';
    }
}
?>

<div class="rp-study-shell">
    <div>
        <h2 class="rp-study-title"><?php echo htmlspecialchars($row['Name']); ?></h2>
        <?php if ($orderUid !== '' || $orderStatus !== '' || $returnLabel !== ''): ?>
            <div style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <span class="rp-order-pill"><?php echo htmlspecialchars($orderStatus !== '' ? strtoupper($orderStatus) : 'REPORT ORDER'); ?><?php echo $orderUid !== '' ? ' · ' . htmlspecialchars($orderUid) : ''; ?></span>
                <?php if ($returnLabel !== ''): ?>
                    <span id="returnStatusPill" class="rp-order-pill <?php echo htmlspecialchars($returnClass); ?>"><?php echo htmlspecialchars($returnLabel); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="rp-facts">
            <div class="k">Study UID</div><div class="v"><?php echo htmlspecialchars($row['studyint']); ?></div>
            <div class="k">Accession Number</div><div class="v"><?php echo htmlspecialchars($row['accession_number']); ?></div>
            <div class="k">Date of Birth</div><div class="v"><?php echo htmlspecialchars($row['date_of_birth']); ?></div>
            <div class="k">Age</div><div class="v"><?php echo $patientAge !== '' ? htmlspecialchars($patientAge) : '-'; ?></div>
            <div class="k">Gender</div><div class="v"><?php echo htmlspecialchars($row['gender']); ?></div>
            <div class="k">Requesting Physician</div><div class="v"><?php echo htmlspecialchars($row['requesting_physician']); ?></div>
            <div class="k">Requested Procedure</div><div class="v"><?php echo htmlspecialchars($row['requested_procedure']); ?></div>
            <div class="k">Technician</div><div class="v"><?php echo htmlspecialchars($row['technician_name']); ?></div>
            <div class="k">Study Date</div><div class="v"><?php echo htmlspecialchars($row['start_date']); ?></div>
        </div>

        <input type="hidden" name="studyint" id="studyint" value="<?php echo htmlspecialchars($row['studyint']); ?>">

        <div class="rp-actions">
            <button class="rp-btn rp-btn-primary" type="button" id="openImageBtn" data-studyint="<?php echo htmlspecialchars($row['studyint']); ?>">Open Images</button>
            <a id="downloadZipBtn" class="rp-btn rp-btn-success" href="/remotepanda/api/download-study-package.php?studyint=<?php echo rawurlencode((string)$row['studyint']); ?>" download>Download Images</a>
            <span id="imageStatus" class="rp-status"></span>
        </div>
    </div>

    <div class="rp-right-card">
        <h3 class="rp-right-title">Radiologist Quick Context</h3>
        <div class="rp-mini-grid">
            <div class="rp-mini"><div class="k">Procedure</div><div class="v"><?php echo htmlspecialchars($row['requested_procedure']); ?></div></div>
            <div class="rp-mini"><div class="k">Referrer</div><div class="v"><?php echo htmlspecialchars($row['requesting_physician']); ?></div></div>
            <div class="rp-mini"><div class="k">Patient Sex</div><div class="v"><?php echo htmlspecialchars($row['gender']); ?></div></div>
            <div class="rp-mini"><div class="k">Patient Age</div><div class="v"><?php echo $patientAge !== '' ? htmlspecialchars($patientAge) : '-'; ?></div></div>
        </div>

        <?php if ($requestFile !== ''): ?>
        <div class="rp-request-wrap" id="requestFileSection">
            <div style="font-weight:700;color:#fff;margin-bottom:8px;">Request File</div>
            <a class="rp-request-link" target="_blank" rel="noopener" href="../../extensions/pdf/<?php echo rawurlencode($requestFile); ?>">Open Request File</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<div class="rp-report-card rp-method-card">
    <div class="rp-report-head" style="margin-bottom:0;">
        <div>
            <h3 class="rp-report-title">Reporting Workspace</h3>
            <p class="rp-report-sub">Choose how you want to complete this case.</p>
        </div>
        <div class="rp-method-toggle" aria-label="Reporting method">
            <button type="button" id="directReportModeBtn" class="active">Type Report</button>
            <button type="button" id="typistReportModeBtn">Dictate / Typist Pool</button>
            <small>Switch anytime before finalizing.</small>
        </div>
    </div>
</div>
<div id="typistWorkflowPanel" class="rp-method-panel">
<div class="rp-report-card">
    <div class="rp-report-head">
        <div>
            <h3 class="rp-report-title">Typist Workflow</h3>
            <p class="rp-report-sub">Upload dictations, send them to the typing pool, review the typed draft, then approve it for clinic return.</p>
        </div>
        <div class="rp-report-state">
            <span class="rp-order-pill <?php echo $typistDraftStatus === 'typed_draft_ready' ? 'ok' : 'warn'; ?>">
                <?php echo htmlspecialchars($typistDraftStatus !== '' ? strtoupper(str_replace('_', ' ', $typistDraftStatus)) : ($dictationCount > 0 ? 'DICTATION READY' : 'NO DICTATION')); ?>
            </span>
        </div>
    </div>
    <div class="rp-typist-grid">
        <div class="rp-typist-panel">
            <h4>Dictations</h4>
            <?php if (!empty($typistState['dictations'])): ?>
                <ul class="rp-typist-list">
                    <?php foreach ($typistState['dictations'] as $dictation): ?>
                        <li class="rp-typist-item">
                            <strong><?php echo htmlspecialchars((string)($dictation['radiologist_username'] ?? 'Radiologist')); ?></strong>
                            <small><?php echo htmlspecialchars((string)($dictation['created_at'] ?? '')); ?> · <?php echo number_format(((int)($dictation['file_size'] ?? 0)) / 1024, 1); ?> KB</small>
                            <?php if (trim((string)($dictation['note_text'] ?? '')) !== ''): ?>
                                <small><?php echo htmlspecialchars((string)$dictation['note_text']); ?></small>
                            <?php endif; ?>
                            <audio controls preload="none" src="/remotepanda/api/download-dictation.php?id=<?php echo (int)$dictation['id']; ?>" style="width:100%;margin-top:7px;"></audio>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="rp-typist-item">No saved dictations yet. Use the image viewer recording panel to save one.</div>
            <?php endif; ?>
            <textarea id="sendToTypistMessage" class="rp-typist-note" placeholder="Optional note for typists..."></textarea>
            <div class="rp-typist-actions">
                <button class="rp-btn rp-btn-warning" type="button" id="sendToTypistBtn" data-studyint="<?php echo htmlspecialchars((string)$row['studyint']); ?>" <?php echo $isFinalized ? 'disabled' : ''; ?>>Send to Typist</button>
                <span id="typistWorkflowStatus" class="rp-status"></span>
            </div>
        </div>
        <div class="rp-typist-panel">
            <h4>Typed Draft</h4>
            <?php if ($typistDraftText !== ''): ?>
                <div class="rp-typist-draft" id="typistDraftText"><?php echo htmlspecialchars($typistDraftText); ?></div>
                <small>Typed by <?php echo htmlspecialchars((string)($typistDraft['typist_username'] ?? 'typist')); ?> · <?php echo htmlspecialchars((string)($typistDraft['updated_at'] ?? '')); ?></small>
            <?php else: ?>
                <div class="rp-typist-draft" id="typistDraftText">No typed draft has been submitted yet.</div>
            <?php endif; ?>
            <textarea id="typistEditRequestMessage" class="rp-typist-note" placeholder="Message to typist if edits are needed..."></textarea>
            <div class="rp-typist-actions">
                <button class="rp-btn rp-btn-ghost" type="button" id="useTypistDraftBtn" <?php echo $typistDraftText === '' || $isFinalized ? 'disabled' : ''; ?>>Use Draft in Report</button>
                <button class="rp-btn rp-btn-warning" type="button" id="requestTypistEditsBtn" data-studyint="<?php echo htmlspecialchars((string)$row['studyint']); ?>" <?php echo $typistDraftText === '' || $isFinalized ? 'disabled' : ''; ?>>Request Edits</button>
                <button class="rp-btn rp-btn-final" type="button" id="approveTypistDraftBtn" data-studyint="<?php echo htmlspecialchars((string)$row['studyint']); ?>" <?php echo $typistDraftText === '' || $isFinalized ? 'disabled' : ''; ?>>Approve Draft & Queue Return</button>
            </div>
        </div>
    </div>
</div>
</div><?php
?>
<div id="directReportPanel" class="rp-method-panel active">
<div class="rp-report-card">
    <div class="rp-report-head">
        <div>
            <h3 class="rp-report-title">Final Report</h3>
            <p class="rp-report-sub">Save drafts while reviewing images, then finalize to queue the report for secure return to the clinic.</p>
        </div>
        <div class="rp-report-state">
            <span id="draftSaveStatus" class="rp-status"></span>
            <span id="finalReportStatus" class="rp-status"><?php echo $row['reported_at'] ? 'Reported: ' . htmlspecialchars((string)$row['reported_at']) : ''; ?></span>
        </div>
    </div>
    <div id="returnStatusNote" class="rp-return-note" style="<?php echo $returnLabel !== '' ? '' : 'display:none;'; ?>">
        <?php if ($returnLabel !== ''): ?>
            <?php
            if ($returnClass === 'ok') {
                echo 'The clinic has picked up this finalized report.';
            } elseif ($returnClass === 'bad') {
                echo 'Report return needs attention: ' . htmlspecialchars($returnError !== '' ? $returnError : 'return failed');
            } else {
                echo 'This report is finalized and queued for clinic pickup.';
            }
            if ($returnAttempts > 0) {
                echo ' Attempts: ' . (int)$returnAttempts . '.';
            }
            ?>
        <?php endif; ?>
    </div>
    <div class="rp-direct-grid">
        <aside class="rp-template-rail">
            <h4>Templates & Letterheads</h4>
            <p class="rp-report-sub">Load your saved report template. Patient tags are filled when the template opens.</p>
            <input type="search" id="reportTemplateSearch" class="rp-template-search" placeholder="Search templates">
            <div id="reportTemplateList" class="rp-template-list">
                <?php if (!empty($reportTemplates)): ?>
                    <?php foreach ($reportTemplates as $template): ?>
                        <button type="button" class="rp-template-item" data-template-file="<?php echo htmlspecialchars((string)$template['temp_file']); ?>">
                            <strong><?php echo htmlspecialchars((string)$template['Name']); ?></strong>
                            <small><?php echo htmlspecialchars((string)$template['temp_file']); ?></small>
                        </button>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="rp-template-empty">
                        <strong>No templates yet.</strong><br>
                        Upload letterheads and report templates from the template manager.
                    </div>
                <?php endif; ?>
            </div>
            <div class="rp-template-tools">
                <a class="rp-template-link" href="view_templates.php" target="_blank" rel="noopener">Manage Templates</a>
            </div>
            <h4>Quick Inserts</h4>
            <div class="rp-template-tags">
                <?php foreach ($templatePlaceholderCatalog as $placeholder): ?>
                    <?php
                    $placeholderKey = (string)($placeholder['key'] ?? '');
                    $placeholderValue = rp_template_value($templateContext[$placeholderKey] ?? '');
                    ?>
                    <button type="button" class="rp-template-chip js-report-insert" data-value="<?php echo htmlspecialchars($placeholderValue); ?>" title="<?php echo htmlspecialchars((string)$placeholder['tag']); ?>">
                        <?php echo htmlspecialchars((string)$placeholder['label']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </aside>
        <div class="rp-editor-area">
            <p class="rp-editor-hint">Type directly, load a template, or pull in viewer notes. Drafts save automatically.</p>
            <textarea id="finalReportTextarea" class="rp-report-textarea" placeholder="Type the final report here..." <?php echo $isFinalized ? 'readonly' : ''; ?>><?php echo htmlspecialchars($reportDraft); ?></textarea>
        </div>
    </div>
    <div class="rp-report-footer">
        <button class="rp-btn rp-btn-ghost" type="button" id="copyNotesToReportBtn">Use Viewer Notes</button>
        <button class="rp-btn rp-btn-ghost" type="button" id="saveDraftBtn" data-studyint="<?php echo htmlspecialchars($row['studyint']); ?>" <?php echo $isFinalized ? 'disabled' : ''; ?>>Save Draft</button>
        <button class="rp-btn rp-btn-final" type="button" id="finalizeReportBtn" data-studyint="<?php echo htmlspecialchars($row['studyint']); ?>" <?php echo $isFinalized ? 'disabled' : ''; ?>><?php echo $isFinalized ? 'Finalized' : 'Finalize & Queue Return'; ?></button>
    </div>
</div>
</div>
<?php
        } else {
            echo "<p>No patient details found for the given accession number, or this case is not assigned to your account.</p>";
        }
    } else {
        echo "<p>No accession number provided.</p>";
    }
?>
<div id="dicomViewerModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:99999;">
                <div style="position:absolute; top:3%; left:3%; width:94%; height:94%; background:#0b1220; border-radius:10px; overflow:hidden; border:1px solid #223047; display:flex; flex-direction:column;">
                    <div style="height:48px; display:flex; align-items:center; justify-content:space-between; padding:0 12px; background:#111827; color:#e5e7eb; flex:0 0 auto;">
                        <strong>Online DICOM Viewer</strong>
                        <button id="closeDicomViewerModal" type="button" style="background:#ef4444; border:none; color:#fff; padding:6px 10px; border-radius:6px; cursor:pointer;">Close</button>
                    </div>
                    <div style="display:flex; flex:1 1 auto; min-height:0;">
                        <div style="flex:1 1 auto; min-width:0; border-right:1px solid #1f2937;">
                            <iframe id="dicomViewerFrame" src="" style="width:100%; height:100%; border:none; background:#000;"></iframe>
                        </div>
                        <div style="width:360px; max-width:40%; min-width:300px; background:#0f172a; color:#e5e7eb; display:flex; flex-direction:column;">
                            <div style="padding:12px; border-bottom:1px solid #1f2937; font-weight:600;">Radiologist Notes</div>
                            <div style="padding:12px; display:flex; flex-direction:column; gap:8px; flex:1 1 auto; min-height:0;">
                                <textarea id="dicomNotesTextarea" placeholder="Write findings here..." style="flex:1 1 auto; min-height:180px; resize:vertical; width:100%; border:1px solid #334155; border-radius:6px; background:#020617; color:#e5e7eb; padding:10px;"></textarea>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <button id="dictateStartBtn" type="button" style="background:#2563eb; color:#fff; border:none; border-radius:6px; padding:6px 10px;">Start Dictation</button>
                                    <button id="dictateStopBtn" type="button" style="background:#64748b; color:#fff; border:none; border-radius:6px; padding:6px 10px;">Stop Dictation</button>
                                </div>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <button id="recordStartBtn" type="button" style="background:#16a34a; color:#fff; border:none; border-radius:6px; padding:6px 10px;">Start Recording</button>
                                    <button id="recordStopBtn" type="button" style="background:#64748b; color:#fff; border:none; border-radius:6px; padding:6px 10px;">Stop Recording</button>
                                </div>
                                <div id="recordingStatus" style="font-size:12px; color:#93c5fd;">Recorder idle</div>
                                <audio id="recordingPlayback" controls style="width:100%;"></audio>
                                <a id="downloadRecordingLink" href="#" style="display:none; color:#93c5fd;">Download Recording</a>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <button id="saveNotesBtn" type="button" style="background:#0ea5e9; color:#fff; border:none; border-radius:6px; padding:6px 10px;">Save Notes</button>
                                    <button id="finalizeNotesReportBtn" type="button" style="background:#16a34a; color:#fff; border:none; border-radius:6px; padding:6px 10px;">Finalize Report</button>
                                    <button id="clearNotesBtn" type="button" style="background:#475569; color:#fff; border:none; border-radius:6px; padding:6px 10px;">Clear Notes</button>
                                </div>
                                <div id="notesStatus" style="font-size:12px; color:#94a3b8;">Radiologist notes are saved to the study record in the database.</div>
                                <div id="notesMeta" style="font-size:12px; color:#7dd3fc;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<div id="finalizeConfirmModal" class="rp-confirm-overlay" aria-hidden="true">
    <div class="rp-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="finalizeConfirmTitle">
        <h3 id="finalizeConfirmTitle" class="rp-confirm-title">Finalize Report?</h3>
        <p class="rp-confirm-body">This will queue the report for secure return to the clinic. You can still keep a local copy of your notes.</p>
        <div class="rp-confirm-actions">
            <button type="button" class="rp-confirm-btn rp-confirm-later" id="finalizeConfirmCancel">Later</button>
            <button type="button" class="rp-confirm-btn rp-confirm-primary" id="finalizeConfirmProceed">Finalize Report</button>
        </div>
    </div>
</div>

            <div>
                 <?php              
                                $cid = isset($_GET['editid']) ? (int) $_GET['editid'] : 0;
                                $ret=mysqli_query($con,"select * from  patients where id='$cid'");
                                $cnt=1;
                                while ($ret instanceof mysqli_result && $row=mysqli_fetch_array($ret)) {
                                
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
    
   
   
    </script>
    <script>
    
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

    if (btn && modal) {
        btn.addEventListener("click", openModal);
    }

    if (span && modal) {
        span.addEventListener("click", closeModal);
    }

    // Close the modal if the user clicks outside of it
    window.addEventListener("click", function (event) {
        if (modal && event.target === modal) {
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
        // Show the prebooking modal when the button is clicked
        $('#send_for_report').click(function () {
            $('#compose_report_modal').modal('show');
        });

        // Hide the modal when it's closed
        $('#compose_report_modal').on('hidden.bs.modal', function () {
            $(this).removeClass('show');
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Show the prebooking modal when the button is clicked
        $('#prebookingButton2').click(function () {
            $('#prebookingModal2').modal('show');
        });

        // Hide the modal when it's closed
        $('#prebookingModal2').on('hidden.bs.modal', function () {
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

<script>
    $(document).ready(function () {
        // Show the prebooking modal when the button is clicked
        $('#editPatientButton').click(function () {
            $('#editpatientmodal').modal('show');
        });

        // Hide the modal when it's closed
        $('#editpatientmodal').on('hidden.bs.modal', function () {
            $(this).removeClass('show');
        });
    });
</script>

<script type="text/javascript">
  const bookPatientBtn = document.getElementById('bookPatientBtn');
  if (bookPatientBtn) {
  bookPatientBtn.addEventListener('click', function(event) {
    // Retrieve patient name from button data attribute
    const patientName = event.currentTarget.getAttribute('data-name');
    
    // You can use this data to pre-fill the modal or perform other actions
    document.getElementById('Name').value = patientName;
    
    // Open the modal
    $('#prebookingModal4').modal('show');
});
}
</script>

<script type="text/javascript">
  // Function to open the modal and pre-fill the fields
function openModal(patientName, accessionNumber, requested_procedure, studyint) {
    // Set the values in the modal
    document.getElementById('patientName').value = patientName;
    document.getElementById('accessionNumber').value = accessionNumber;
    document.getElementById('requested_procedure').value = requested_procedure;
    document.getElementById('studyint').value = studyint;

    // Open the modal
    var myModal = new bootstrap.Modal(document.getElementById('reportingModal'));
    myModal.show();
}

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('openImageBtn');
    const dicomModal = document.getElementById('dicomViewerModal');
    const dicomFrame = document.getElementById('dicomViewerFrame');
    const closeDicomModalBtn = document.getElementById('closeDicomViewerModal');

    const notesTextarea = document.getElementById('dicomNotesTextarea');
    const notesStatus = document.getElementById('notesStatus');
    const notesMeta = document.getElementById('notesMeta');
    const saveNotesBtn = document.getElementById('saveNotesBtn');
    const clearNotesBtn = document.getElementById('clearNotesBtn');
    const finalizeNotesReportBtn = document.getElementById('finalizeNotesReportBtn');
    const finalReportTextarea = document.getElementById('finalReportTextarea');
    const finalReportStatus = document.getElementById('finalReportStatus');
    const finalizeReportBtn = document.getElementById('finalizeReportBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const copyNotesToReportBtn = document.getElementById('copyNotesToReportBtn');
    const draftSaveStatus = document.getElementById('draftSaveStatus');
    const returnStatusPill = document.getElementById('returnStatusPill');
    const returnStatusNote = document.getElementById('returnStatusNote');
    const finalizeConfirmModal = document.getElementById('finalizeConfirmModal');
    const finalizeConfirmCancel = document.getElementById('finalizeConfirmCancel');
    const finalizeConfirmProceed = document.getElementById('finalizeConfirmProceed');

    const dictateStartBtn = document.getElementById('dictateStartBtn');
    const dictateStopBtn = document.getElementById('dictateStopBtn');

    const recordStartBtn = document.getElementById('recordStartBtn');
    const recordStopBtn = document.getElementById('recordStopBtn');
    const recordingStatus = document.getElementById('recordingStatus');
    const recordingPlayback = document.getElementById('recordingPlayback');
    const downloadRecordingLink = document.getElementById('downloadRecordingLink');
    const sendToTypistBtn = document.getElementById('sendToTypistBtn');
    const sendToTypistMessage = document.getElementById('sendToTypistMessage');
    const typistWorkflowStatus = document.getElementById('typistWorkflowStatus');
    const typistDraftText = document.getElementById('typistDraftText');
    const useTypistDraftBtn = document.getElementById('useTypistDraftBtn');
    const requestTypistEditsBtn = document.getElementById('requestTypistEditsBtn');
    const approveTypistDraftBtn = document.getElementById('approveTypistDraftBtn');
    const typistEditRequestMessage = document.getElementById('typistEditRequestMessage');
    const directReportModeBtn = document.getElementById('directReportModeBtn');
    const typistReportModeBtn = document.getElementById('typistReportModeBtn');
    const directReportPanel = document.getElementById('directReportPanel');
    const typistWorkflowPanel = document.getElementById('typistWorkflowPanel');
    const reportTemplateSearch = document.getElementById('reportTemplateSearch');
    const reportTemplateList = document.getElementById('reportTemplateList');
    const reportTemplateItems = Array.prototype.slice.call(document.querySelectorAll('.rp-template-item'));
    const reportInsertButtons = Array.prototype.slice.call(document.querySelectorAll('.js-report-insert'));
    const templateRenderBaseUrl = '/remotepanda/api/radiologist-template-render.php?accession=<?php echo isset($accession) ? rawurlencode((string)$accession) : ''; ?>';

    if (!btn) return;

    let currentStudyint = '';
    let recognition = null;
    let isDictating = false;
    let mediaRecorder = null;
    let audioChunks = [];
    let currentRecordingUrl = '';
    let notesDirty = false;
    let pendingFinalize = null;
    let finalDraftTimer = null;

    if (window.tinymce && finalReportTextarea) {
        tinymce.init({
            selector: '#finalReportTextarea',
            branding: false,
            height: 430,
            menubar: 'file edit view insert format tools table',
            plugins: 'lists link table code',
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code',
            setup: function (editor) {
                editor.on('input change keyup undo redo', function () {
                    scheduleFinalDraftSave();
                });
                editor.on('init', function () {
                    if (finalReportTextarea.readOnly) {
                        setReportReadonly(true);
                    }
                });
            }
        });
    }

    function setTypistStatus(text, color) {
        if (typistWorkflowStatus) {
            typistWorkflowStatus.textContent = text || '';
            typistWorkflowStatus.style.color = color || '#475569';
        }
    }

    function getReportEditor() {
        return window.tinymce ? tinymce.get('finalReportTextarea') : null;
    }

    function saveReportEditorToTextarea() {
        const editor = getReportEditor();
        if (editor) {
            editor.save();
        }
    }

    function getReportContent() {
        const editor = getReportEditor();
        if (editor) {
            return editor.getContent();
        }
        return finalReportTextarea ? finalReportTextarea.value : '';
    }

    function getReportPlainText(html) {
        const holder = document.createElement('div');
        holder.innerHTML = html || '';
        return (holder.textContent || holder.innerText || '').trim();
    }

    function setReportContent(content) {
        const editor = getReportEditor();
        if (editor) {
            editor.setContent(content || '');
            editor.fire('change');
            editor.focus();
        } else if (finalReportTextarea) {
            finalReportTextarea.value = content || '';
            finalReportTextarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function insertReportContent(content) {
        const editor = getReportEditor();
        if (editor) {
            editor.focus();
            editor.execCommand('mceInsertContent', false, content || '');
        } else if (finalReportTextarea) {
            const start = finalReportTextarea.selectionStart || 0;
            const end = finalReportTextarea.selectionEnd || 0;
            const before = finalReportTextarea.value.slice(0, start);
            const after = finalReportTextarea.value.slice(end);
            finalReportTextarea.value = before + (content || '') + after;
            finalReportTextarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function setReportReadonly(readonly) {
        if (finalReportTextarea) {
            finalReportTextarea.readOnly = !!readonly;
        }
        const editor = getReportEditor();
        if (editor) {
            if (typeof editor.setMode === 'function') {
                editor.setMode(readonly ? 'readonly' : 'design');
            } else {
                editor.getBody().setAttribute('contenteditable', readonly ? 'false' : 'true');
            }
        }
    }

    function setReportingMode(mode) {
        const isTypist = mode === 'typist';
        if (directReportPanel) directReportPanel.classList.toggle('active', !isTypist);
        if (typistWorkflowPanel) typistWorkflowPanel.classList.toggle('active', isTypist);
        if (directReportModeBtn) directReportModeBtn.classList.toggle('active', !isTypist);
        if (typistReportModeBtn) typistReportModeBtn.classList.toggle('active', isTypist);
        try {
            window.localStorage.setItem('remotepanda_reporting_mode', isTypist ? 'typist' : 'direct');
        } catch (err) {}
    }

    async function loadReportTemplate(file, button) {
        if (!file) return;
        if (finalReportStatus) {
            finalReportStatus.textContent = 'Loading template...';
            finalReportStatus.style.color = '#475569';
        }
        try {
            const res = await fetch(templateRenderBaseUrl + '&file=' + encodeURIComponent(file), { cache: 'no-store' });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Could not load template.');
            }
            setReportContent(data.content || '');
            reportTemplateItems.forEach(function (item) { item.classList.remove('active'); });
            if (button) button.classList.add('active');
            if (finalReportStatus) {
                finalReportStatus.textContent = 'Template loaded into report.';
                finalReportStatus.style.color = '#0f766e';
            }
            scheduleFinalDraftSave();
        } catch (err) {
            if (finalReportStatus) {
                finalReportStatus.textContent = (err && err.message ? err.message : 'Could not load template.').toString().slice(0, 180);
                finalReportStatus.style.color = '#b91c1c';
            }
        }
    }

    function openFinalizeConfirm(payload) {
        pendingFinalize = payload;
        if (!finalizeConfirmModal) {
            runFinalizeReport(payload.studyint, payload.reportText);
            return;
        }
        finalizeConfirmModal.style.display = 'flex';
        finalizeConfirmModal.setAttribute('aria-hidden', 'false');
        if (finalizeConfirmProceed) {
            finalizeConfirmProceed.focus();
        }
    }

    function closeFinalizeConfirm() {
        if (finalizeConfirmModal) {
            finalizeConfirmModal.style.display = 'none';
            finalizeConfirmModal.setAttribute('aria-hidden', 'true');
        }
        pendingFinalize = null;
    }

    async function saveFinalDraft(studyint, silent) {
        const sid = resolveStudyint(studyint);
        saveReportEditorToTextarea();
        const draftText = getReportContent();
        if (!sid || !finalReportTextarea) {
            if (draftSaveStatus && !silent) {
                draftSaveStatus.textContent = 'Draft save failed: missing study ID.';
                draftSaveStatus.style.color = '#b91c1c';
            }
            return false;
        }

        if (draftSaveStatus && !silent) {
            draftSaveStatus.textContent = 'Saving draft...';
            draftSaveStatus.style.color = '#475569';
        }

        try {
            const res = await fetch('/remotepanda/api/study-notes.php', {
                cache: 'no-store',
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    studyint: sid,
                    notes: draftText || ''
                })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to save draft.');
            }
            if (draftSaveStatus) {
                draftSaveStatus.textContent = 'Draft saved at ' + new Date().toLocaleTimeString();
                draftSaveStatus.style.color = '#0f766e';
            }
            return true;
        } catch (err) {
            if (draftSaveStatus && !silent) {
                draftSaveStatus.textContent = 'Draft save failed: ' + (err && err.message ? err.message : 'Unknown error');
                draftSaveStatus.style.color = '#b91c1c';
            }
            return false;
        }
    }

    function scheduleFinalDraftSave() {
        if (!finalReportTextarea || finalReportTextarea.readOnly || !saveDraftBtn) {
            return;
        }
        if (finalDraftTimer) {
            clearTimeout(finalDraftTimer);
        }
        if (draftSaveStatus) {
            draftSaveStatus.textContent = 'Unsaved draft changes...';
            draftSaveStatus.style.color = '#92400e';
        }
        finalDraftTimer = setTimeout(function () {
            saveFinalDraft(saveDraftBtn.dataset.studyint || '', true);
        }, 1500);
    }

    async function runFinalizeReport(sid, reportText) {
        if (!finalizeReportBtn) return;
        finalizeReportBtn.disabled = true;
        if (finalReportStatus) {
            finalReportStatus.textContent = 'Finalizing report...';
            finalReportStatus.style.color = '#475569';
        }

        try {
            const res = await fetch('/remotepanda/api/finalize-report.php', {
                cache: 'no-store',
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    studyint: sid,
                    report_text: reportText
                })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Could not finalize report.');
            }
            if (finalReportStatus) {
                finalReportStatus.textContent = data.message || 'Report finalized and queued.';
                finalReportStatus.style.color = '#15803d';
            }
            if (draftSaveStatus) {
                draftSaveStatus.textContent = 'Final report locked.';
                draftSaveStatus.style.color = '#475569';
            }
            if (returnStatusPill) {
                returnStatusPill.textContent = 'Return: QUEUED';
                returnStatusPill.className = 'rp-order-pill warn';
            }
            if (returnStatusNote) {
                returnStatusNote.textContent = 'This report is finalized and queued for clinic pickup.';
                returnStatusNote.style.display = 'block';
            }
            setReportReadonly(true);
            if (saveDraftBtn) {
                saveDraftBtn.disabled = true;
            }
            finalizeReportBtn.textContent = 'Queued for Clinic Return';
            autoPushCloudReturns();
        } catch (err) {
            if (finalReportStatus) {
                finalReportStatus.textContent = (err && err.message ? err.message : 'Could not finalize report.').toString().slice(0, 180);
                finalReportStatus.style.color = '#b91c1c';
            }
            finalizeReportBtn.disabled = false;
        }
    }

    async function autoPushCloudReturns() {
        try {
            const res = await fetch('/remotepanda/api/cloud-push-returned-reports.php?limit=10', {
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await res.json();
            const sent = data && data.summary ? parseInt(data.summary.sent || 0, 10) : 0;
            const failed = data && data.summary ? parseInt(data.summary.failed || 0, 10) : 0;
            if (sent > 0) {
                if (finalReportStatus) {
                    finalReportStatus.textContent = 'Report finalized and pushed to Cloud for clinic return.';
                    finalReportStatus.style.color = '#15803d';
                }
                if (returnStatusPill) {
                    returnStatusPill.textContent = 'Return: SENT';
                    returnStatusPill.className = 'rp-order-pill ok';
                }
                if (returnStatusNote) {
                    returnStatusNote.textContent = 'This report has been pushed to Cloud and is waiting for clinic pickup.';
                    returnStatusNote.style.display = 'block';
                }
            } else if (failed > 0 && finalReportStatus) {
                finalReportStatus.textContent = 'Report finalized. Cloud push will retry in the background.';
                finalReportStatus.style.color = '#92400e';
            }
        } catch (err) {
            if (finalReportStatus) {
                finalReportStatus.textContent = 'Report finalized. Cloud push will retry in the background.';
                finalReportStatus.style.color = '#92400e';
            }
        }
    }

    async function postTypistWorkflow(action, payload) {
        const sid = resolveStudyint((payload && payload.studyint) || (sendToTypistBtn ? sendToTypistBtn.dataset.studyint : ''));
        if (!sid) {
            throw new Error('Missing study ID.');
        }
        const body = Object.assign({}, payload || {}, { action: action, studyint: sid });
        const res = await fetch('/remotepanda/api/typist-workflow.php', {
            cache: 'no-store',
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Typist workflow update failed.');
        }
        return data;
    }

    async function uploadDictationBlob(audioBlob, mimeType) {
        const sid = resolveStudyint(currentStudyint || (sendToTypistBtn ? sendToTypistBtn.dataset.studyint : ''));
        if (!sid || !audioBlob || !audioBlob.size) {
            return;
        }
        const form = new FormData();
        const extension = (mimeType || '').indexOf('ogg') !== -1 ? 'ogg' : 'webm';
        form.append('studyint', sid);
        form.append('note_text', notesTextarea ? notesTextarea.value.trim().slice(0, 1000) : '');
        form.append('audio', audioBlob, 'dictation-' + Date.now() + '.' + extension);

        if (recordingStatus) {
            recordingStatus.textContent = 'Saving recording for typists...';
        }
        const res = await fetch('/remotepanda/api/upload-dictation.php', {
            cache: 'no-store',
            method: 'POST',
            body: form
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Could not save recording.');
        }
        if (recordingStatus) {
            recordingStatus.textContent = data.message || 'Recording saved for typists.';
        }
        setTypistStatus('Dictation saved. Refresh to see it in the list.', '#0f766e');
    }

    function updateNotesMeta(lastSavedBy, lastSavedAt, hasMetaColumns) {
        if (!notesMeta) return;
        if (!hasMetaColumns) {
            notesMeta.textContent = 'Metadata columns not configured yet (radiologist_notes_updated_by, radiologist_notes_updated_at).';
            return;
        }
        if (!lastSavedBy && !lastSavedAt) {
            notesMeta.textContent = 'Not yet saved.';
            return;
        }
        notesMeta.textContent = 'Last saved by: ' + (lastSavedBy || '-') + ' at ' + (lastSavedAt || '-');
    }
    let notesAutoSaveTimer = null;

    function resolveStudyint(candidateStudyint, viewerUrl) {
        const direct = (candidateStudyint || '').trim();
        if (direct) return direct;

        const fromInput = ((document.getElementById('studyint') || {}).value || '').trim();
        if (fromInput) return fromInput;

        try {
            if (viewerUrl) {
                const u = new URL(viewerUrl, window.location.origin);
                const fromUrl = (u.searchParams.get('studyint') || '').trim();
                if (fromUrl) return fromUrl;
            }
        } catch (e) {}

        return '';
    }

        async function saveNotesToServer(studyint) {
        const sid = resolveStudyint(studyint);
        if (!sid || !notesTextarea) {
            if (notesStatus) {
                notesStatus.textContent = 'Save failed: missing study ID.';
            }
            return;
        }

        try {
            const res = await fetch('/remotepanda/api/study-notes.php', {
                cache: 'no-store',
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    studyint: sid,
                    notes: notesTextarea.value || ''
                })
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to save notes');
            }

            notesDirty = false;
            updateNotesMeta(data.last_saved_by || '', data.last_saved_at || '', !!data.meta_columns_present);
            if (notesStatus) {
                notesStatus.textContent = 'Notes saved at ' + new Date().toLocaleTimeString();
            }
        } catch (err) {
            if (notesStatus) {
                notesStatus.textContent = 'Save failed: ' + (err && err.message ? err.message : 'Unknown error');
            }
        }
    }
        async function loadNotesFromServer(studyint) {
        const sid = resolveStudyint(studyint);
        if (!sid || !notesTextarea) {
            if (notesStatus) {
                notesStatus.textContent = 'Load failed: missing study ID.';
            }
            return;
        }

        try {
            const res = await fetch('/remotepanda/api/study-notes.php?studyint=' + encodeURIComponent(sid), { cache: 'no-store' });
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Failed to load notes');
            }

            notesTextarea.value = normalizeLoadedNotes(data.notes || '');
            notesDirty = false;
            updateNotesMeta(data.last_saved_by || '', data.last_saved_at || '', !!data.meta_columns_present);
            if (notesStatus) {
                notesStatus.textContent = notesTextarea.value ? 'Loaded saved notes from database.' : 'No saved notes yet for this study.';
            }
        } catch (err) {
            notesTextarea.value = '';
            if (notesStatus) {
                notesStatus.textContent = 'Load failed: ' + (err && err.message ? err.message : 'Unknown error');
            }
        }
    }

    function normalizeLoadedNotes(rawNotes) {
        if (!rawNotes) return '';

        const hasHtml = /<[^>]+>/.test(rawNotes);
        if (!hasHtml) {
            return rawNotes;
        }

        const htmlWithBreaks = rawNotes
            .replace(/<\s*br\s*\/?>/gi, '\n')
            .replace(/<\s*\/p\s*>/gi, '\n');

        const temp = document.createElement('div');
        temp.innerHTML = htmlWithBreaks;

        return (temp.textContent || temp.innerText || '')
            .replace(/\u00a0/g, ' ')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }
    function scheduleNotesAutoSave() {
        if (!currentStudyint) return;
        if (notesAutoSaveTimer) {
            clearTimeout(notesAutoSaveTimer);
        }
        notesAutoSaveTimer = setTimeout(function () {
            saveNotesToServer(currentStudyint);
        }, 1000);
    }

    function resetRecordingUi() {
        if (recordingPlayback) {
            recordingPlayback.removeAttribute('src');
            recordingPlayback.load();
        }
        if (downloadRecordingLink) {
            downloadRecordingLink.style.display = 'none';
            downloadRecordingLink.href = '#';
        }
    }

    function stopDictation() {
        if (recognition && isDictating) {
            recognition.stop();
        }
        isDictating = false;
        if (notesStatus) {
            notesStatus.textContent = 'Dictation stopped.';
        }
    }

    function openDicomModal(viewerUrl, studyint) {
        currentStudyint = resolveStudyint(studyint, viewerUrl);
        notesDirty = false;
        if (!dicomModal || !dicomFrame) {
            window.open(viewerUrl, '_blank');
            return;
        }

        loadNotesFromServer(currentStudyint);
        resetRecordingUi();

        dicomFrame.src = viewerUrl;
        dicomModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeDicomModal() {
        if (!dicomModal || !dicomFrame) return;

        if (currentStudyint && notesDirty) {
            saveNotesToServer(currentStudyint);
        }

        stopDictation();

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }

        dicomFrame.src = '';
        dicomModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (closeDicomModalBtn) {
        closeDicomModalBtn.addEventListener('click', closeDicomModal);
    }

    if (dicomModal) {
        dicomModal.addEventListener('click', function (e) {
            if (e.target === dicomModal) {
                closeDicomModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && finalizeConfirmModal && finalizeConfirmModal.style.display === 'flex') {
            closeFinalizeConfirm();
            return;
        }
        if (e.key === 'Escape' && dicomModal && dicomModal.style.display === 'block') {
            closeDicomModal();
        }
    });

    if (saveNotesBtn) {
        saveNotesBtn.addEventListener('click', async function () {
            if (!currentStudyint) {
                currentStudyint = resolveStudyint('');
            }
            notesDirty = true;
            await saveNotesToServer(currentStudyint);
        });
    }

    if (clearNotesBtn) {
        clearNotesBtn.addEventListener('click', function () {
            if (!notesTextarea) return;
            notesTextarea.value = '';
            if (currentStudyint) {
                notesDirty = true;
                scheduleNotesAutoSave();
            }
            if (notesStatus) {
                notesStatus.textContent = 'Notes cleared.';
            }
        });
    }

    if (notesTextarea) {
        notesTextarea.addEventListener('input', function () {
            if (currentStudyint) {
                notesDirty = true;
                scheduleNotesAutoSave();
            }
        });
    }

    function isLocalSecureOrigin() {
        return ['localhost', '127.0.0.1', '::1'].indexOf(window.location.hostname) !== -1;
    }

    function setMicStatus(message, target) {
        const el = target || recordingStatus || notesStatus;
        if (el) {
            el.textContent = message;
        }
    }

    function microphoneHelpText(err) {
        const secureHint = (!window.isSecureContext && !isLocalSecureOrigin())
            ? ' Open this page with https:// before trying again.'
            : '';
        const name = err && err.name ? err.name : '';
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            return 'Microphone is blocked by the browser. Click the microphone/site icon in the address bar, allow microphone access, then refresh.' + secureHint;
        }
        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            return 'No microphone was found on this device.';
        }
        if (name === 'NotReadableError' || name === 'TrackStartError') {
            return 'The microphone is already in use by another app.';
        }
        return 'Microphone access failed.' + secureHint;
    }

    async function requestMicrophoneStream(target) {
        if (!window.isSecureContext && !isLocalSecureOrigin()) {
            const httpsUrl = 'https://' + window.location.host + window.location.pathname + window.location.search + window.location.hash;
            throw new Error('Microphone needs a secure page. Open this page with HTTPS: ' + httpsUrl);
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Audio recording is not supported in this browser.');
        }
        try {
            setMicStatus('Requesting microphone permission...', target);
            return await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (err) {
            throw new Error(microphoneHelpText(err));
        }
    }

    async function checkMicrophonePermission() {
        if (!navigator.permissions || !navigator.permissions.query) {
            return;
        }
        try {
            const permission = await navigator.permissions.query({ name: 'microphone' });
            if (permission.state === 'denied') {
                setMicStatus('Microphone is blocked. Use the address bar site settings to allow it, then refresh.');
            } else if (permission.state === 'prompt') {
                setMicStatus('Microphone permission has not been granted yet. Click Start Recording or Start Dictation to allow it.');
            } else if (permission.state === 'granted') {
                setMicStatus('Microphone ready.');
            }
        } catch (err) {
            // Some browsers do not expose microphone permissions through this API.
        }
    }

    checkMicrophonePermission();

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-US';

        recognition.onresult = function (event) {
            if (!notesTextarea) return;

            let interimText = '';
            let finalText = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalText += transcript + ' ';
                } else {
                    interimText += transcript;
                }
            }

            if (finalText) {
                notesTextarea.value = (notesTextarea.value ? notesTextarea.value + ' ' : '') + finalText.trim();
                if (currentStudyint) {
                    notesDirty = true;
                    scheduleNotesAutoSave();
                }
            }

            if (notesStatus) {
                notesStatus.textContent = interimText ? ('Listening: ' + interimText) : 'Dictation active...';
            }
        };

        recognition.onerror = function (event) {
            isDictating = false;
            if (notesStatus) {
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    notesStatus.textContent = 'Dictation is blocked. Allow microphone access in the address bar, then refresh.';
                } else {
                    notesStatus.textContent = 'Dictation error: ' + event.error;
                }
            }
        };

        recognition.onend = function () {
            if (isDictating) {
                try {
                    recognition.start();
                } catch (err) {
                    isDictating = false;
                }
            }
        };
    } else {
        if (dictateStartBtn) dictateStartBtn.disabled = true;
        if (dictateStopBtn) dictateStopBtn.disabled = true;
        if (notesStatus) notesStatus.textContent = 'Dictation not supported in this browser.';
    }

    if (dictateStartBtn) {
        dictateStartBtn.addEventListener('click', async function () {
            if (!recognition) return;
            let stream = null;
            try {
                stream = await requestMicrophoneStream(notesStatus);
                stream.getTracks().forEach(function (track) { track.stop(); });
                isDictating = true;
                recognition.start();
                if (notesStatus) {
                    notesStatus.textContent = 'Dictation started.';
                }
            } catch (err) {
                isDictating = false;
                if (stream) {
                    stream.getTracks().forEach(function (track) { track.stop(); });
                }
                if (notesStatus) {
                    notesStatus.textContent = (err && err.message ? err.message : 'Could not start dictation.').toString().slice(0, 220);
                }
            }
        });
    }

    if (dictateStopBtn) {
        dictateStopBtn.addEventListener('click', stopDictation);
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
        if (recordStartBtn) recordStartBtn.disabled = true;
        if (recordStopBtn) recordStopBtn.disabled = true;
        if (recordingStatus) recordingStatus.textContent = 'Audio recording not supported in this browser.';
    } else {
        if (recordStartBtn) {
            recordStartBtn.addEventListener('click', async function () {
                let stream = null;
                try {
                    stream = await requestMicrophoneStream(recordingStatus);
                    mediaRecorder = new MediaRecorder(stream);
                    audioChunks = [];

                    mediaRecorder.ondataavailable = function (event) {
                        if (event.data && event.data.size > 0) {
                            audioChunks.push(event.data);
                        }
                    };

                    mediaRecorder.onstop = function () {
                        const mimeType = (mediaRecorder && mediaRecorder.mimeType) ? mediaRecorder.mimeType : 'audio/webm';
                        const audioBlob = new Blob(audioChunks, { type: mimeType });

                        if (currentRecordingUrl) {
                            URL.revokeObjectURL(currentRecordingUrl);
                        }
                        currentRecordingUrl = URL.createObjectURL(audioBlob);

                        if (recordingPlayback) {
                            recordingPlayback.src = currentRecordingUrl;
                        }

                        if (downloadRecordingLink) {
                            const extension = mimeType.indexOf('ogg') !== -1 ? 'ogg' : 'webm';
                            downloadRecordingLink.href = currentRecordingUrl;
                            downloadRecordingLink.download = 'study-' + (currentStudyint || 'recording') + '-dictation.' + extension;
                            downloadRecordingLink.style.display = 'inline-block';
                        }

                        if (recordingStatus) {
                            recordingStatus.textContent = 'Recording stopped. Saving it for typists...';
                        }

                        uploadDictationBlob(audioBlob, mimeType).catch(function (err) {
                            if (recordingStatus) {
                                recordingStatus.textContent = (err && err.message ? err.message : 'Could not save recording.').toString().slice(0, 160);
                            }
                        });

                        stream.getTracks().forEach(function (t) { t.stop(); });
                    };

                    mediaRecorder.start();
                    if (recordingStatus) {
                        recordingStatus.textContent = 'Recording...';
                    }
                } catch (err) {
                    if (stream) {
                        stream.getTracks().forEach(function (t) { t.stop(); });
                    }
                    if (recordingStatus) {
                        recordingStatus.textContent = (err && err.message ? err.message : 'Microphone access denied or unavailable.').toString().slice(0, 220);
                    }
                }
            });
        }

        if (recordStopBtn) {
            recordStopBtn.addEventListener('click', function () {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }
            });
        }
    }

    btn.addEventListener('click', function () {
        const studyint = resolveStudyint(this.dataset.studyint || '');
        const statusEl = document.getElementById('imageStatus');
        if (!studyint) {
            if (statusEl) {
                statusEl.textContent = 'Study identifier missing.';
                statusEl.style.color = '#b91c1c';
            }
            return;
        }

        if (statusEl) {
            statusEl.textContent = 'Opening viewer...';
            statusEl.style.color = '#475569';
        }

        openDicomModal('/remotepanda/viewer/index.php?studyint=' + encodeURIComponent(studyint) + '&embed=1', studyint);

        if (statusEl) {
            statusEl.textContent = 'Viewer opened.';
            statusEl.style.color = '#15803d';
        }
    });

    if (directReportModeBtn) {
        directReportModeBtn.addEventListener('click', function () { setReportingMode('direct'); });
    }

    if (typistReportModeBtn) {
        typistReportModeBtn.addEventListener('click', function () { setReportingMode('typist'); });
    }

    try {
        const savedMode = window.localStorage.getItem('remotepanda_reporting_mode');
        setReportingMode(savedMode === 'typist' ? 'typist' : 'direct');
    } catch (err) {
        setReportingMode('direct');
    }

    reportTemplateItems.forEach(function (item) {
        item.addEventListener('click', function () {
            loadReportTemplate(item.dataset.templateFile || '', item);
        });
    });

    if (reportTemplateSearch) {
        reportTemplateSearch.addEventListener('input', function () {
            const q = reportTemplateSearch.value.toLowerCase();
            reportTemplateItems.forEach(function (item) {
                item.style.display = item.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : '';
            });
        });
    }

    reportInsertButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            insertReportContent(button.dataset.value || '');
            scheduleFinalDraftSave();
        });
    });

    if (copyNotesToReportBtn) {
        copyNotesToReportBtn.addEventListener('click', function () {
            if (!finalReportTextarea || !notesTextarea) return;
            if (finalReportTextarea.readOnly) {
                if (finalReportStatus) {
                    finalReportStatus.textContent = 'This report is already finalized.';
                    finalReportStatus.style.color = '#475569';
                }
                return;
            }
            setReportContent(notesTextarea.value || getReportContent() || '');
            if (finalReportStatus) {
                finalReportStatus.textContent = 'Viewer notes copied into final report draft.';
                finalReportStatus.style.color = '#0f766e';
            }
            scheduleFinalDraftSave();
        });
    }

    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function () {
            saveFinalDraft(saveDraftBtn.dataset.studyint || '', false);
        });
    }

    if (sendToTypistBtn) {
        sendToTypistBtn.addEventListener('click', async function () {
            sendToTypistBtn.disabled = true;
            setTypistStatus('Sending to typist queue...', '#475569');
            try {
                const data = await postTypistWorkflow('send_to_typist', {
                    studyint: sendToTypistBtn.dataset.studyint || '',
                    message: sendToTypistMessage ? sendToTypistMessage.value : ''
                });
                setTypistStatus(data.message || 'Sent to typist queue.', '#0f766e');
            } catch (err) {
                setTypistStatus((err && err.message ? err.message : 'Could not send to typist.').toString().slice(0, 180), '#b91c1c');
                sendToTypistBtn.disabled = false;
            }
        });
    }

    if (useTypistDraftBtn) {
        useTypistDraftBtn.addEventListener('click', function () {
            if (!finalReportTextarea || !typistDraftText) return;
            setReportContent(typistDraftText.textContent || '');
            setReportingMode('direct');
            scheduleFinalDraftSave();
            setTypistStatus('Typist draft copied into final report.', '#0f766e');
        });
    }

    if (requestTypistEditsBtn) {
        requestTypistEditsBtn.addEventListener('click', async function () {
            requestTypistEditsBtn.disabled = true;
            setTypistStatus('Returning draft to typist...', '#475569');
            try {
                const data = await postTypistWorkflow('request_edits', {
                    studyint: requestTypistEditsBtn.dataset.studyint || '',
                    message: typistEditRequestMessage ? typistEditRequestMessage.value : ''
                });
                setTypistStatus(data.message || 'Returned to typist for edits.', '#0f766e');
            } catch (err) {
                setTypistStatus((err && err.message ? err.message : 'Could not request edits.').toString().slice(0, 180), '#b91c1c');
                requestTypistEditsBtn.disabled = false;
            }
        });
    }

    if (approveTypistDraftBtn) {
        approveTypistDraftBtn.addEventListener('click', async function () {
            approveTypistDraftBtn.disabled = true;
            setTypistStatus('Approving draft and queuing clinic return...', '#475569');
            try {
                const data = await postTypistWorkflow('approve_draft', {
                    studyint: approveTypistDraftBtn.dataset.studyint || ''
                });
                setTypistStatus(data.message || 'Draft approved and queued for return.', '#0f766e');
                if (finalReportTextarea && typistDraftText) {
                    setReportContent(typistDraftText.textContent || getReportContent());
                    setReportReadonly(true);
                }
                if (finalReportStatus) {
                    finalReportStatus.textContent = data.message || 'Report finalized and queued.';
                    finalReportStatus.style.color = '#15803d';
                }
                if (finalizeReportBtn) {
                    finalizeReportBtn.disabled = true;
                    finalizeReportBtn.textContent = 'Queued for Clinic Return';
                }
            } catch (err) {
                setTypistStatus((err && err.message ? err.message : 'Could not approve draft.').toString().slice(0, 180), '#b91c1c');
                approveTypistDraftBtn.disabled = false;
            }
        });
    }

    if (finalReportTextarea) {
        finalReportTextarea.addEventListener('input', scheduleFinalDraftSave);
    }

    if (finalizeReportBtn) {
        finalizeReportBtn.addEventListener('click', function () {
            const sid = resolveStudyint(finalizeReportBtn.dataset.studyint || '');
            saveReportEditorToTextarea();
            const reportText = getReportContent();
            if (!sid) {
                if (finalReportStatus) finalReportStatus.textContent = 'Missing study ID.';
                return;
            }
            if (!getReportPlainText(reportText)) {
                if (finalReportStatus) {
                    finalReportStatus.textContent = 'Final report cannot be blank.';
                    finalReportStatus.style.color = '#b91c1c';
                }
                return;
            }
            saveFinalDraft(sid, true);
            openFinalizeConfirm({ studyint: sid, reportText: reportText });
        });
    }

    if (finalizeConfirmCancel) {
        finalizeConfirmCancel.addEventListener('click', closeFinalizeConfirm);
    }

    if (finalizeConfirmModal) {
        finalizeConfirmModal.addEventListener('click', function (e) {
            if (e.target === finalizeConfirmModal) {
                closeFinalizeConfirm();
            }
        });
    }

    if (finalizeConfirmProceed) {
        finalizeConfirmProceed.addEventListener('click', function () {
            const payload = pendingFinalize;
            closeFinalizeConfirm();
            if (payload) {
                runFinalizeReport(payload.studyint, payload.reportText);
            }
        });
    }

    if (finalizeNotesReportBtn) {
        finalizeNotesReportBtn.addEventListener('click', function () {
            if (finalReportTextarea && notesTextarea && notesTextarea.value.trim()) {
                setReportContent(notesTextarea.value.trim());
            }
            if (finalizeReportBtn) {
                finalizeReportBtn.click();
            }
        });
    }
});
</script>




</body>
</html>
<?php  ?>























