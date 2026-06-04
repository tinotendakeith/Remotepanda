<?php
session_start();
error_reporting(0);

include('../../includes/dbconnection.php');
include('../../functions.php');
if (!isLoggedIn()) {
	$_SESSION['msg'] = "You must log in first";
	header('location: ../../index.php');
}

if (isset($_GET['logout'])) {
	session_destroy();
	unset($_SESSION['user']);
	header("location: ../../index.php");
}

else{

$value = '';
$loadedTemplateContent = '';
$currentUser = isset($_SESSION['user']['username']) ? trim((string) $_SESSION['user']['username']) : '';

if (isset($_POST['template-submit'])) {
    $cid = isset($_GET['viewid']) ? (int) $_GET['viewid'] : 0;
    $value = basename((string) ($_POST['templateed'] ?? ''));

    if ($value === '') {
        echo '<script>alert("Pick a Template to Display");</script>';
    } elseif ($currentUser === '') {
        echo '<script>alert("Your session is missing a username. Please log in again.");</script>';
    } else {
        $templateStmt = mysqli_prepare($con, "SELECT temp_file FROM Templates WHERE Author = ? AND temp_file = ? LIMIT 1");
        if ($templateStmt) {
            mysqli_stmt_bind_param($templateStmt, 'ss', $currentUser, $value);
            mysqli_stmt_execute($templateStmt);
            $templateResult = mysqli_stmt_get_result($templateStmt);
            $templateRow = $templateResult ? mysqli_fetch_assoc($templateResult) : null;
            mysqli_stmt_close($templateStmt);
        } else {
            $templateRow = null;
        }

        if (!$templateRow) {
            echo '<script>alert("Template not found for your account.");</script>';
            $value = '';
        } else {
            $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . basename((string) $templateRow['temp_file']);
            if (!is_file($templatePath)) {
                echo '<script>alert("Template file could not be found.");</script>';
                $value = '';
            } else {
                $loadedTemplateContent = (string) file_get_contents($templatePath);
                $query = false;
                $updateStmt = mysqli_prepare($con, "UPDATE `study` SET `status`='In Progress', `templateed`=? WHERE `study`.`study_id`=?");
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, 'si', $value, $cid);
                    $query = mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                }

                if ($query) {
                    $msg = "Template Loaded";
                } else {
                    $msg = "Try Again";
                }
            }
        }
    }
}

if (isset($_POST['remarks-submit'])) {
    
      $cont= $_POST['textspace'];
      $content = $_REQUEST['textarea'];
      $cid=$_GET['viewid'];

   $query=mysqli_query($con, "UPDATE `study` SET `textarea`='$cont', `status`='scanned'  WHERE `study`.`study_id`='$cid'");
   $query=mysqli_query($con, "UPDATE `events` SET `status`='scanned', `color`='red' WHERE `events`.`id`='$cid'");

 
    if ($query) {
    echo "<script>alert('Remarks have been Added.');</script>"; 
    echo "<script>window.location.href = 'sono-scanned.php?viewid=$cid'</script>"; 
    

  }
  else
    {
      echo "<script>alert('Please Try Again.');</script>"; 
      
    }
   //do something here;
}







?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>RADPANDA|| View Appointment</title>
<meta charset=UTF-8>
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Bootstrap Core CSS -->
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<!-- Custom CSS -->
<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">


<!-- font CSS -->
<!-- font-awesome icons -->
<link href="../../extensions/css/font-awesome.css" rel="stylesheet"> 
<!-- //font-awesome icons -->
 <!-- js-->
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/tinymce/js/tinymce/tinymce.min.js"></script>
<script>tinymce.init({ selector:'#pclu-textarea',plugins :"save", menubar : false });</script>
<script src="../../extensions/js/modernizr.custom.js"></script>
<script>tinymce.init({ selector:'#textarea', branding: false });</script>
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

* width */
::-webkit-scrollbar {
  width: 10px;
}

/* Track */
::-webkit-scrollbar-track {
  background: #f1f1f1;
}

/* Handle */
::-webkit-scrollbar-thumb {
  background: #888;
}

/* Handle on hover */
::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
</head> 
<body class="cbp-spmenu-push">
	<div class="main-content" style="height:900px;">
		<!--left-fixed -navigation-->
		 <?php include_once('../../includes/radiographer-sidebar.php');?>
		<!--left-fixed -navigation-->
		<!-- header-starts -->
		 <?php include_once('../../includes/radiographer-heading.php');?>
		<!-- //header-ends -->
		<!-- main content start-->
		<div id="page-wrapper">
			<div class="main-page">
				<div class="tables">
				
					<div class="table-responsive bs-example widget-shadow">
						
						<?php
                            $cid=$_GET['viewid'];
                            $ret=mysqli_query($con,"select * from study where accession_number='$cid'");
                            $cnt=1;
                            while ($row=mysqli_fetch_array($ret)) {
                            
                        ?>
                        <form name="template-submit" method="post" enctype="multipart/form-data">
                        <table class="table table-bordered">
                                 

                                              <tr>
                                                <th style="width: 30%">
                                                    <p style="font-size: 20px;">Name: <?php  echo $row['Name'];?></p>
                                                    <p style="font-size: 20px;">Study: <?php  echo $row['requested_procedure'];?></p>
                                                    <p style="font-size: 20px;">Status: <?php  echo $row['status'];?></p>
                                                    <p style="font-size: 20px;">Date of Birth: <?php  echo $row['date_of_birth'];?></p>
                                                    <p style="font-size: 20px;">Gender: <?php  echo $row['gender'];?></p>


                                                </th>

                                                <td>
                                                    <?php
                                                    include('../../includes/dbconnection.php');
                                                    
                                                    $cid=$_GET['viewid'];
                                                    $select = "SELECT * FROM `events` where id='$cid'";
                                                                                $cnt=1;
                                                        $result = $con->query($select);
                                                        while($row = $result->fetch_object()){
                                                          $pdf = $row->filename;
                                                         
                                                            
                                                    }
                                            
                                                    ?>
                                                 <iframe src="<?php echo "../../extensions/pdf/$pdf"; ?>" width="90%" height="400px"> </iframe>   
                                                </td>
                                              </tr>
                                              
                                               <tr>
                                                <th>Templates :</th>
                                                <td>
                                                    <?php
                                                     $user = $_SESSION['user']['username'];
                                                    ?>
                                                    <select name="templateed" id="templateed" class="form-control">
                                            		 	<option value="<?php if (isset($_POST['temp_file'])){echo htmlentities($_POST['temp_file']); }?>">Select Template</option>
                                            		      <?php
                                                          $templateListStmt = mysqli_prepare($con, "SELECT temp_file FROM Templates WHERE Author = ? ORDER BY temp_file ASC");
                                                          if ($templateListStmt) {
                                                              mysqli_stmt_bind_param($templateListStmt, 's', $user);
                                                              mysqli_stmt_execute($templateListStmt);
                                                              $templateListResult = mysqli_stmt_get_result($templateListStmt);
                                                              while($row=mysqli_fetch_array($templateListResult))
                                                                  {
                                                                  ?>
                                            		      <option name="templateed" id="templatee" value="<?php echo htmlentities($row['temp_file']);?>"><?php echo htmlentities($row['temp_file']);?></option>
                                            		      <?php }
                                                              mysqli_stmt_close($templateListStmt);
                                                          } ?> 
                                            		  </select>
                                                   </td>
                                                 </tr>
                                                  
                                                  
                                   
                                   
                                    <p  style="font-size:12px;">You are editing <?php echo $value; ?></p>
                                                  
                                                  
                                          <tr align="center">
                                             <td colspan="2">
                                              <button type="submit" name="template-submit" class="btn btn-az-primary pd-x-20">Load Template</button>
                                                <button type="button" id="openModalBtn" class="btn btn-az-primary pd-x-20" data-toggle="modal" data-target="#blankModal">
                                                    Start Exam
                                                </button>
                                                <button type="button" id="openModalrecord" class="btn btn-az-primary pd-x-20" data-toggle="modal" data-target="#blankModal">
                                                    Record
                                                </button>
                                                
                                             </td>                                                                                                
                                         </tr>
                                              
                                              
                                              
                                              
                            </table>
                            
                       
                            
      </form>
        
        <!-- Display the countdown timer in an element -->
        <!--<p id="demo"></p>-->
     


<!-- Modal Structure -->
<div class="modal" id="blankModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div style="width:89%; height:80%;" class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">You are editing <?php echo $value; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Your modal content goes here -->
        <form name="remarks-submit" method="post" enctype="multipart/form-data">
           
                        <table class="table table-bordered">


                                              
                                    <tr style="display:center;">
                                    <center><p>
                                     
                                           <textarea style="height:800px;"  name="textspace" id="textarea" value="" class="tinymce" cols="90" rows="20" >
                                            <?php if (isset($_POST['textspace'])){ $item = $_POST['textspace']; echo htmlentities($item, ENT_IGNORE, "UTF-8");  }?>
                                            <?php echo htmlspecialchars($loadedTemplateContent, ENT_NOQUOTES, 'UTF-8'); ?></textarea>
                                        
                                      </p></center>
                                  </tr>
                                                 
                                                  
                                                  <tr align="center">
                                                <td colspan="2">
                                                    <button type="submit" name="remarks-submit" class="btn btn-az-primary pd-x-20">END EXAM</button>
                                                    
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                </td>
                                                    
                                              </tr>
                                              
                                              
                            </table>
                            
                       
                            
      </form>
      </div>
      <div class="modal-footer">
        
      </div>
    </div>
  </div>
</div>

  
						<?php } ?>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<!--footer-->
		
        <!--//footer-->
	</div>
	

<!-- Modal for Recording Audio -->
<div class="modal fade" id="audioModal" tabindex="-1" role="dialog" aria-labelledby="audioModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="audioModalLabel">Audio Recording</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <button id="startRecording" class="btn btn-primary">Start Recording</button>
                <button id="stopRecording" class="btn btn-danger" disabled>Stop Recording</button>
            </div>
        </div>
    </div>
</div>



</body>
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
     
  <script>
// Set the date we're counting down to
var countDownDate = new Date("Feb 14, 2023 15:37:25").getTime();

// Update the count down every 1 second
var x = setInterval(function() {

  // Get today's date and time
  var now = new Date().getTime();

  // Find the distance between now and the count down date
  var distance = countDownDate - now;

  // Time calculations for days, hours, minutes and seconds
  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Display the result in the element with id="demo"
  document.getElementById("demo").innerHTML = days + "d " + hours + "h "
  + minutes + "m " + seconds + "s ";

  // If the count down is finished, write some text
  if (distance < 0) {
    clearInterval(x);
    document.getElementById("demo").innerHTML = "EXPIRED";
  }
}, 1000);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var audioModal = new bootstrap.Modal(document.getElementById('audioModal'));
        var startRecordingBtn = document.getElementById('startRecording');
        var stopRecordingBtn = document.getElementById('stopRecording');
        var recorder;

        startRecordingBtn.addEventListener('click', function () {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(function (stream) {
                    recorder = new MediaRecorder(stream);

                    var chunks = [];

                    recorder.ondataavailable = function (e) {
                        if (e.data.size > 0) {
                            chunks.push(e.data);
                        }
                    };

                    recorder.onstop = function () {
                        var audioBlob = new Blob(chunks, { type: 'audio/mp3' });
                        var audioUrl = URL.createObjectURL(audioBlob);

                        // Send the audio data to the server using AJAX or save it in any desired way
                        // You may want to add a hidden input field to the form to store the audio data

                        // Example: Save audio as an MP3 file
                        var a = document.createElement('a');
                        a.href = audioUrl;
                        a.download = 'audio.mp3';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    };

                    recorder.start();
                    startRecordingBtn.disabled = true;
                    stopRecordingBtn.disabled = false;
                })
                .catch(function (error) {
                    console.error('Error accessing microphone:', error);
                });
        });

        stopRecordingBtn.addEventListener('click', function () {
            if (recorder && recorder.state !== 'inactive') {
                recorder.stop();
                startRecordingBtn.disabled = false;
                stopRecordingBtn.disabled = true;
            }
        });

        // Handle modal show event
        $('#blankModal').on('shown.bs.modal', function () {
            audioModal.show();
        });

        // Handle modal hide event
        $('#blankModal').on('hidden.bs.modal', function () {
            if (recorder && recorder.state !== 'inactive') {
                recorder.stop();
            }
            startRecordingBtn.disabled = false;
            stopRecordingBtn.disabled = true;
        });
    });
</script>



  <!--scrolling js-->
  <script src="../../extensions/js/jquery.nicescroll.js"></script>
  <script src="../../extensions/js/scripts.js"></script>
  <!--//scrolling js-->
  <!-- Bootstrap Core JavaScript -->
  <script src="../../extensions/js/bootstrap.js"> </script>

<!-- Add jQuery script reference (if not already included) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<script>
  // Wait for the document to be ready
  $(document).ready(function () {
    // Add click event handler for the "Start Exam" button
    $("#openModalBtn").click(function () {
      // Show the modal
      $("#blankModal").modal("show");
    });
  });
</script>


</html>
<?php  ?>
