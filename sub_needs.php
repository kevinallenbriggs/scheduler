<?php #sub_needs.php
$page_title = "Sub Needs" ;
include('./includes/subneedssessionstart.php');

if (isset($_SESSION['came_from'])){
	$came_from = $_SESSION['came_from'];
	}

include('./includes/allsessionvariables.php');
include ('./includes/header.html');
if (($_SESSION['role'] == 'Admin')||($_SESSION['role'] == 'Supervisor')){
	include ('./includes/supersidebar.html');
	}
else {
	include ('./includes/sidebar.html');
	}

$division = 'All';	
	
$today = date('Y-m-d');

function subs(){
	$query = "SELECT employee_number, concat(first_name, ' ', last_name) as employee_name FROM employees 
		WHERE division = 'Subs' and active = 'Active' ORDER BY last_name asc";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)){
		$empno = $row['employee_number'];
		$name = $row['employee_name'];
		$subs[$empno] = $name;
		}
	return $subs;
	}
$subs = subs();

if (($_SESSION['role'] == 'Admin')||($_SESSION['role'] == 'Supervisor')){
?>
	<script>
	function confirmSub(theForm){
		var employee_name = theForm['employee_name'].value;
		var sub_needs_date = theForm['short_date'].value;
		var agree=confirm("Are you sure you wish to confirm "+employee_name+" for "+sub_needs_date+"?");
		if (agree){
			return true ;
			}
		else {
			return false ;
			}
		}
	function deleteSubNeeds(theForm){
		var sub_needs_division = theForm['sub_needs_division'].value;
		var sub_needs_date = theForm['short_date'].value;
		var agree=confirm("Are you sure you wish to delete sub shift on "+sub_needs_date+" for "+sub_needs_division+"?");
		if (agree){
			return true ;}
		else {
			return false ;
			}
		}
	</script>
<?php
	echo '<div class="sub_needs_wrapper"><div class="sub_needs_button"><a href="add_sub_needs">Add Sub Shift</a></div>';
	echo '<span class="date"><h1>Sub Needs</h1></span>'."\n";
	
	if (isset($_POST['add_sub_needs_submitted'])){
		if ($_SESSION['sub_needs_division'] == 'All'){
			$division = 'All';
			}
		else{
			$division = $_POST['sub_needs_division'];
			}
		
		$sn_div = $_POST['sub_needs_division'];
		list($sn_mon, $sn_day, $sn_yr) = explode('/',$_POST['sub_needs_date']);
		$sn_date = "$sn_yr-$sn_mon-$sn_day";

		$sns_hr = $_POST['sub_needs_start']['hours'];
		$sns_mn = $_POST['sub_needs_start']['minutes'];
		if (!empty($sns_hr)){
			if ((!is_numeric($sns_hr)) || ((!empty($sns_mn)) && (!is_numeric($sns_mn)))){
				$errors[] = 'Please enter a valid start time.';
				}
			else {
				if ($sns_hr < 7){$sns_hr = $sns_hr+12;}
				if (empty($sns_mn)){
					$sns_mn = '00';
					}
				$sns_time = "$sns_hr:$sns_mn:00";
				}
			}	
		else{
			$errors[] = 'Please enter a start time for this coverage shift.';
			}
	
		$sne_hr = $_POST['sub_needs_end']['hours'];
		$sne_mn = $_POST['sub_needs_end']['minutes'];
		if (!empty($sne_hr)){
			if ((!is_numeric($sne_hr)) || ((!empty($sne_mn)) && (!is_numeric($sne_mn)))){
				$errors[] = 'Please enter a valid end time.';
				}
			else {
				if (empty($sne_mn)){
					$sne_mn = '00';
					}
				if (($sne_hr < $sns_hr)||(($sns_hr == $sne_hr)&&($sne_mn <= $sns_mn))) {$sne_hr = $sne_hr+12;}
				if ($sne_hr < 7){$sne_hr = $sne_hr+12;}
				$sne_time = "$sne_hr:$sne_mn:00";
				}
			}
		else{
			$errors[] = 'Please enter an end time for this coverage shift.';
			}

		//Insert into db
		$query = "INSERT into sub_needs (sub_needs_date, sub_needs_start_time, sub_needs_end_time, sub_needs_division, sub_needs_create) 
			values ('$sn_date', '$sns_time', '$sne_time', '$sn_div', null)";
		$result = mysql_query($query) or die(mysql_error($dbc));
		if ($result) {
			echo "<div class=\"message\">Sub need shift entered for <b>$sn_div</b> on <b>$sn_date</b></div>";
			}
		}
	
	if (isset($_POST['confirm'])){
		$division = $_SESSION['sub_needs_division'];
	
		$empno = $_POST['employee_number'];
		$name = $_POST['employee_name'];
		$sub_needs_id = $_POST['sub_needs_id'];
		$sub_needs_division = $_POST['sub_needs_division'];
		$sub_needs_date = $_POST['sub_needs_date'];
		$short_date = $_POST['short_date'];
		$sub_needs_start_time = $_POST['sub_needs_start_time'];
		$sub_needs_end_time = $_POST['sub_needs_end_time'];
		
		//Check for overlaps
		$query = "SELECT e.employee_number, division, concat(first_name, ' ', last_name) as employee_name, coverage_division,
			coverage_date, coverage_start_time, coverage_end_time FROM coverage as t, coverageassoc as a, employees as e 
			WHERE e.employee_number = a.employee_number and t.coverage_id = a.coverage_id and e.employee_number = '$empno' 
			and coverage_date = '$sub_needs_date' and (('$sub_needs_start_time' >= coverage_start_time and 
			'$sub_needs_start_time' < coverage_end_time) 
			or ('$sub_needs_end_time' > coverage_start_time and '$sub_needs_end_time' <= coverage_end_time) 
			or ('$sub_needs_start_time' <= coverage_start_time and '$sub_needs_end_time' >= coverage_end_time))"; 
		$result = mysql_query($query);
		$num_rows = mysql_num_rows($result);
		if ($num_rows != 0) {
			while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)){
				$full_name = $row['employee_name'];
				$old_empno = $row['employee_number'];
				$division = $row['division'];
				$cov_date = $row['coverage_date'];
				$covs_time = $row['coverage_start_time'];
				$cove_time = $row['coverage_end_time'];
				$cov_div = $row['coverage_division'];
				$errors[] = "<b>$full_name</b> is already scheduled to cover $cov_div<br/>&nbsp;&nbsp;$cov_date, 
					$covs_time-$cove_time";
				$overlap = TRUE;
				}
			}
		
		if (empty($errors)) {
			$query1 = "UPDATE sub_needs set sub_needs_covered='Y' WHERE sub_needs_id='$sub_needs_id'";
			$result1 = mysql_query($query1);
			$query2 = "INSERT into coverage (coverage_date, coverage_start_time, coverage_end_time, coverage_division, coverage_reason, coverage_create) 
				VALUES ('$sub_needs_date', '$sub_needs_start_time', '$sub_needs_end_time', '$sub_needs_division', '', null)";
			$result2 = mysql_query($query2);
			if ($result2) {
				$coverage_id = mysql_insert_id();
				$query3 = "INSERT into coverageassoc values(null, '$coverage_id', '$empno')";
				$result3 = mysql_query($query3) or die(mysql_error($dbc));
				}
			echo '<div class="message"><b>'. $name . ' on ' . $short_date . '</b> has been confirmed.</div>';
			}
		else{
			echo '<div class="errormessage"><h3>Error!</h3><br/>
			The following error(s) occurred:<br/><br/>';
			foreach ($errors as $msg) { //Print each error
				echo " - $msg<br/>\n";
				}
			echo '</div>';
			}
		}
		
	if (isset($_POST['delete'])){
		$division = $_SESSION['sub_needs_division'];
		
		$sub_needs_id = $_POST['sub_needs_id'];
		$short_date = $_POST['short_date'];
		$query = "DELETE from sub_needs WHERE sub_needs_id='$sub_needs_id'";
		$result = mysql_query($query);
		echo '<div class="message">Sub shift on <b>' . $short_date . '</b> has been deleted.</div>';
		}
		
	if (isset($_POST['sub_div'])) {
		$division = $_POST['division'];
		$_SESSION['sub_needs_division'] = $division;
		}
	$div = array('All', 'Adult', 'Children', 'Customer Service', 'LTI', 'Teen');
	echo '<form action="sub_needs" method="post">
		<p class="divform">Sub Request Division: 
			<select name="division" onchange="this.form.submit();">';
	foreach ($div as $key => $d){
		echo '<option value="' . $d . '" ';
		if (isset($division)){
			if ($division==$d) {echo 'selected="selected"';}
			}
		echo '>' . $d . '</option>';
		}
	echo '</select>
		<input type="hidden" name="sub_div" value="TRUE" />
		</p>
		</form>';

	if (((isset($_POST['sub_div']))||(isset($_SESSION['sub_needs_division']))) && ($division !== 'All')){
		$query = "SELECT sub_needs_id, sub_needs_division, sub_needs_date, sub_needs_start_time, 
			time_format(sub_needs_start_time,'%k') as sub_needs_start, 
			time_format(sub_needs_start_time,'%i') as sub_needs_start_minutes, sub_needs_end_time,
			time_format(sub_needs_end_time,'%k') as sub_needs_end, 
			time_format(sub_needs_end_time,'%i') as sub_needs_end_minutes, sub_needs_empno
			FROM sub_needs
			WHERE sub_needs_date >= '$today' and sub_needs_covered = 'N' and sub_needs_division = '$division'
			ORDER by sub_needs_date asc, sub_needs_start_time asc, sub_needs_division asc";
		}
	else {
		$query = "SELECT sub_needs_id, sub_needs_division, sub_needs_date, sub_needs_start_time, 
			time_format(sub_needs_start_time,'%k') as sub_needs_start, 
			time_format(sub_needs_start_time,'%i') as sub_needs_start_minutes, sub_needs_end_time,
			time_format(sub_needs_end_time,'%k') as sub_needs_end, 
			time_format(sub_needs_end_time,'%i') as sub_needs_end_minutes, sub_needs_empno
			FROM sub_needs
			WHERE sub_needs_date >= '$today' and sub_needs_covered = 'N'
			ORDER by sub_needs_date asc, sub_needs_start_time asc, sub_needs_division asc";
		}
	$result = mysql_query($query);
	if ($result){
		$num_rows = mysql_num_rows($result);
		if ($num_rows != 0) {
			echo '<div class="divboxes">'."\n".'<table class="sub_needs sort">'."\n";
			echo '<tr><th class="division">Division</th><th class="datetime">Shift</th>
				<th class="assign">Assign</th><th class="confirm"></th><th></th></tr>';
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				$sub_needs_id = $row['sub_needs_id'];
				$sub_needs_division = $row['sub_needs_division'];
				$sub_needs_date = $row['sub_needs_date'];
				$sub_needs_start_time = $row['sub_needs_start_time'];
				$sub_needs_start_hours = $row['sub_needs_start'];
				$sub_needs_start_minutes = $row['sub_needs_start_minutes'];
				$sub_needs_end_time = $row['sub_needs_end_time'];
				$sub_needs_end_hours = $row['sub_needs_end'];
				$sub_needs_end_minutes = $row['sub_needs_end_minutes'];
				$sub_needs_empno = $row['sub_needs_empno'];
				$sns12 = NULL;
				$sne12 = NULL;
				
				//Adjust 24-hour time.		
				if ($sub_needs_start_hours > 12){
					$sns12 = $sub_needs_start_hours - 12;
					if ($sub_needs_start_minutes != '00') {
						$sns12 .= ':'.$sub_needs_start_minutes;
						}
					$sne12 = $sub_needs_end_hours - 12;
					if ($sub_needs_end_minutes != '00') {
						$sne12 .= ':'.$sub_needs_end_minutes;
						}
					$sne12 .= 'pm';
					}
				elseif ($sub_needs_start_hours == 12){
					$sns12 = $sub_needs_start_hours;
					if ($sub_needs_start_minutes != '00') {
						$sns12 .= ':'.$sub_needs_start_minutes;
						}
					$sne12 = $sub_needs_end_hours - 12;
					if ($sub_needs_end_minutes != '00') {
						$sne12 .= ':'.$sub_needs_end_minutes;
						}
					$sne12 .= 'pm';
					}					
				else {
					$sns12 = $sub_needs_start_hours;
					if ($sub_needs_start_minutes != '00') {
						$sns12 .= ':'.$sub_needs_start_minutes;
						}
					$sns12 .= 'am';
					if ($sub_needs_end_hours > 12){
						$sne12 = $sub_needs_end_hours - 12;
						if ($sub_needs_end_minutes != '00') {
							$sne12 .= ':'.$sub_needs_end_minutes;
							}
						$sne12 .= 'pm';
						}
					elseif ($sub_needs_end_hours == 12){
						$sne12 = $sub_needs_end_hours;
						if ($sub_needs_end_minutes != '00') {
							$sne12 .= ':'.$sub_needs_end_minutes;
							}
						$sne12 .= 'pm';
						}
					else {
						$sne12 = $sub_needs_end_hours;
						if ($sub_needs_end_minutes != '00') {
							$sne12 .= ':'.$sub_needs_end_minutes;
							}
						$sne12 .= 'am';
						}
					}
				
				//Date specifics
				$snmonth = date('M', strtotime($sub_needs_date));
				$snday = date('j', strtotime($sub_needs_date));
				$sndow = date('D', strtotime($sub_needs_date));
				if ((date('Y', strtotime($sub_needs_date))) > date('Y')){
					$snyear = date('Y', strtotime($sub_needs_date));
					$snyear = ' '.$snyear;
					}
				else {
					$snyear = NULL;
					}
				
				//Find Declines
				$declined = array();
				$declined_ordered = array();
				$query2 = "SELECT employee_number from sub_needs_declined WHERE sub_needs_id='$sub_needs_id'";
				$result2 = mysql_query($query2);
				while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
					$empno = $row2['employee_number'];
					$declined[] = $empno;
					}
				foreach ($subs as $empno=>$name){
					if (in_array($empno, $declined)){
						$declined_ordered[] = $empno;
						}
					}
				
				echo "<tr><td class=\"division\">$sub_needs_division</td>";
				echo "<td class=\"datetime\"><span class=\"todate\">$sndow, $snday $snmonth$snyear</span>";
				echo ", $sns12 - $sne12";
				echo "</td>";
				echo "<td class=\"assign\">";
				if ($sub_needs_empno != NULL){
					$sub = $subs[$sub_needs_empno];
					echo "Shift assigned to <span class=\"assign_style\">$sub</span>";
					}
				else {
					echo "Shift not yet assigned...";
					if (!empty($declined)){
						echo '<br/><b>Declined by:</b> <span class="decline">';
						foreach ($declined_ordered as $key=>$empno){
							if ($key != 0){
								echo ', ';
								}
							echo $subs[$empno];
							}
						echo '</span>';
						}
					}
				echo "</td>";
				echo "<td class=\"confirm\">";
				if ($sub_needs_empno != NULL){
					echo '<form action="sub_needs" method="post" onsubmit="return confirmSub(this)">
						<input type="hidden" name="sub_needs_id" value="' . $sub_needs_id . '"/>
						<input type="hidden" name="employee_number" value="' . $sub_needs_empno . '"/>
						<input type="hidden" name="employee_name" value="' . $sub . '"/>
						<input type="hidden" name="sub_needs_division" value="' . $sub_needs_division . '"/>
						<input type="hidden" name="sub_needs_date" value="' . $sub_needs_date . '"/>
						<input type="hidden" name="short_date" value="' . $snmonth . ' ' .$snday.$snyear . '"/>
						<input type="hidden" name="sub_needs_start_time" value="' . $sub_needs_start_time . '"/>
						<input type="hidden" name="sub_needs_end_time" value="' . $sub_needs_end_time . '"/>
						<input type="hidden" name="confirm" value="TRUE" />
						<input type="submit" name="confirm" value="Confirm" /></form>';
					}
				echo '</td>';
				echo '<td class="delete">';
				if ($sub_needs_empno == NULL){
					echo '<form action="sub_needs" method="post" onsubmit="return deleteSubNeeds(this)">
						<input type="hidden" name="sub_needs_id" value="' . $sub_needs_id . '"/>
						<input type="hidden" name="sub_needs_division" value="' . $sub_needs_division . '"/>
						<input type="hidden" name="short_date" value="' . $snmonth . ' ' .$snday.$snyear . '"/>
						<input type="hidden" name="delete" value="TRUE" />
						<input type="submit" name="deletesub" value="Delete" /></form>';
					}
				echo '</td>';
				echo "</tr>\n";
				}
			echo "</table>\n</div>";
			}
		else {
			echo '<div class="divboxes"><table class="sub_needs">
				<tr><td style="padding:3px 20px;">There are no pending sub requests.</td></tr></table></div>';
			}
		}
	echo '</div>';
	}
elseif ($_SESSION['role'] == 'Subs'){
?>
	<script>
	$(document).ready(function(){
		$("select[name*='subs']").change(function(){
			getScrollXY();
			var sub = $("option:selected", this).text();
			var empno = $(this).val();
			var form = $(this).closest("form").attr("id");
			if (empno != 'cancel'){
				if(confirm('Switch sub to '+sub+'?')){
					$('#'+form).submit();
					}
				else{
					$("select option[value='NULL']").attr("selected","selected");
					}
				}
			else {
				if(confirm('Clear sub?')){
					$('#'+form).submit();
					}
				else{
					$("select option[value='NULL']").attr("selected","selected");
					}
				}
			});
		$('.declined_check').change(function(){
			if($(this).is(':checked')){
				$(this).parent().find(".comment").show();
				}
			else {
				$(this).parent().find(".comment").hide();
				}
			});
		});
	</script>
<?php
	echo '<span class="date"><h1>Sub Needs</h1></span>'."\n";
	if (isset($_POST['confirm'])){
		$sub_needs_id = $_POST['sub_needs_id'];
		$sub_needs_empno = $_POST['subs_'.$sub_needs_id];
		
		if ($sub_needs_empno == 'cancel'){
			$query = "UPDATE sub_needs set sub_needs_empno=NULL WHERE sub_needs_id='$sub_needs_id'";
			$result = mysql_query($query);
			}
		else {
			$query1 = "UPDATE sub_needs set sub_needs_empno='$sub_needs_empno' WHERE sub_needs_id='$sub_needs_id'";
			$result1 = mysql_query($query1);
			}
		}
	if (isset($_POST['decline'])){
		$sub_needs_id = $_POST['sub_needs_id'];
		$empno = $_POST['declined'];
		$query2 = "INSERT into sub_needs_declined (sub_needs_id, employee_number) VALUES ('$sub_needs_id', '$empno')";
		$result2 = mysql_query($query2);
		}
	if (isset($_POST['decline_comment'])){
		$sub_needs_declined_id = $_POST['sub_needs_declined_id'];
		$comment = $_POST['declined_comment'];
		$query6 = "UPDATE sub_needs_declined SET comment='$comment' WHERE sub_needs_declined_id = '$sub_needs_declined_id'";
		$result6 = mysql_query($query6);
		}
	if (isset($_POST['undecline'])){
		$sub_needs_declined_id = $_POST['sub_needs_declined_id'];
		$query3 = "DELETE from sub_needs_declined WHERE sub_needs_declined_id='$sub_needs_declined_id'";
		$result3 = mysql_query($query3);
		}
	if (isset($_POST['available'])){
		$sub_needs_id = $_POST['sub_needs_id'];
		$empno = $_POST['availability'];
		$query4 = "INSERT into sub_needs_available (sub_needs_id, employee_number) VALUES ('$sub_needs_id', '$empno')";
		$result4 = mysql_query($query4);
		}
	if (isset($_POST['unavailable'])){
		$sub_needs_available_id = $_POST['sub_needs_available_id'];
		$query5 = "DELETE from sub_needs_available WHERE sub_needs_available_id='$sub_needs_available_id'";
		$result5 = mysql_query($query5);
		}
		
	$query = "SELECT sub_needs_id, sub_needs_division, sub_needs_date, time_format(sub_needs_start_time,'%k') as sub_needs_start, 
		time_format(sub_needs_start_time,'%i') as sub_needs_start_minutes, time_format(sub_needs_end_time,'%k') as sub_needs_end, 
		time_format(sub_needs_end_time,'%i') as sub_needs_end_minutes, sub_needs_empno
		FROM sub_needs
		WHERE sub_needs_date >= '$today' and sub_needs_covered = 'N'
		ORDER by sub_needs_date asc, sub_needs_start_time asc, sub_needs_division asc";
	$result = mysql_query($query);
	if ($result){
		$num_rows = mysql_num_rows($result);
		if ($num_rows != 0) {
			echo '<div class="divboxes">'."\n".'<table class="sub_needs">'."\n";
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				$sub_needs_id = $row['sub_needs_id'];
				$sub_needs_division = $row['sub_needs_division'];
				$sub_needs_date = $row['sub_needs_date'];
				$sub_needs_start_hours = $row['sub_needs_start'];
				$sub_needs_start_minutes = $row['sub_needs_start_minutes'];
				$sub_needs_end_hours = $row['sub_needs_end'];
				$sub_needs_end_minutes = $row['sub_needs_end_minutes'];
				$sub_needs_empno = $row['sub_needs_empno'];
				$sns12 = NULL;
				$sne12 = NULL;
				
				//Adjust 24-hour time.		
				if ($sub_needs_start_hours > 12){
					$sns12 = $sub_needs_start_hours - 12;
					if ($sub_needs_start_minutes != '00') {
						$sns12 .= ':'.$sub_needs_start_minutes;
						}
					$sne12 = $sub_needs_end_hours - 12;
					if ($sub_needs_end_minutes != '00') {
						$sne12 .= ':'.$sub_needs_end_minutes;
						}
					$sne12 .= 'pm';
					}
				elseif ($sub_needs_start_hours == 12){
					$sns12 = $sub_needs_start_hours;
					if ($sub_needs_start_minutes != '00') {
						$sns12 .= ':'.$sub_needs_start_minutes;
						}
					$sne12 = $sub_needs_end_hours - 12;
					if ($sub_needs_end_minutes != '00') {
						$sne12 .= ':'.$sub_needs_end_minutes;
						}
					$sne12 .= 'pm';
					}					
				else {
					$sns12 = $sub_needs_start_hours;
					if ($sub_needs_start_minutes != '00') {
						$sns12 .= ':'.$sub_needs_start_minutes;
						}
					$sns12 .= 'am';
					if ($sub_needs_end_hours > 12){
						$sne12 = $sub_needs_end_hours - 12;
						if ($sub_needs_end_minutes != '00') {
							$sne12 .= ':'.$sub_needs_end_minutes;
							}
						$sne12 .= 'pm';
						}
					else {
						$sne12 = $sub_needs_end_hours;
						if ($sub_needs_end_minutes != '00') {
							$sne12 .= ':'.$sub_needs_end_minutes;
							}
						$sne12 .= 'am';
						}
					}
				
				//Date specifics
				$snmonth = date('M', strtotime($sub_needs_date));
				$snday = date('j', strtotime($sub_needs_date));
				$sndow = date('D', strtotime($sub_needs_date));
				if ((date('Y', strtotime($sub_needs_date))) > date('Y')){
					$snyear = date('Y', strtotime($sub_needs_date));
					$snyear = ' '.$snyear;
					}
				else {
					$snyear = NULL;
					}
				
				//Find Declines
				$declined = array();
				$query2 = "SELECT * from sub_needs_declined WHERE sub_needs_id='$sub_needs_id'";
				$result2 = mysql_query($query2);
				while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
					$sub_needs_declined_id = $row2['sub_needs_declined_id'];
					$empno = $row2['employee_number'];
					$comment = $row2['comment'];
					$declined[$empno] = array($sub_needs_declined_id=>$comment);
					}

				//Find Availables
				$available = array();
				$query3 = "SELECT * from sub_needs_available WHERE sub_needs_id='$sub_needs_id'";
				$result3 = mysql_query($query3);
				while ($row3 = mysql_fetch_array($result3, MYSQL_ASSOC)){
					$sub_needs_available_id = $row3['sub_needs_available_id'];
					$empno = $row3['employee_number'];
					$available[$sub_needs_available_id] = $empno;
					}
				
				echo "<tr><td class=\"division\">$sub_needs_division</td>";
				echo "<td class=\"datetime\"><span class=\"todate\">$sndow, $snday $snmonth$snyear</span>";
				echo ", $sns12 - $sne12";
				echo "</td>";

				if ($sub_needs_empno != NULL){
					echo "<td class=\"assign\" colspan=\"2\">";
					$sub = $subs[$sub_needs_empno];
					echo "Shift assigned to <span class=\"assign_style\">$sub</span>";
					echo "</td>";
					}
				else {
					echo "<td class=\"assign\">";
					echo '<b>Decline:</b><br/>';
					foreach($subs as $empno=>$name){
						if (array_key_exists($empno, $declined)){
							$decarray = $declined[$empno];
							foreach ($decarray as $k=>$v){
								$sub_needs_declined_id = $k;
								$comment = $v;
								echo '<form id="undecline" action="sub_needs" method="post" class="undecline" style="float:left;margin-right:5px;">
									<input class="declined_check" type="checkbox" onchange="getScrollXY();this.form.submit();" name="declined" value="'.$empno.'"';
								echo ' checked="checked"';
								echo '><span class="decline">'.$name.'</span>';
								echo '<input type="hidden" name="undecline" value="TRUE" />
									<input type="hidden" name="sub_needs_declined_id" value="'.$sub_needs_declined_id.'"/>
									</form>';
								echo '<div class="comment" style="display:inline;text-align:right;">
									<form class="declinecomment" action="sub_needs" method="post">
									<input type="submit" name="submit" value="Save" onclick="getScrollXY();this.form.submit();"/>
									<input type="hidden" name="decline_comment" value="TRUE" />
									<input type="hidden" name="sub_needs_declined_id" value="'.$sub_needs_declined_id.'"/>
									<input type="text" name="declined_comment" size="20" maxlength="140" value="'.$comment.'"/>
									</form>
									</div>';	
								}
							}
						else {
							echo '<form id="decline" action="sub_needs" method="post">
								<input class="declined_check" type="checkbox" onchange="getScrollXY();this.form.submit();" name="declined" value="'.$empno.'"';
							echo '><span class="decline">'.$name.'</span>';
							echo '<input type="hidden" name="decline" value="TRUE" />
								<input type="hidden" name="sub_needs_id" value="'.$sub_needs_id.'"/>
								</form>';
							}
						}
					echo "</td>";
					echo "<td class=\"assign\">";
					echo '<b>Available:</b><br/>';
					foreach($subs as $empno=>$name){
						if (in_array($empno, $available)){
							$sub_needs_available_id = array_search($empno, $available);
							echo '<form id="unavailable" action="sub_needs" method="post">
								<input type="checkbox" onchange="getScrollXY();this.form.submit();" name="availability" value="'.$empno.'"';
							echo ' checked="checked"';
							echo '><span class="decline">'.$name.'</span><br/>';
							echo '<input type="hidden" name="unavailable" value="TRUE" />
								<input type="hidden" name="sub_needs_available_id" value="' . $sub_needs_available_id . '"/>
								</form>';	
							}
						else {
							echo '<form id="available" action="sub_needs" method="post">
								<input type="checkbox" onchange="getScrollXY();this.form.submit();" name="availability" value="'.$empno.'"';
							echo '><span class="decline">'.$name.'</span><br/>';
							echo '<input type="hidden" name="available" value="TRUE" />
								<input type="hidden" name="sub_needs_id" value="' . $sub_needs_id . '"/>
								</form>';	
							}
						}
					echo "</td>";
					}

				echo "<td class=\"assign\" style=\"vertical-align:top;\">";
				if ($sub_needs_empno == NULL){
					echo "<b>Accept:</b><br/>";
					}
				echo '<form id="switch_sub_'.$sub_needs_id.'" class="subassign" action="sub_needs" method="post">
					<select name="subs_'.$sub_needs_id.'" style="width:142px;">';
				echo "<option value=\"NULL\" selected=\"selected\"></option>\n";
				foreach ($subs as $key => $value) {
					echo "<option value=\"$key\">$value</option>\n";
					}
				if ($sub_needs_empno != NULL){
					echo "<option value=\"cancel\">...cancel assignment</option>\n";
					}
				echo '</select>
					<input type="hidden" name="sub_needs_id" value="' . $sub_needs_id . '"/>
					<input type="hidden" name="confirm" value="TRUE" />
					</form>';
				echo '</td>';
				echo "</tr>\n";
				}
			echo "</table>\n</div>";
			}
		else {
			echo '<div class="divboxes"><table class="sub_needs">
				<tr><td style="padding:3px 20px;">There are no pending sub requests.</td></tr></table></div>';
			}
		}
	}
if ((isset($came_from)) && ($came_from == 'sub_needs')){
	if((isset($_POST['scrollTop'])) && (isset($_POST['scrollLeft']))){
?>
<script>
$(document).ready(function(){
	var setTop = <?php if(isset($_POST['scrollTop'])){echo $_POST['scrollTop'];} ?>;
	var setLeft = <?php if(isset($_POST['scrollLeft'])){echo $_POST['scrollLeft'];} ?>;
	$(document).scrollTop(setTop);
	$(document).scrollLeft(setLeft);
});
</script>
<?php
		}
	}
include ('./includes/footer.html');
?>