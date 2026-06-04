<style>
.nav > li > a {
  position: relative;
  display: block;
  padding: 2px 15px;
}
.icho {
  background: #ed1b24;
  padding-top: 11px !important;
  color: #fff !important;
  padding-bottom: 10px !important;
}
.rp-sidebar-clock {
  position: fixed;
  left: 18px;
  bottom: 20px;
  width: 214px;
  padding: 13px 14px;
  border-radius: 14px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.09);
  color: #d8e7f8;
  z-index: 1000;
  pointer-events: none;
}
.rp-sidebar-clock small {
  display: block;
  color: #8fa8c2;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: 4px;
}
.rp-sidebar-clock strong {
  display: block;
  font-size: 12px;
  line-height: 1.4;
}
</style>
<?php
$__rp_user_type = strtolower((string)($_SESSION['user']['user_type'] ?? $_SESSION['user_type'] ?? ''));
$__rp_is_admin = in_array($__rp_user_type, ['admin', 'superadmin', 'owner'], true);
?>
<div class=" sidebar" role="navigation">
  <div class="navbar-collapse">
    <nav style="background-color:#01152a; height:100% " class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left" id="cbp-spmenu-s1">
      <ul class="nav" id="side-menu">
        <?php if ($__rp_user_type === 'typist') { ?>
        <li>
          <a class="icho" href="../typist/index.php"><i class="fa fa-keyboard-o nav_icon"></i>Typist Queue</a>
        </li>
        <li>
          <a href="../typist/index.php?status=Needs+Edits"><i class="fa fa-pencil-square-o nav_icon"></i>Needs Edits</a>
        </li>
        <li>
          <a href="../typist/index.php?status=Ready+for+Radiologist"><i class="fa fa-check-square-o nav_icon"></i>Sent for Approval</a>
        </li>
        <li>
          <a href="../typist/view_templates.php"><i class="fa fa-file-text-o nav_icon"></i>Templates</a>
        </li>
        <?php } else { ?>
        <li>
          <a class="icho" href="index.php"><i class="fa fa-home nav_icon"></i>Dashboard</a>
        </li>

        <li>
          <a href="not-attended.php"><i class="fa fa-list-alt nav_icon"></i>Studies<span class="fa arrow"></span> </a>
          <ul style="background: #032244;" class="nav nav-second-level collapse">
            <li>
              <a href="not-attended.php">New Studies</a>
            </li>
            <li>
              <a href="attended.php">Finalized Studies</a>
            </li>
          </ul>
        </li>

        <li>
          <a href="sono_reports.php"><i style="margin-right: 15px;" class="fa fa-id-card" aria-hidden="true"></i>Reports</a>
        </li>

        <li>
          <a href="template_create.php"><i class="fa fa-hospital-o nav_icon"></i>Templates<span class="fa arrow"></span> </a>
          <ul class="nav nav-second-level collapse">
            <li>
              <a href="view_templates.php">View Templates</a>
            </li>
          </ul>
        </li>

        <li>
          <a href="settings.php"><i class="fa fa-cogs nav_icon"></i>System<span class="fa arrow"></span> </a>
          <ul class="nav nav-second-level collapse">
            <li>
              <a href="settings.php">Remote Settings</a>
            </li>
            <?php if ($__rp_is_admin) { ?>
            <li>
              <a href="profile-options.php">Profile Options</a>
            </li>
            <li>
              <a href="api-audit.php">API Audit Logs</a>
            </li>
            <?php } ?>
          </ul>
        </li>
        <?php } ?>
      </ul>

      <div class="clearfix"> </div>
      <div class="rp-sidebar-clock">
        <small>Local Time</small>
        <strong data-rp-clock></strong>
      </div>
    </nav>
  </div>
</div>
