<?php $tab="2.6"; $pagetitle="My Reports"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 004                    #
# 08-07-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 004 Fixed bug #144
# 003 Fixed a sorting bug
# 002 Added option to always send the report
# 001 Added language support
####################################

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_userid",
                "int_m",
                "sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(subjecta|subjectd|last_senta|last_sentd|templatea|templated|detaila|detaild|activea|actived)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
  if ($sql_sort != "") {
    add_to_sql("$sql_sort", "order");
  }
} else {
  $sql_sort = " last_sent ASC";
  $sort = "last_senta";
}

# Make sure all access rights are correct
if (isset($clean['userid'])) {
  $user_id = $clean['userid'];
  if ($s_access_user < 1) {
    header("location: index.php");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT * FROM login WHERE organisation = $s_org AND id = $user_id";
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      $user_id = $s_userid;
    } else {
      $user_id = $clean['userid'];
    }
  } else {
    $user_id = $clean['userid'];
  }
} else {
  $user_id = $s_userid;
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($s_access_user > 0) {
  if ($s_access_user < 9) {
    $sql_user = "SELECT username FROM login ";
    $sql_user .= "WHERE id = $user_id AND login.organisation = '$q_org' ";
  } else {
    $sql_user = "SELECT username FROM login WHERE id = $user_id ";
  }
  $debuginfo[] = $sql_user;
  $result_user = pg_query($sql_user);
  $row = pg_fetch_assoc($result_user);
  $username = $row['username'];

  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<a href='report_new.php?int_userid=$user_id'>" .$l['mr_addreport']. "</a><br />";
          echo "<a href='report_mod.php?int_userid=$user_id&a=d&md5_hash=$s_hash'>" .$l['mr_disableall']. "</a><br />";
          echo "<a href='report_mod.php?int_userid=$user_id&a=e&md5_hash=$s_hash'>" .$l['mr_enableall']. "</a><br />";
          echo "<a href='report_mod.php?int_userid=$user_id&a=r&md5_hash=$s_hash'>" .$l['mr_resetall']. "</a>";
        echo "</div>\n";
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</blockContent>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>

  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        if ($s_access_user > 1) {
          echo "<div class='blockHeader'>";
            echo "<div class='blockHeaderLeft'>" .$l['mr_reportsof']. " $username</div>\n";
            echo "<div class='blockHeaderRight'>\n";
              echo "<form name='viewform' action='$url' method='GET'>\n";
                $sql_users = "SELECT login.id, username, organisations.organisation FROM login, organisations ";
                $sql_users .= " WHERE login.organisation = organisations.id ";
                if ($q_org != 0) {
                  $sql_users .= " AND login.organisation = '$q_org' ";
                }
                $sql_users .= " ORDER BY login.username ASC ";
                $debuginfo[] = $sql_users;
                $result_users = pg_query($pgconn, $sql_users);
                echo "<select name='int_userid' class='smallselect' onChange='javascript: this.form.submit();'>\n";
                  echo printOption("", "", $user_id);
                  while ($row = pg_fetch_assoc($result_users)) {
                    $uid = $row['id'];
                    $username = $row['username'];
                    $org = $row['organisation'];
                    if ($s_access_user == 9) {
                      echo printOption($uid, "$username - $org", $user_id);
                    } else {
                      echo printOption($uid, $username, $user_id);
                    }
                  }
                echo "</select>\n";
              echo "</form>\n";
            echo "</div>\n";
          echo "</div>\n";
        } else {
          echo "<div class='blockHeader'>" .$l['mr_reportsof']. " $username</div>\n";
        }
        echo "<div class='blockContent'>";
          echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='250'>" .printsort($l['mr_title'], "subject"). "</th>\n";
              echo "<th width='120'>" .printsort($l['mr_lastsent'], "last_sent"). "</th>\n";
              echo "<th width='100'>" .printsort($l['mr_temp'], "template"). "</th>\n";
              echo "<th width='170'>" .$l['mr_timeopts']. "</th>\n";
              echo "<th width='170' class='norightb'>" .printsort($l['g_type'], "detail"). "</th>\n";
              echo "<th width='16' class='noleftb'></th>\n";
              echo "<th width='60'>" .printsort($l['g_status'], "status"). "</th>\n";
              echo "<th>" .$l['g_delete']. "</th>\n";
            echo "</tr>\n";

            # Get reports
            if ($s_access_user < 9) {
              $sql = "SELECT report_content.* FROM report_content, login ";
              $sql .= "WHERE user_id = $user_id AND report_content.user_id = login.id AND login.organisation = '$s_org' ";
            } else {
              $sql = "SELECT * FROM report_content WHERE user_id = $user_id ";
            }
            if ($sql_sort != "") {
              $sql .= " ORDER BY $sql_sort";
            }
            $result_report_content = pg_query($sql);
            $debuginfo[] = $sql;
            $rssfeed = array();

            while ($report_content = pg_fetch_assoc($result_report_content)) {
              $rcid = $report_content['id'];
              $subject = $report_content['subject'];
              $active = $report_content['active'];
              $last_sent = $report_content['last_sent'];
              $template = $report_content['template'];
              $freq = $report_content['frequency'];
              $int = $report_content['interval'];
              $threshold = $report_content['threshold'];
              $sev = $report_content['severity'];
              $op = $report_content['operator'];
              $qs = $report_content['qs'];
              $from_ts = $report_content['from_ts'];
              $to_ts = $report_content['to_ts'];
              $always = $report_content['always'];
              $detail = $report_content['detail'];
              $public = $report_content['public'];
              if ($freq == 1) {
                $freqstring = $l['g_hourly'];
              } elseif ($freq == 2) {
                $freqstring = $l['sv_daily'] . " " .$l['g_at_l']. " ${int}:00";
              } elseif ($freq == 3) {
                $freqstring = $l['sv_weekly'] . " " .$l['g_on_l']. " $v_weekdays[$int]";
              } elseif ($freq == 4) {
                if ($sev == -1) {
                  $freqstring = $l['g_all'] ." $v_mail_operator_ar[$op] $threshold";
                } else {
                  $freqstring = "$v_severity_ar[$sev] $v_mail_operator_ar[$op] $threshold";
                }
              }
              if ($detail == 10 || $detail == 11) {
                $freqstring = "N/A";
              }
              if ($template == 6) {
                if ($int != -1) {
                  $freqstring = sec_to_string($int, 3);
                } elseif ($from_ts != -1 && $to_ts != -1) {
                  $from_date = date($c_date_format_notime, $from_ts);
                  $to_date = date($c_date_format_notime, $to_ts);
                  $freqstring = "$from_date - $to_date";
                } else {
                  $freqstring = "N/A";
                }
              } elseif ($template == 5 || $template == 7) {
                $freqstring = $l['mr_instant'];
              }              
              if ($active == "t") {
                $status = "<font style='color:green;'>" .$l['mr_active']. "</font";
              } else {
                $status = "<font style='color:red;'>" .$l['mr_inactive']. "</font>";
              }
              if ($last_sent == null) {
                $last_sent = "<i>" .$l['mr_never']. "</i>";
              } else {
                $last_sent = date($c_date_format_short, $last_sent);
              }

              if ($detail > 9 && $active == "t") {
                $rssfeed[$rcid] = $subject;
              }

              if (strlen($subject) > 40) {
                $subtext = substr($subject, 0, 40) ."...";
                $subtext = "<font " .printover($subject). ">$subtext</font>";
              } else {
                $subtext = $subject;
              }

              echo "<tr>\n";
                echo "<td>";
                  if ($template != 6) {
                    echo "<a href='report_edit.php?int_userid=$user_id&int_rcid=$rcid'>$subtext</a>";
                  } else {
                    echo "$subtext";
                  }
                echo "</td>\n";
                echo "<td>" . $last_sent . "</td>\n";
                echo "<td>" . $v_mail_template_ar[$template] . "</td>\n";
                echo "<td>";
                  echo $freqstring;
                  if ($always == 1) {
                    echo " (" .$l['mr_always']. ")";
                  }
                echo "</td>\n";
                if ($template == 6) {
                  echo "<td class='norightb'>" .$l['mr_result']. "</td>\n";
                } else {
                  if ($public == "t") {
                    echo "<td class='norightb'>(<b>" .$l['mr_public']. "</b>) " . $v_mail_detail_ar[$detail] . "</td>\n";
                  } else {
                    echo "<td class='norightb'>" . $v_mail_detail_ar[$detail] . "</td>\n";
                  }
                }
                if ($detail == 10 || $detail == 11) {
                  if ($active == "t") {
                    if ($public == "t") {
                      echo "<td class='noleftb'><a href='pubfeed.php?int_rcid=$rcid'><img src='images/rss.gif' height='16' width='16' /></a></td>\n";
                    } else {
                      echo "<td class='noleftb'><a href='rssfeed.php?int_rcid=$rcid'><img src='images/rss.gif' height='16' width='16' /></a></td>\n";
                    }
                  } else {
                    echo "<td class='noleftb'></td>\n";
                  }
                } elseif ($template == 6) {
                  if ($int != -1) {
                    $qs .= "&int_interval=$int";
                  } elseif ($to_ts != -1 && $from_ts != -1) {
                    $qs .= "&int_to=$to_ts&int_from=$from_ts";
                  }
                  echo "<td class='noleftb'>" .downlink("logsearch.php?$qs", ""). "</td>\n";
                } else {
                  echo "<td class='noleftb'></td>\n";
                }
                echo "<td>" . $status . "</td>\n";
                echo "<td><a href='report_del.php?int_userid=$user_id&int_rcid=$rcid' onclick=\"javascript: return confirm('" .$l['mr_confirmdel']. "?');\">[" .$l['g_delete']. "]</a></td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
          foreach ($rssfeed as $rcid => $sub) {
            echo "<link rel='alternate' title='SURF IDS RSS: $sub' type='application/rss+xml' href='$c_webinterface_prefix/rssfeed.php?int_rcid=$rcid'>\n";
          }
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</center>
}

debug_sql();
pg_close($pgconn);
footer();
?>
