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

if(isset($_POST['submit']))
  {
      $cid=$_GET['viewid'];


   $query=mysqli_query($con, "UPDATE `pdf_data` SET `status`='Completed' WHERE `pdf_data`. `id`='$cid'");
   
    if ($query) {
    $msg="Remarks have been send.";

  }
  else
    {
      $msg="Template has been loaded";
      
    }
}



?>

<!DOCTYPE HTML>
<html>
<head>
<title>RADPANDA|| View Scanned Patient</title>

<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<!-- Custom CSS -->
<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<link rel="icon" type="image/x-icon" href="/images/favicon.png">

<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 
<!-- //font-awesome icons -->
 <!-- js-->
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/tinymce/js/tinymce/tinymce.min.js"></script>
<script>tinymce.init({ selector:'#pclu-textarea',plugins :"save", menubar : false });</script>
<script src="../../extensions/js/modernizr.custom.js"></script>
<script>tinymce.init({ selector:'#textarea', branding: false,  });</script>
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
<style>
    .btn {
    display: inline-block;
    padding: 6px 38px !important;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-image: none;
    border: 1px solid transparent;
    border-radius: 4px;
    color: #fff !important;
    background: #ed1b24 !important;
}
</style>
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
					<h3 class="title1">View Scanned Patient</h3>
					
					
				
					<div class="table-responsive bs-example widget-shadow">
						<p style="font-size:16px; color:red" align="center"> <?php if($msg){
                        echo $msg;
                      }  ?> </p>
						<h4>View Scanned Patient:</h4>
						<?php
                            $cid=$_GET['viewid'];
                            $ret=mysqli_query($con,"select * from pdf_data where id='$cid'");
                            $cnt=1;
                            while ($row=mysqli_fetch_array($ret)) {
                            
                        ?>
                        <form name="submit" method="post" enctype="multipart/form-data">
                        <table class="table table-bordered">
                                 

							
                                              <tr>
                                            <th style="width: 26%;">Name</th>
                                                <td><?php  echo $row['username'];?></td>
                                              </tr>
                                            
                                               <tr>
                                                <th>Scan Type</th>
                                                <td><?php  echo $row['Services'];?></td>
                                              </tr>
                                              
                                            <tr>
                                                <th>Booking Date & Time</th>
                                                <td><?php  echo $row['Time'];?></td>
                                              </tr>
                                              <tr>
                                                <th>Status</th>
                                                <td><?php  echo $row['status'];?></td>
                                              </tr>
                                              <tr>
                                                <th>Remarks</th>
                                                <td><textarea  name="textarea" id="textarea" class="tinymce" cols="90" rows="10"><?php echo $row['textarea']; ?></textarea></td>
                                              </tr>
                                              
                                              
                                              
                                              
                                              <tr>
                                                <th>Request Form</th>
                                                <td>
                                                    <?php
                                                    include('includes/dbconnection.php');
                                                    
                                                    $cid=$_GET['viewid'];
                                                    $select = "SELECT * FROM `pdf_data` where id='$cid'";
                                                                                $cnt=1;
                                                        $result = $con->query($select);
                                                        while($row = $result->fetch_object()){
                                                          $pdf = $row->filename;
                                                         
                                                            
                                                    }
                                            
                                                    ?>
                                                 <iframe src="<?php echo "../../extensions/pdf/$pdf"; ?>" width="90%" height="500px"> </iframe>   
                                                </td>
                                              </tr>
                                              
                                               
                                                  
                                                  <tr align="center">
                                                <td colspan="2">
                                                    <button type="submit" name="submit" class="btn btn-az-primary pd-x-20">Complete Process</button>
                                                </td>
                                                    
                                              </tr>
                                              
                                              
                            </table>
                            
                           
                            
      </form>



  
						<?php } ?>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<!--footer-->
		
        <!--//footer-->
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
		
			<script>
	    const targetDiv = document.getElementById("third");
	    const btn =document.getElementById("toggle");
	    btn.onclick = function(){
	        if (targetDiv.style.display !== "none"){
	            targetDiv.style.display = "none";
	            }else{
	                targetDiv.style.display ="block";
	            }
	         };
	</script>
	<script>
			function addressFunction() {
				if (document.getElementById(
				"same").checked) {
					document.getElementById(
					"textarea").value =
					document.getElementById(
					"templateed").value;
				
				
				} else {
					document.getElementById(
					"templateed").value = "";
					
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
<?php  ?>