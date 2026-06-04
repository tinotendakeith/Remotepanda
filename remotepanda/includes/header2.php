<?php
include('dbconnection.php');
include('../functions.php');
$searchErr = '';
$employee_details='';
if(isset($_POST['save']))
{
    if(!empty($_POST['search']))
    {
        $search = $_POST['search'];
        $stmt = $con->prepare("select * from tblcustomers where Name like '%$search%' or PhoneNumber like '%$search%'");
        $stmt->execute();
        $Name = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //print_r($employee_details);
         
    }
    else
    {
        $searchErr = "Please enter the information";
    }
    
}
 
?>

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
      <div class="header-left">
        <!--toggle button start-->
        <!-- <button id="showLeftPush"><i class="fa fa-bars"></i></button> -->
        <!--toggle button end-->
        <!--logo -->
        <div style="background: #01152a;" class="logo">
          <a style="padding: 0.9em 2.98em 1.6em;" href="index.php">
            <h1><img style="width: 155px;margin-top: 11px;" src="images/logow.png"></h1>
          </a>
        </div>
        <!--//logo-->
       <input class="searchbar" type="text" id="search" placeholder="Search Patient">
       
        <div class="clearfix"> </div>
      </div>
      <div class="header-right">
        <div class="profile_details_left"><!--notifications of menu start -->
        <meta http-equiv="refresh" content="50">

          <ul class="nofitications-dropdown">
            <?php $ret1=mysqli_query($con,"select *from pdf_data where status='scanned'"); $num=mysqli_num_rows($ret1);

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
                     <?php if($num>0){
                while($result=mysqli_fetch_array($ret1))
                     {  ?>
                      <a class="dropdown-item" href="scanned-patients.php?viewid=<?php echo $result['id'];?>">
                      <span style ="background:#01152a; color:#fff; width:128%; display:block; padding: 2px 10px 2px 20px !important; margin: 0px !important; margin-bottom: 5px !important; border-radius:20px " ><?php echo $result['username'];?></span> </a><?php }} else {?>
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
                   <span class="prfil-img"><img src="images/download (1).png" alt="" width="50" height="50"> </span> <div class="user-name">
                    <p  style="color: #ed1b24;"><?php echo $_SESSION['user']['username']; ?></p>
                    <span><?php echo $_SESSION['user']['user_type']; ?></span>
                  </div>
                  <i class="fa fa-angle-down lnr"></i>
                  <i class="fa fa-angle-up lnr"></i>
                  <div class="clearfix"></div>  
                </div>  
              </a>
              <ul class="dropdown-menu drp-mnu">
                <li> <a href="change-password.php"><i class="fa fa-cog"></i> Settings</a> </li> 
                <li> <a href="admin-profile.php"><i class="fa fa-user"></i> Profile</a> </li> 
                <li> <a href="index.php"><i class="fa fa-sign-out"></i> Logout</a> </li>
              </ul>
            </li>
          </ul>
        </div>  
        <div class="clearfix"> </div> 
      </div>
      <div class="clearfix"> </div> 
    </div>
