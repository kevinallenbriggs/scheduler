<?php #edit_my_timesheet.php
//Time to decimal function
function dec_minutes($mins) {
	$dec_mins = $mins/60;
	return $dec_mins;
	}
//Dates between function
function dates_between_inclusive($start_date, $end_date){
	global $array;
	$array = array();
	$array[] = $start_date;
	
	$start_date = is_int($start_date) ? $start_date : strtotime($start_date);
	$end_date = is_int($end_date) ? $end_date : strtotime($end_date);
	 
	$end_date -= (60 * 60 * 24);

	global $test_date;
	$test_date = $start_date;
	$day_incrementer = 1;
	 
	do{
		$test_date = $start_date + ($day_incrementer * 60 * 60 * 24);
		$realdate = date('Y-m-d' , $test_date);
		$array[] = $realdate;
		} 
	while ($test_date <= $end_date && ++$day_incrementer );
	}

include('./includes/sessionstart.php');
$came_from = $_SESSION['came_from'];

if ((!isset($came_from)) || (!isset($_POST['submit']))){
	header ('Location: timesheet');
	}
	
if (($came_from != 'timesheet') && ($came_from != 'edit_my_timesheet')){
	header ('Location: timesheet');
	}
	
include('./includes/allsessionvariables.php');

$query = "SELECT weekly_hours from employees WHERE employee_number='$this_empno'";
$result = mysql_query($query);
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	$weekly_hours = $row['weekly_hours'];
	}

if (isset($_POST['pp_id'])){
	$pp_id=$_POST['pp_id'];
	$_SESSION['pp_id'] = $pp_id;
	}

if (isset($_POST['pp_start_date'])){
	$pp_start_date = $_POST['pp_start_date'];
	$_SESSION['pp_start_date'] = $pp_start_date;
	$pp_start_friendly = date('j M Y', strtotime($pp_start_date));
	$pp_midweek_end_date = strtotime('+6days', strtotime($pp_start_date));
	$pp_midweek_end_date = date('Y-m-d' , $pp_midweek_end_date );
	$pp_midweek_start_date = strtotime('+7days', strtotime($pp_start_date));
	$pp_midweek_start_date = date('Y-m-d' , $pp_midweek_start_date );
	$pp_end_date = strtotime('+13days', strtotime($pp_start_date));
	$pp_end_date = date('Y-m-d' , $pp_end_date );
	}
	
if (isset($_POST['confirmed'])){
	$time_entry = $_POST['time_entry'];
	//Check for duplicates
	$query = "DELETE from time_entry WHERE employee_number='$this_empno' and pp_id='$pp_id'";
	$result = mysql_query($query);
	foreach ($time_entry as $date=>$codes){
		foreach ($codes as $code=>$hours){
			if((!empty($hours))&&($hours != 0)&&(is_numeric($hours))){
				$query2 = "INSERT into time_entry (pp_id, employee_number, entry_date, hour_code, hours)
					VALUES ('$pp_id', '$this_empno','$date','$code','$hours')";
				$result2 = mysql_query($query2) or die(mysql_error());
				}
			}
		}
	$query3 = "SELECT * from timesheet_confirm WHERE employee_number='$this_empno' and pp_id='$pp_id'";
	$result3 = mysql_query($query3);
	if (($result3)&&(mysql_num_rows($result3) == 0)){
		$query4 = "INSERT into timesheet_confirm (employee_number, pp_id, employee_confirm, supervisor_approve) 
			VALUES ('$this_empno','$pp_id','Y','N')";
		}
	else{
		$query4 = "UPDATE timesheet_confirm set employee_confirm='Y' where employee_number='$this_empno' and pp_id='$pp_id'";
		}
	$result4 = mysql_query($query4);
	
	$_SESSION['timesheet_confirmed'] = TRUE;
	header ('Location: timesheet');
	}

$other_hours = array();
$query = "SELECT * from hour_codes WHERE hour_code not in ('02','28','26','24','48')";
$result = mysql_query($query);
while ($row = mysql_fetch_assoc($result)) {
	$hour_code = $row['hour_code'];
	$other_hours[$hour_code] = $row['description'];
	}

//Get previous entries
$previous = array();
$query = "SELECT * from time_entry WHERE employee_number='$this_empno' and entry_date>='$pp_start_date'
	and entry_date<='$pp_end_date'";
$result = mysql_query($query);
if (($result) && (mysql_num_rows($result)!=0)){
	while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
		$entry_date = $row['entry_date'];
		$hour_code = $row['hour_code'];
		$hours = $row['hours'];
		$previous[$entry_date][$hour_code] = $hours;
		}
	}
	
$page_title = 'Timesheet | '.$pp_start_friendly;

include ('./includes/header.html');
include ('./includes/sidebar.html');

echo '<div class="wideview">
	<span class="date"><h1>Edit My Timesheet for '.$pp_start_friendly.'</h1></span>';
?>
<script>
function calcTotals(){
	$(document).ready(function(){
		$('td.total').each(function(){
			var row_total = 0;
			$(this).parent().find('input').each(function(){
				row_total += Number($(this).val());
				});
			$(this).text(row_total);
			});
		$('td.weekly_total').each(function(){
			var week_total = 0;
			$(this).parent().parent().find('td.total').each(function(){
				week_total += Number($(this).text());
				});
			$(this).text(week_total);
			var shift_total = Number($(this).parent().parent().parent().find('.shift_total').text());
			
			if (week_total != shift_total){
				$(this).addClass('insufficient');
				}
			else{
				$(this).removeClass('insufficient');
				}
			});
		});
	}
calcTotals();

function forceNumeric(input){
	$(document).ready(function(){
		if (!/^[0-9]+$/.test(input.val())) {
			input.val(input.val().replace(/[^0-9]/g, ''));
			}
		});
	}

$(document).ready(function(){
	$('input:text').blur(function(){
		forceNumeric($(this));
		calcTotals();
		});
		
	$('select').change(function(){
		var code = $(this).val();
		$(this).parent().parent().find('input').each(function(){
			var dateName = $(this).attr('name');
			var newName = dateName.replace( /\[[a-zA-Z0-9]{1,2}\]/, '['+code+']');
			$(this).attr('name', newName);
			});
		});
	});

function validateNumbers() {
	var falseness;
	$(document).ready(function(){
		$('input:text').each(function(){
			var entry = $(this).val();
			if ((entry != null)&&(entry != '')&&(entry != parseInt(entry))){
				alert("Numbers only, please!");
				var falseness = false;
				}
			});
		});
	if (falseness === false){
		return false;
		}
	else { return true;}
	}
function validateTotals(){
	var falseness;
	$(document).ready(function(){
		$('.weekly_total').each(function(){
			var week_no = $(this).data("week");
			var weekly_total = Number($(this).text());
			var shift_total = Number($(this).parent().parent().parent().find('.shift_total').text());
			if (weekly_total > shift_total){
				var conf = confirm("Shifts exceed allotted weekly hours in week #"+week_no+". Confirm anyway?");
				if (!conf){
					falseness = false;
					return false;
					}
				}
			else if (weekly_total < shift_total){
				var conf = confirm("Weekly hours not accounted for in week #"+week_no+". Confirm anyway?");
				if (!conf){
					falseness = false;
					return false;
					}
				}
			});
		});
	if (falseness === false){
		return false;
		}
	else { return true;}
	}
	
function validator() {
	if (!validateNumbers()) {return false;}
	else if (!validateTotals()) {return false;}
	else {return true;}
	}
</script>
<?php
echo '<form action="edit_my_timesheet" method="post" name="timesheet" onsubmit="return validator();">';

//Timesheet Week #1
dates_between_inclusive("$pp_start_date", "$pp_midweek_end_date");

echo '<table class="timetable top" cellspacing="0">'."\n";
echo '<tr class="timetable_date"><td></td>';
foreach ($array as $k=>$v){
	$short_date = date('j M', strtotime($v));
	$day = date('D', strtotime($v));
	echo '<td class="day">'.$short_date.'<br/>'.$day.'</td>';
	}
echo '<td class="day">Total</td></tr>';
echo '<tr class="scheduled"><td class="hours_type">Scheduled</td>';
foreach ($array as $k=>$v){
	echo '<td class="shift">';	
	$query = "SELECT date, week_type FROM dates where date = '$v'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_assoc($result)) {
		$week_type = $row['week_type'];
		}

	$day = date('D', strtotime($v));
	
	$query2 = "SELECT weekly_hours, time_format(shift_start,'%k') as shift_start, 
		time_format(shift_start,'%i') as shift_start_minutes, time_format(shift_end,'%k') as shift_end, 
		time_format(shift_end,'%i') as shift_end_minutes from employees as e, shifts as a, schedules as s 
		WHERE e.employee_number = '$this_empno' and e.employee_number = a.employee_number and 
		schedule_start_date <= '$v' and schedule_end_date >= '$v' 
		and week_type='$week_type' and shift_day='$day' and a.specific_schedule=s.specific_schedule 
		and e.active = 'Active' and (e.employee_lastday >= '$v' or e.employee_lastday is null)";
	$result2 = mysql_query($query2);
	if($result2){
		while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
			$shift_start = $row2['shift_start'];
			$shift_start_minutes = $row2['shift_start_minutes'];
			$shift_end = $row2['shift_end'];
			$shift_end_minutes = $row2['shift_end_minutes'];
			$weekly_hours = $row2['weekly_hours'];
			$shift_display = '';
			
			if ($shift_start > 12){
				$ss12 = $shift_start - 12;
				}
			elseif($shift_start == 0){
				$ss12 = NULL;
				}
			else{
				$ss12 = $shift_start;
				}
			if ($shift_start_minutes != '00') {
				$ss12 = $ss12.':'.$shift_start_minutes;
				}
		
			if ($shift_end > 12){
				$se12 = $shift_end - 12;
				}
			elseif($shift_end == 0){
				$se12 = NULL;
				}
			else{
				$se12 = $shift_end;
				}
			if ($shift_end_minutes != '00') {
				$se12 = $se12.':'.$shift_end_minutes;
				}
			if (isset($ss12)){
				$shift_display .= $ss12 . '-' . $se12;
				}
			if (!empty($shift_display)){
				echo $shift_display;
				}
			}
		}
	if ($_SESSION['role'] == 'Subs'){
		$sub_query = "SELECT weekly_hours, time_format(coverage_start_time,'%k') as cov_start, 
			time_format(coverage_start_time,'%i') as cov_start_minutes, time_format(coverage_end_time,'%k') as cov_end, 
			time_format(coverage_end_time,'%i') as cov_end_minutes from coverage c, employees e
			WHERE coverage_date = '$v' and c.employee_number = '$this_empno' and c.employee_number=e.employee_number 
			ORDER BY coverage_start_time asc";
		$sub_result = mysql_query($sub_query);
		if ($sub_result){
			$num = mysql_num_rows($sub_result);
			if ($num>0) {
				$sub_array = array();
				while($sub_row = mysql_fetch_array($sub_result, MYSQL_ASSOC)){
					$cov_start = $sub_row['cov_start'];
					$cov_start_minutes = $sub_row['cov_start_minutes'];
					$cov_end = $sub_row['cov_end'];
					$cov_end_minutes = $sub_row['cov_end_minutes'];
					$weekly_hours = $sub_row['weekly_hours'];
					
					if ($cov_start > 12){
						$cs12 = $cov_start - 12;
						}
					elseif($cov_start == 0){
						$cs12 = NULL;
						}
					else{
						$cs12 = $cov_start;
						}
					if ($cov_start_minutes != '00'){
						$cs12 = $cs12.':'.$cov_start_minutes;
						}
				
					if ($cov_end > 12){
						$ce12 = $cov_end - 12;
						}
					elseif($cov_end == 0){
						$ce12 = NULL;
						}
					else{
						$ce12 = $cov_end;
						}
					if ($cov_end_minutes != '00'){
						$ce12 = $ce12.':'.$cov_end_minutes;
						}
					
					$sub_array[] = array($cs12, $ce12);
					}
				if (count($sub_array)==1){
					echo $sub_array[0][0].'-'.$sub_array[0][1];
					}
				else{
					$cov_display = '';
					foreach ($sub_array as $item=>$covs){
						if ($item == 0){
							$cov_display .= $covs[0].'-'.$covs[1];
							}
						else{
							$cov_display .= '<br/>'.$covs[0].'-'.$covs[1];
							}
						}
					echo $cov_display;
					}
				}
			}
		}
	echo '</td>';
	}
echo '<td class="shift_total">';
if (isset($weekly_hours)){
	echo $weekly_hours;
	}
echo '</td></tr>';

echo '<tr class="specialshaded"><td class="hours_type"><b>Regular Hours Worked</b></td>';

foreach ($array as $k=>$v){
	echo '<td class="entry">';
	$reg_hours = 0;
	
	if(isset($previous[$v])){
		if(isset($previous[$v]['02'])){
			$reg_hours = $previous[$v]['02'];
			}
		}
	else{
		$query = "SELECT date, week_type FROM dates where date = '$v'";
		$result = mysql_query($query);
		while ($row = mysql_fetch_assoc($result)) {
			$week_type = $row['week_type'];
			}

		$day = date('D', strtotime($v));
		
		$query2 = "SELECT time_format(shift_start,'%k') as shift_start, 
			time_format(shift_start,'%i') as shift_start_minutes, time_format(shift_end,'%k') as shift_end, 
			time_format(shift_end,'%i') as shift_end_minutes from employees as e, shifts as a, schedules as s 
			WHERE e.employee_number = '$this_empno' and e.employee_number = a.employee_number and 
			schedule_start_date <= '$v' and schedule_end_date >= '$v' 
			and week_type='$week_type' and shift_day='$day' and a.specific_schedule=s.specific_schedule 
			and e.active = 'Active' and (e.employee_lastday >= '$v' or e.employee_lastday is null)";
		$result2 = mysql_query($query2);
		if($result2){
			while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
				$shift_start = $row2['shift_start'];
				$shift_start_minutes = $row2['shift_start_minutes'];
				$shift_end = $row2['shift_end'];
				$shift_end_minutes = $row2['shift_end_minutes'];
				if ($shift_start_minutes != '00') {
					$shift_start += dec_minutes($shift_start_minutes);
					}
				if ($shift_end_minutes != '00') {
					$shift_end += dec_minutes($shift_end_minutes);
					}
				if(($shift_end-$shift_start)>=8){
					$reg_hours += 8;
					}
				else{
					$shift = $shift_end-$shift_start;
					$reg_hours += $shift;
					}
					
				$query3 = "SELECT * from closures where closure_date='$v'";
				$result3 = mysql_query($query3);
				if(mysql_num_rows($result3)!=0){
					while($row3 = mysql_fetch_assoc($result3)){
						if(($row3['closure_start_time'] == '00:01:00')&&($row3['closure_end_time'] == '23:59:00')){
							$reg_hours = 0;
							}
						else{
							list($cs_hr, $cs_mn, $cs_sec) = explode(':',$row3['closure_start_time']);
							list($ce_hr, $ce_mn, $ce_sec) = explode(':',$row3['closure_end_time']);
							if ($cs_mn != '00') {
								$cs = $cs_hr - dec_minutes($cs_mn);
								}
							else{
								$cs = $cs_hr;
								}
							if ($ce_mn != '00') {
								$ce = $ce_hr + dec_minutes($ce_mn);
								}
							else{
								$ce = $ce_hr;
								}
							
							if(($cs <= $shift_start)&&($ce >= $shift_end)){
								$reg_hours = 0;
								$closure = 0;
								}
							elseif(($row3['closure_start_time'] == '00:01:00')||(($cs <= $shift_start)&&($ce <= $shift_end))){
								$closure = $ce-$shift_start;
								}
							elseif(($row3['closure_end_time'] == '23:59:00')||(($ce >= $shift_end)&&($cs >= $shift_start))){
								$closure = $shift_end-$cs;
								}
							else{
								$closure = $ce-$cs;
								}
							$reg_hours -= $closure;
							}
						}
					}
				}
			}
		if ($_SESSION['role'] == 'Subs'){
			$sub_query = "SELECT time_format(coverage_start_time,'%k') as cov_start, 
				time_format(coverage_start_time,'%i') as cov_start_minutes, time_format(coverage_end_time,'%k') as cov_end, 
				time_format(coverage_end_time,'%i') as cov_end_minutes from coverage 
				WHERE coverage_date = '$v' and employee_number = '$this_empno' ORDER BY coverage_start_time asc";
			$sub_result = mysql_query($sub_query);
			if ($sub_result){
				$num = mysql_num_rows($sub_result);
				if ($num>0) {
					while ($sub_row = mysql_fetch_array($sub_result, MYSQL_ASSOC)){
						$cov_start = $sub_row['cov_start'];
						$cov_start_minutes = $sub_row['cov_start_minutes'];
						$cov_end = $sub_row['cov_end'];
						$cov_end_minutes = $sub_row['cov_end_minutes'];
						if ($cov_start_minutes != '00') {
							$cov_start += dec_minutes($cov_start_minutes);
							}
						if ($cov_end_minutes != '00') {
							$cov_end += dec_minutes($cov_end_minutes);
							}
						$shift = $cov_end-$cov_start;
						$reg_hours += $shift;
						}
					}
				}
			}
		}
	echo '<input type="text" name="time_entry['.$v.'][02]" maxlength="5" size="1" class="hrs" value="'.$reg_hours.'"/>';
	echo '</td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr><td class="hours_type">Floating Holiday</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][28]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['28']))){echo ' value="'.$previous[$v]['28'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr class="shaded"><td class="hours_type">Medical Leave</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][26]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['26']))){echo ' value="'.$previous[$v]['26'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr><td class="hours_type">Vacation</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][24]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['24']))){echo ' value="'.$previous[$v]['24'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr class="shaded"><td class="hours_type">Bereavement</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][48]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['48']))){echo ' value="'.$previous[$v]['48'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';

if(isset($previous)){
	$selects = array();
	foreach ($previous as $prev_date=>$prev_codes){
		if (($prev_date >= $pp_start_date)&&($prev_date <= $pp_midweek_end_date)){
			foreach ($prev_codes as $code=>$hours){
				if (isset($other_hours[$code])){
					$selects[] = $code;
					}
				}
			}
		}
	}

echo '<tr><td class="hours_type">Other <select name="Other1">
	<option value="X" selected="selected">- Select -</option>';
foreach ($other_hours as $code=>$desc){
	echo '<option value="'.$code.'"';
	if (count($selects)>0){
		if ($selects[0] == $code){
			$first_code = $selects[0];
			echo 'selected="selected"';
			}
		}
	echo '>'.$desc.'</option>';
	}
echo '</select></td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][';
	if (isset($first_code)){echo $first_code;}
	else {echo 'X';}
	echo ']" maxlength="5" size="1" class="hrs"';
	if ((count($selects)>0)&&(isset($previous[$v][$first_code]))){echo 'value="'.$previous[$v][$first_code].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr class="shaded"><td class="hours_type">Other <select name="Other1">
	<option value="Y" selected="selected">- Select -</option>';
foreach ($other_hours as $code=>$desc){
	echo '<option value="'.$code.'"';
	if (count($selects)>1){
		if ($selects[1] == $code){
			$second_code = $selects[1];
			echo 'selected="selected"';
			}
		}
	echo '>'.$desc.'</option>';
	}
echo '</select></td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][';
	if (isset($second_code)){echo $second_code;}
	else {echo 'Y';}
	echo ']" maxlength="5" size="1" class="hrs"';
	if ((count($selects)>1)&&(isset($previous[$v][$second_code]))){echo 'value="'.$previous[$v][$second_code].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';

echo '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td class="week_style label" colspan="2">Weekly Total</td><td data-week="1" class="week_style weekly_total"></td></tr>';
echo '</table>';

//Timesheet Week #2
dates_between_inclusive("$pp_midweek_start_date", "$pp_end_date");

echo '<table class="timetable top" cellspacing="0">'."\n";
echo '<tr class="timetable_date"><td></td>';
foreach ($array as $k=>$v){
	$short_date = date('j M', strtotime($v));
	$day = date('D', strtotime($v));
	echo '<td class="day">'.$short_date.'<br/>'.$day.'</td>';
	}
echo '<td class="day">Total</td></tr>';
echo '<tr class="scheduled"><td class="hours_type">Scheduled</td>';
foreach ($array as $k=>$v){
	echo '<td class="shift">';	
	$query = "SELECT date, week_type FROM dates where date = '$v'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_assoc($result)) {
		$week_type = $row['week_type'];
		}

	$day = date('D', strtotime($v));
	
	$query2 = "SELECT weekly_hours, time_format(shift_start,'%k') as shift_start, 
		time_format(shift_start,'%i') as shift_start_minutes, time_format(shift_end,'%k') as shift_end, 
		time_format(shift_end,'%i') as shift_end_minutes from employees as e, shifts as a, schedules as s 
		WHERE e.employee_number = '$this_empno' and e.employee_number = a.employee_number and 
		schedule_start_date <= '$v' and schedule_end_date >= '$v' 
		and week_type='$week_type' and shift_day='$day' and a.specific_schedule=s.specific_schedule 
		and e.active = 'Active' and (e.employee_lastday >= '$v' or e.employee_lastday is null)";
	$result2 = mysql_query($query2);
	if($result2){
		while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
			$shift_start = $row2['shift_start'];
			$shift_start_minutes = $row2['shift_start_minutes'];
			$shift_end = $row2['shift_end'];
			$shift_end_minutes = $row2['shift_end_minutes'];
			$weekly_hours = $row2['weekly_hours'];
			$shift_display = '';
			
			if ($shift_start > 12){
				$ss12 = $shift_start - 12;
				}
			elseif($shift_start == 0){
				$ss12 = NULL;
				}
			else{
				$ss12 = $shift_start;
				}
			if ($shift_start_minutes != '00') {
				$ss12 = $ss12.':'.$shift_start_minutes;
				}
		
			if ($shift_end > 12){
				$se12 = $shift_end - 12;
				}
			elseif($shift_end == 0){
				$se12 = NULL;
				}
			else{
				$se12 = $shift_end;
				}
			if ($shift_end_minutes != '00') {
				$se12 = $se12.':'.$shift_end_minutes;
				}
			if (isset($ss12)){
				$shift_display .= $ss12 . '-' . $se12;
				}
			if (!empty($shift_display)){
				echo $shift_display;
				}
			}
		}
	if ($_SESSION['role'] == 'Subs'){
		$sub_query = "SELECT time_format(coverage_start_time,'%k') as cov_start, 
			time_format(coverage_start_time,'%i') as cov_start_minutes, time_format(coverage_end_time,'%k') as cov_end, 
			time_format(coverage_end_time,'%i') as cov_end_minutes from coverage 
			WHERE coverage_date = '$v' and employee_number = '$this_empno' ORDER BY coverage_start_time asc";
		$sub_result = mysql_query($sub_query);
		if ($sub_result){
			$num = mysql_num_rows($sub_result);
			if ($num>0) {
				$sub_array = array();
				while($sub_row = mysql_fetch_array($sub_result, MYSQL_ASSOC)){
					$cov_start = $sub_row['cov_start'];
					$cov_start_minutes = $sub_row['cov_start_minutes'];
					$cov_end = $sub_row['cov_end'];
					$cov_end_minutes = $sub_row['cov_end_minutes'];
					
					if ($cov_start > 12){
						$cs12 = $cov_start - 12;
						}
					elseif($cov_start == 0){
						$cs12 = NULL;
						}
					else{
						$cs12 = $cov_start;
						}
					if ($cov_start_minutes != '00'){
						$cs12 = $cs12.':'.$cov_start_minutes;
						}
				
					if ($cov_end > 12){
						$ce12 = $cov_end - 12;
						}
					elseif($cov_end == 0){
						$ce12 = NULL;
						}
					else{
						$ce12 = $cov_end;
						}
					if ($cov_end_minutes != '00'){
						$ce12 = $ce12.':'.$cov_end_minutes;
						}
					
					$sub_array[] = array($cs12, $ce12);
					}
				if (count($sub_array)==1){
					echo $sub_array[0][0].'-'.$sub_array[0][1];
					}
				else{
					$cov_display = '';
					foreach ($sub_array as $item=>$covs){
						if ($item == 0){
							$cov_display .= $covs[0].'-'.$covs[1];
							}
						else{
							$cov_display .= '<br/>'.$covs[0].'-'.$covs[1];
							}
						}
					echo $cov_display;
					}
				}
			}
		}
	echo '</td>';
	}
echo '<td class="shift_total">';
if (isset($weekly_hours)){
	echo $weekly_hours;
	}
echo '</td></tr>';

echo '<tr class="specialshaded"><td class="hours_type"><b>Regular Hours Worked</b></td>';

foreach ($array as $k=>$v){
	echo '<td class="entry">';
	$reg_hours = 0;
	
	if(isset($previous[$v])){
		if(isset($previous[$v]['02'])){
			$reg_hours = $previous[$v]['02'];
			}
		}
	else{
		$query = "SELECT date, week_type FROM dates where date = '$v'";
		$result = mysql_query($query);
		while ($row = mysql_fetch_assoc($result)) {
			$week_type = $row['week_type'];
			}

		$day = date('D', strtotime($v));
		
		$query2 = "SELECT time_format(shift_start,'%k') as shift_start, 
			time_format(shift_start,'%i') as shift_start_minutes, time_format(shift_end,'%k') as shift_end, 
			time_format(shift_end,'%i') as shift_end_minutes from employees as e, shifts as a, schedules as s 
			WHERE e.employee_number = '$this_empno' and e.employee_number = a.employee_number and 
			schedule_start_date <= '$v' and schedule_end_date >= '$v' 
			and week_type='$week_type' and shift_day='$day' and a.specific_schedule=s.specific_schedule 
			and e.active = 'Active' and (e.employee_lastday >= '$v' or e.employee_lastday is null)";
		$result2 = mysql_query($query2);
		if($result2){
			while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
				$shift_start = $row2['shift_start'];
				$shift_start_minutes = $row2['shift_start_minutes'];
				$shift_end = $row2['shift_end'];
				$shift_end_minutes = $row2['shift_end_minutes'];
				if ($shift_start_minutes != '00') {
					$shift_start += dec_minutes($shift_start_minutes);
					}
				if ($shift_end_minutes != '00') {
					$shift_end += dec_minutes($shift_end_minutes);
					}
				if(($shift_end-$shift_start)>=8){
					$reg_hours += 8;
					}
				else{
					$shift = $shift_end-$shift_start;
					$reg_hours += $shift;
					}
					
				$query3 = "SELECT * from closures where closure_date='$v'";
				$result3 = mysql_query($query3);
				if(mysql_num_rows($result3)!=0){
					while($row3 = mysql_fetch_assoc($result3)){
						if(($row3['closure_start_time'] == '00:01:00')&&($row3['closure_end_time'] == '23:59:00')){
							$reg_hours = 0;
							}
						else{
							list($cs_hr, $cs_mn, $cs_sec) = explode(':',$row3['closure_start_time']);
							list($ce_hr, $ce_mn, $ce_sec) = explode(':',$row3['closure_end_time']);
							if ($cs_mn != '00') {
								$cs = $cs_hr - dec_minutes($cs_mn);
								}
							else{
								$cs = $cs_hr;
								}
							if ($ce_mn != '00') {
								$ce = $ce_hr + dec_minutes($ce_mn);
								}
							else{
								$ce = $ce_hr;
								}
							
							if(($cs <= $shift_start)&&($ce >= $shift_end)){
								$reg_hours = 0;
								$closure = 0;
								}
							elseif(($row3['closure_start_time'] == '00:01:00')||(($cs <= $shift_start)&&($ce <= $shift_end))){
								$closure = $ce-$shift_start;
								}
							elseif(($row3['closure_end_time'] == '23:59:00')||(($ce >= $shift_end)&&($cs >= $shift_start))){
								$closure = $shift_end-$cs;
								}
							else{
								$closure = $ce-$cs;
								}
							$reg_hours -= $closure;
							}
						}
					}
				}
			}
		}
	if ($_SESSION['role'] == 'Subs'){
		$sub_query = "SELECT time_format(coverage_start_time,'%k') as cov_start, 
			time_format(coverage_start_time,'%i') as cov_start_minutes, time_format(coverage_end_time,'%k') as cov_end, 
			time_format(coverage_end_time,'%i') as cov_end_minutes from coverage 
			WHERE coverage_date = '$v' and employee_number = '$this_empno' ORDER BY coverage_start_time asc";
		$sub_result = mysql_query($sub_query);
		if ($sub_result){
			$num = mysql_num_rows($sub_result);
			if ($num>0) {
				while ($sub_row = mysql_fetch_array($sub_result, MYSQL_ASSOC)){
					$cov_start = $sub_row['cov_start'];
					$cov_start_minutes = $sub_row['cov_start_minutes'];
					$cov_end = $sub_row['cov_end'];
					$cov_end_minutes = $sub_row['cov_end_minutes'];
					if ($cov_start_minutes != '00') {
						$cov_start += dec_minutes($cov_start_minutes);
						}
					if ($cov_end_minutes != '00') {
						$cov_end += dec_minutes($cov_end_minutes);
						}
					$shift = $cov_end-$cov_start;
					$reg_hours += $shift;
					}
				}
			}
		}
	echo '<input type="text" name="time_entry['.$v.'][02]" maxlength="5" size="1" class="hrs" value="'.$reg_hours.'"/>';
	echo '</td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr><td class="hours_type">Floating Holiday</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][28]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['28']))){echo ' value="'.$previous[$v]['28'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr class="shaded"><td class="hours_type">Medical Leave</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][26]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['26']))){echo ' value="'.$previous[$v]['26'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr><td class="hours_type">Vacation</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][24]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['24']))){echo ' value="'.$previous[$v]['24'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr class="shaded"><td class="hours_type">Bereavement</td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][48]" maxlength="5" size="1" class="hrs"';
	if((isset($previous[$v]))&&(isset($previous[$v]['48']))){echo ' value="'.$previous[$v]['48'].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';

if(isset($previous)){
	$selects = array();
	foreach ($previous as $prev_date=>$prev_codes){
		if (($prev_date >= $pp_midweek_start_date)&&($prev_date <= $pp_end_date)){
			foreach ($prev_codes as $code=>$hours){
				if (isset($other_hours[$code])){
					$selects[] = $code;
					}
				}
			}
		}
	}

echo '<tr><td class="hours_type">Other <select name="Other1">
	<option value="X" selected="selected">- Select -</option>';
foreach ($other_hours as $code=>$desc){
	echo '<option value="'.$code.'"';
	if (count($selects)>0){
		if ($selects[0] == $code){
			$first_code = $selects[0];
			echo 'selected="selected"';
			}
		}
	echo '>'.$desc.'</option>';
	}
echo '</select></td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][';
	if (isset($first_code)){echo $first_code;}
	else {echo 'X';}
	echo ']" maxlength="5" size="1" class="hrs"';
	if ((count($selects)>0)&&(isset($previous[$v][$first_code]))){echo 'value="'.$previous[$v][$first_code].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';
echo '<tr class="shaded"><td class="hours_type">Other <select name="Other1">
	<option value="Y" selected="selected">- Select -</option>';
foreach ($other_hours as $code=>$desc){
	echo '<option value="'.$code.'"';
	if (count($selects)>1){
		if ($selects[1] == $code){
			$second_code = $selects[1];
			echo 'selected="selected"';
			}
		}
	echo '>'.$desc.'</option>';
	}
echo '</select></td>';
foreach ($array as $k=>$v){
	echo '<td class="entry"><input type="text" name="time_entry['.$v.'][';
	if (isset($second_code)){echo $second_code;}
	else {echo 'Y';}
	echo ']" maxlength="5" size="1" class="hrs"';
	if ((count($selects)>1)&&(isset($previous[$v][$second_code]))){echo 'value="'.$previous[$v][$second_code].'"';}
	echo '/></td>';
	}
echo '<td class="total"></td></tr>';

echo '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td class="week_style label" colspan="2">Weekly Total</td><td data-week="2" class="week_style weekly_total"></td></tr>';
echo '</table>';

echo '<input type="submit" name="submit" value="Confirm Timesheet" />
	<input type="hidden" name="confirmed" value="TRUE" />
	<input type="hidden" name="pp_start_date" value="'.$pp_start_date.'" />
	<input type="hidden" name="pp_id" value="'.$pp_id.'" />';
echo '</form>';
echo '</div>';

include ('./includes/footer.html');
?>