 <?php
                            if(isset($_POST['search']))
                            { 
                            
                            $sdata=$_POST['searchdata'];
                              ?>
                              
                            <?php
                            $ret=mysqli_query($con,"select *from patients where patient_name like '%$sdata%'");
                            $num=($ret instanceof mysqli_result) ? mysqli_num_rows($ret) : 0;
                            if($num>0){
                            $cnt=1;
                            while ($row=mysqli_fetch_array($ret)) {
                            
                            ?>
                            
                            <?php 
                        $cnt=$cnt+1;
                        } } else { ?>
                        
<?php } }?>
<?php include_once(__DIR__ . '/skeleton-loader.php'); ?>

<style type="text/css">
  .searchbar {
    float: right;
    padding: 6px;
    border: 1px solid #fafbfd;
    margin-top: 14px;
    border-radius: 10px;
    height: 48px;
    width: 55%;
    margin-right: 16px;
    font-size: 17px;
}
</style> 


<div class="sticky-header header-section ">
      <div style="width: 77% !important" class="header-left">
        <toggle button start>
        <button style="background:#ffffff;" id="showLeftPush"><img style="width:54px;" src="../../extensions/images/favicon.png"></i></button>
        <toggle button end>
        <!--logo -->
        <div style="background: #ffffff;" class="logo">
          <a style="padding: 0.9em 1.05em 1.6em 0em;" href="index.php">
            <h1><img style="width: 155px;margin-top: 11px;" src="../../extensions/images/logo1.png"></h1>
          </a>
          
        </div>
        <!--//logo-->
       <div style="display: flex;    width: 76%;    padding-left: 0%;">
           
           <form method="post" name="search" action="">
							<p style="font-size:16px; color:red" align="center"> <?php if($msg){echo $msg; }  ?> </p>

  
							 <div style="margin-left: 27%;   margin-top: 4.7%;   width: 107%;" class="form-group">  
							 	<div style="display:flex">
							 	    <input style="width:44%; border-radius: 20px; " placeholder="Search Patient" ID="searchdata" type="text" Name="searchdata"  class="form-control">
							        <button style="width: 3%;padding: 0px 26px 0px 10px;  margin-right: 10%; border-radius: 19px;" type="submit" name="search" class="btn"><i class="fa fa-search nav_icon"></i></button>
							      
							      <div style="width: 24%;">
							          <p style="width: 198%; padding: 5px 0px 0px 0px;" id="datetime"></p> <!-- This is where the date and time will be displayed -->
							      </div>
							      
							      
							      
							  </div>
							    
				 </div>
			</form>
			
	                            <div style="display:flex;margin-left: 30%;">
							         <button id="addPatientButton" style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px; height: 42%;
    margin-top: 9%;">Add Patient +</button>
                            
							         <button id="prebookingButton" style="background-color:#01152a;border: none;color: #fff;padding: 7px 15px;border-radius: 16px; margin-left: 5px;height: 42%;
    margin-top: 9%;">Book Patients</button>
							
							      </div>
       </div>
       
       <div>
           
       </div>
       
        <div class="clearfix"> </div>
      </div>
      <div class="header-right">
        <div class="profile_details_left"><!--notifications of menu start -->
        <!--<meta http-equiv="refresh" content="50">-->

          <ul class="nofitications-dropdown">
            <?php $ret1=mysqli_query($con,"select *from events where status='scanned'"); 
            $num=($ret1 instanceof mysqli_result) ? mysqli_num_rows($ret1) : 0;

            ?>  
            <li class="dropdown head-dpdn">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i style="color:#01152a;" class="fa fa-bell"></i><span style="background-color:#ed1b24;" class="badge blue"><?php echo $num;?></span></a>
              
              <ul class="dropdown-menu">
                <li>
                  <div class="notification_header">
                    <h3>You have <?php echo $num;?> Scanned patients</h3>
                  </div>
                </li>
                <li>
            
                   <div class="notification_desc">
                     <?php if($num>0 && $ret1 instanceof mysqli_result){
                while($result=mysqli_fetch_array($ret1))
                     {  ?>
                      <a class="dropdown-item" href="scanned-patients.php?viewid=<?php echo $result['id'];?>">
                      <span style ="background:#01152a; color:#fff; width:128%; display:block; padding: 2px 10px 2px 20px !important; margin: 0px !important; margin-bottom: 5px !important; border-radius:20px " ><?php echo $result['Name'];?></span> </a><?php }} else {?>
                      <a class="dropdown-item" href="remarks.php">No New Remarks Received</a>
                 <?php } ?>
                           
                  </div>
                  <div class="clearfix"></div>  
                 </a></li>
                 
                
                 <li>
                  <div class="notification_bottom">
                    <a href="remarks.php">See all Remarks</a>
                  </div> 
                </li>
              </ul>
            </li> 
          
          </ul>
          <div class="clearfix"> </div>
        </div>
        <!--notification menu end -->
        <div class="profile_details">  
        

          <ul>
            <li class="dropdown profile_details_drop">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <div class="profile_img"> 
                   <span class="prfil-img"><img src="../../extensions/images/download (1).png" alt="" width="50" height="50"> </span> <div class="user-name">
                    <p  style="color: #ed1b24;"><?php echo $_SESSION['user']['username']; ?></p>
                    <span><?php echo $_SESSION['user']['user_type']; ?></span>
                  </div>
                  <i class="fa fa-angle-down lnr"></i>
                  <i class="fa fa-angle-up lnr"></i>
                  <div class="clearfix"></div>  
                </div>  
              </a>
             <ul class="dropdown-menu drp-mnu">
              <li><a href="change-password.php"><i class="fa fa-cog"></i> Settings</a></li>
              <li><a href="profile.php"><i class="fa fa-user"></i> Profile</a></li>
              <li><a href="../../functions.php?logout=1"><i class="fa fa-sign-out"></i> Logout</a></li>
          </ul>
            </li>
          </ul>
        </div>  
        <div class="clearfix"> </div> 
      </div>
      <div class="clearfix"> </div> 
    </div>
    <script>
    function updateDateTime() {
        const datetimeElement = document.getElementById("datetime");
        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric' };
        const formattedDateTime = now.toLocaleDateString('en-US', options);
        datetimeElement.textContent = formattedDateTime;
    }

    // Call the function initially to display the date and time when the page loads
    updateDateTime();

    // Update the date and time every second (1000 milliseconds)
    setInterval(updateDateTime, 1000);
</script>
