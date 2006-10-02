<?php include('menu.php');

####################################
# SURFnet IDS                      #
# Version 1.02.02                  #
# 28-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.02.02 Removed includes
# 1.02.01 Initial release
####################################

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_access = $_SESSION['s_access'];
$s_access_search = $s_access{1};

?>
<h4>Query generation</h4>
<table border='0'>
  <tr>
    <td class='datatd' width='100'><b>SELECT</b></td>
    <td class='datatd'><input type='text' name='q_select' value='' size='50' /></td>
  </tr>
  <tr>
    <td class='datatd' width='100'><b>FROM</b></td>
    <td class='datatd'><input type='text' name='q_from' value='' size='50' /></td>
  </tr>
  <tr>
    <td class='datatd' width='100'><b>WHERE</b></td>
    <td class='datatd'>
      <textarea name='q_where' cols='60' rows='20'></textarea>
    </td>
  </tr>
</table>

<?php footer(); ?>
