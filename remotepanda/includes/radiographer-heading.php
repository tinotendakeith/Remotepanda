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
                            while ($ret instanceof mysqli_result && $row=mysqli_fetch_array($ret)) {
                            
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
.rp-rad-header-tools {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    min-width: 0;
    padding-top: 4px;
}
.rp-rad-header-slot {
    display: flex;
    width: 76%;
    padding-left: 0;
    align-items: center;
}
.rp-rad-search {
    position: relative;
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    margin: 0;
}
.rp-rad-search-trigger {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 999px;
    background: #ed1b24;
    color: #fff;
    box-shadow: 0 8px 20px rgba(237, 27, 36, .18);
}
.rp-rad-search-panel {
    display: none;
    position: absolute;
    top: 46px;
    left: 0;
    z-index: 10000;
    width: min(420px, 72vw);
    padding: 9px;
    background: #fff;
    border: 1px solid #d7e4f5;
    border-radius: 14px;
    box-shadow: 0 16px 38px rgba(8,34,72,.18);
    gap: 7px;
}
.rp-rad-search.is-open .rp-rad-search-panel,
.rp-rad-search:focus-within .rp-rad-search-panel {
    display: flex;
}
.rp-rad-search input {
    height: 38px;
    border-radius: 999px;
    border: 1px solid #d7e4f5;
    padding: 0 14px;
    width: 100%;
    color: #0b2446;
}
.rp-rad-search-submit,
.rp-rad-search-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    min-width: 42px;
    padding: 0 12px;
    border-radius: 999px;
    border: 0;
    background: #ed1b24;
    color: #fff;
    font-weight: 700;
    text-decoration: none;
}
.rp-rad-search-clear {
    background: #f3f7ff;
    color: #0b2446;
    border: 1px solid #d7e4f5;
}
.rp-rad-nav {
    display: flex;
    align-items: center;
    gap: 7px;
    flex: 0 0 auto;
}
.rp-rad-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 34px;
    padding: 7px 11px;
    border-radius: 999px;
    background: #f3f7ff;
    border: 1px solid #d7e4f5;
    color: #0b2446;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}
.rp-rad-pill.primary {
    background: #ed1b24;
    border-color: #ed1b24;
    color: #fff;
}
.rp-rad-pill.dark {
    background: #01152a;
    border-color: #01152a;
    color: #fff;
}
.rp-rad-pill .count {
    display: inline-flex;
    min-width: 20px;
    height: 20px;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border-radius: 999px;
    background: rgba(255,255,255,.85);
    color: #0b2446;
    font-size: 11px;
}
.rp-rad-more {
    position: relative;
}
.rp-rad-more-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 40px;
    width: 210px;
    background: #fff;
    border: 1px solid #d7e4f5;
    border-radius: 12px;
    box-shadow: 0 14px 35px rgba(8,34,72,.16);
    padding: 6px;
    z-index: 9999;
}
.rp-rad-more:hover .rp-rad-more-menu,
.rp-rad-more:focus-within .rp-rad-more-menu {
    display: block;
}
.rp-rad-more-menu a {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    padding: 9px 10px;
    border-radius: 9px;
    color: #0b2446;
    text-decoration: none;
    font-weight: 700;
    font-size: 12px;
}
.rp-rad-more-menu a:hover {
    background: #f3f7ff;
}
@media (max-width: 1280px) {
    .rp-rad-pill span.label { display: none; }
    .rp-rad-pill { padding: 7px 9px; }
    .rp-rad-search-panel { width: min(360px, 78vw); }
}
</style> 

<?php
$rpHeaderUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : array();
$rpHeaderUserType = strtolower((string)($rpHeaderUser['user_type'] ?? ($rpHeaderUser['type'] ?? '')));
$rpIsRadiologistHeader = $rpHeaderUserType === 'radiologist';
$rpHeaderProfileImage = (string)($rpHeaderUser['profile_image'] ?? '');
if ($rpHeaderProfileImage === '' && isset($con) && $con instanceof mysqli && !empty($rpHeaderUser['id'])) {
    $rpHeaderUserId = (int)$rpHeaderUser['id'];
    $rpHeaderImageResult = @mysqli_query($con, "SELECT profile_image FROM users WHERE id = {$rpHeaderUserId} LIMIT 1");
    if ($rpHeaderImageResult && ($rpHeaderImageRow = mysqli_fetch_assoc($rpHeaderImageResult))) {
        $rpHeaderProfileImage = (string)($rpHeaderImageRow['profile_image'] ?? '');
        if ($rpHeaderProfileImage !== '') {
            $_SESSION['user']['profile_image'] = $rpHeaderProfileImage;
        }
    }
}
$rpHeaderProfileImageUrl = '../../extensions/images/download (1).png';
if ($rpHeaderProfileImage !== '') {
    if (preg_match('/^https?:\/\//i', $rpHeaderProfileImage)) {
        $rpHeaderProfileImageUrl = $rpHeaderProfileImage;
    } else {
        $rpHeaderProfileImageUrl = '../../' . ltrim(str_replace('\\', '/', $rpHeaderProfileImage), '/');
    }
}
$rpRadiologistHeaderCounts = array(
    'new' => 0,
    'progress' => 0,
    'drafts' => 0,
    'finalized' => 0,
    'return_queue' => 0
);
if ($rpIsRadiologistHeader && isset($con) && $con instanceof mysqli) {
    if (!function_exists('rp_remote_reporting_current_user')) {
        @include_once(__DIR__ . '/remote_reporting_service.php');
    }
    if (!function_exists('rp_typist_workflow_ensure_schema')) {
        @include_once(__DIR__ . '/typist_workflow_service.php');
    }
    if (function_exists('rp_remote_reporting_current_user') && function_exists('rp_remote_reporting_assignment_sql') && function_exists('rp_remote_reporting_bind')) {
        @rp_remote_reporting_ensure_schema($con);
        if (function_exists('rp_typist_workflow_ensure_schema')) {
            @rp_typist_workflow_ensure_schema($con);
        }
        $rpHeaderReporter = rp_remote_reporting_current_user();
        $rpHeaderAssignment = rp_remote_reporting_assignment_sql($rpHeaderReporter, 's', 'r');
        $rpHeaderJoin = "LEFT JOIN remote_report_orders r ON r.id = (
            SELECT rr.id FROM remote_report_orders rr
            WHERE rr.studyint = s.studyint OR rr.accession_number = CAST(s.accession_number AS CHAR)
            ORDER BY rr.id DESC LIMIT 1
        )";
        $rpHeaderSql = "SELECT
            SUM(CASE WHEN r.viewed_at IS NULL AND COALESCE(r.status, s.status) IN ('received','sent_to_cloud','Awaiting Report') THEN 1 ELSE 0 END) AS new_count,
            SUM(CASE WHEN s.status = 'In Progress' OR r.status IN ('in_progress','dictated','with_typist','needs_typist_edits') THEN 1 ELSE 0 END) AS progress_count,
            SUM(CASE WHEN d.status = 'typed_draft_ready' THEN 1 ELSE 0 END) AS drafts_count,
            SUM(CASE WHEN s.status = 'Finalized' OR r.status = 'reported' THEN 1 ELSE 0 END) AS finalized_count,
            SUM(CASE WHEN ro.status = 'queued' THEN 1 ELSE 0 END) AS return_queue_count
            FROM study s
            {$rpHeaderJoin}
            LEFT JOIN report_typist_drafts d ON d.studyint = s.studyint
            LEFT JOIN remote_report_return_outbox ro ON ro.order_uid = r.order_uid
            WHERE {$rpHeaderAssignment['sql']}";
        $rpHeaderStmt = @mysqli_prepare($con, $rpHeaderSql);
        if ($rpHeaderStmt) {
            rp_remote_reporting_bind($rpHeaderStmt, $rpHeaderAssignment['types'], $rpHeaderAssignment['params']);
            @mysqli_stmt_execute($rpHeaderStmt);
            $rpHeaderRes = @mysqli_stmt_get_result($rpHeaderStmt);
            if ($rpHeaderRes && ($rpHeaderRow = mysqli_fetch_assoc($rpHeaderRes))) {
                $rpRadiologistHeaderCounts['new'] = (int)($rpHeaderRow['new_count'] ?? 0);
                $rpRadiologistHeaderCounts['progress'] = (int)($rpHeaderRow['progress_count'] ?? 0);
                $rpRadiologistHeaderCounts['drafts'] = (int)($rpHeaderRow['drafts_count'] ?? 0);
                $rpRadiologistHeaderCounts['finalized'] = (int)($rpHeaderRow['finalized_count'] ?? 0);
                $rpRadiologistHeaderCounts['return_queue'] = (int)($rpHeaderRow['return_queue_count'] ?? 0);
            }
            @mysqli_stmt_close($rpHeaderStmt);
        }
    }
}
?>


<div class="sticky-header header-section ">
     <div style="width: 77% !important" class="header-left">
        <toggle button start>
        <button style="background:#fff;" id="showLeftPush"><img style="width:54px;" src="../../extensions/images/favicon.png"></i></button>
        <toggle button end>
        <!--logo -->
        <div style="background: #fff;" class="logo">
          <a style="padding: 0.9em 1.05em 1.6em 0em;" href="index.php">
            <h1><img style="width: 155px;margin-top: 11px;" src="../../extensions/images/logo1.png"></h1>
          </a>
          
        </div>
        <!--//logo-->
       <div class="rp-rad-header-slot">
           <?php if ($rpIsRadiologistHeader): ?>
           <div class="rp-rad-header-tools">
             <form class="rp-rad-search" method="get" action="index.php" data-rp-rad-search>
               <button class="rp-rad-search-trigger" type="button" aria-label="Search cases" title="Search cases" data-rp-rad-search-toggle>
                 <i class="fa fa-search nav_icon"></i>
               </button>
               <div class="rp-rad-search-panel">
                 <input placeholder="Search patient, accession, referrer" ID="searchdata" type="text" name="q" value="<?php echo htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                 <button class="rp-rad-search-submit" type="submit">Go</button>
                 <?php if (trim((string)($_GET['q'] ?? '')) !== ''): ?>
                 <a class="rp-rad-search-clear" href="index.php">Clear</a>
                 <?php endif; ?>
               </div>
             </form>
             <nav class="rp-rad-nav" aria-label="Radiologist shortcuts">
               <a class="rp-rad-pill primary" href="index.php?status=Awaiting+Report" title="New and unopened cases">
                 <i class="fa fa-inbox"></i><span class="label">New</span><span class="count"><?php echo (int)$rpRadiologistHeaderCounts['new']; ?></span>
               </a>
               <a class="rp-rad-pill dark" href="index.php?status=In+Progress" title="Cases already started">
                 <i class="fa fa-pen"></i><span class="label">In Progress</span><span class="count"><?php echo (int)$rpRadiologistHeaderCounts['progress']; ?></span>
               </a>
               <a class="rp-rad-pill" href="index.php?status=Pending+Verification" title="Typed drafts waiting for approval">
                 <i class="fa fa-file-signature"></i><span class="label">Drafts</span><span class="count"><?php echo (int)$rpRadiologistHeaderCounts['drafts']; ?></span>
               </a>
               <div class="rp-rad-more">
                 <button class="rp-rad-pill" type="button" title="More tools">
                   <i class="fa fa-ellipsis-h"></i><span class="label">More</span>
                 </button>
                 <div class="rp-rad-more-menu">
                   <a href="index.php?status=Return+Queued"><span>Return Queue</span><strong><?php echo (int)$rpRadiologistHeaderCounts['return_queue']; ?></strong></a>
                   <a href="index.php?status=Finalized"><span>Finalized</span><strong><?php echo (int)$rpRadiologistHeaderCounts['finalized']; ?></strong></a>
                   <a href="view_templates.php"><span>Templates</span><i class="fa fa-chevron-right"></i></a>
                   <a href="profile.php"><span>Profile</span><i class="fa fa-chevron-right"></i></a>
                 </div>
               </div>
             </nav>
           </div>
           <?php else: ?>
           
           <form method="post" name="search" action="">
              <?php /* Optional page message placeholder. */ ?>

  
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
      
                              <div style="display:flex;margin-left: 27%;">
                       <button id="addPatientButton" style="background-color: #ed1b24; border: none; color: white; padding: 7px 15px; border-radius: 16px; height: 42%;
    margin-top: 9%;">View all Patients +</button>
                            
                       <button id="prebookingButton" style="background-color:#01152a;border: none;color: #fff;padding: 7px 15px;border-radius: 16px; margin-left: 5px;height: 42%;
    margin-top: 9%;">Waiting List</button>
              
                    </div>
          <?php endif; ?>
       </div>
       
       <div>
           
       </div>
       
        <div class="clearfix"> </div>
      </div>
      <div class="header-right">
        <div class="profile_details_left"><!--notifications of menu start -->
        <!--<meta http-equiv="refresh" content="50">-->

          <ul class="nofitications-dropdown">
    <?php 
        // Assuming you have the current username stored in the session
        $currentUsername = $_SESSION['user']['username'];

        // Modify the SQL query to include a condition for the current user
        $ret1 = mysqli_query($con, "SELECT * FROM study WHERE status='Not Scanned' AND technician_name  = '$currentUsername'");
        $num = mysqli_num_rows($ret1);
    ?>  
    <li class="dropdown head-dpdn">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <i style="color:#01152a;" class="fa fa-bell"></i>
            <span style="background-color:#ed1b24;" class="badge blue"><?php echo $num;?></span>
        </a>
        <ul class="dropdown-menu">
            <li>
                <div class="notification_header">
                    <h3>You have <?php echo $num;?> Incoming patients</h3>
                </div>
            </li>
            <li>
                <div class="notification_desc">
                    <?php 
                    if ($num > 0) {
                        while ($result = mysqli_fetch_array($ret1)) {  
                    ?>
                        <a class="dropdown-item" href="view-appointment.php?viewid=<?php echo $result['study_id'];?>">
                            <span style ="background:#01152a; color:#fff; width:128%; display:block; padding: 2px 10px 2px 20px !important; margin: 0px !important; margin-bottom: 5px !important; border-radius:20px ">
                                <?php echo $result['Name'];?>
                            </span>
                        </a>
                    <?php 
                        }
                    } else {
                    ?>
                        <a class="dropdown-item" href="remarks.php">No New Patients</a>
                    <?php } ?>
                </div>
                <div class="clearfix"></div>  
            </li>
            <li>
                <div class="notification_bottom">
                    <a href="all-patients.php">See all Patients</a>
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
                   <span class="prfil-img"><img src="<?php echo htmlspecialchars($rpHeaderProfileImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="50" height="50" style="object-fit:cover;border-radius:50%;"> </span> <div class="user-name">
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
        const datetimeElements = document.querySelectorAll("[data-rp-clock], #datetime");
        if (!datetimeElements.length) {
            return;
        }
        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric' };
        const formattedDateTime = now.toLocaleDateString('en-US', options);
        datetimeElements.forEach(function(datetimeElement) {
            datetimeElement.textContent = formattedDateTime;
        });
    }

    // Call the function initially to display the date and time when the page loads
    updateDateTime();

    // Update the date and time every second (1000 milliseconds)
    setInterval(updateDateTime, 1000);

    document.querySelectorAll('[data-rp-rad-search]').forEach(function(form) {
        const toggle = form.querySelector('[data-rp-rad-search-toggle]');
        const input = form.querySelector('input[name="q"]');
        if (!toggle || !input) {
            return;
        }
        toggle.addEventListener('click', function() {
            form.classList.toggle('is-open');
            if (form.classList.contains('is-open')) {
                setTimeout(function() {
                    input.focus();
                    input.select();
                }, 20);
            }
        });
        document.addEventListener('click', function(event) {
            if (!form.contains(event.target)) {
                form.classList.remove('is-open');
            }
        });
    });
</script>
