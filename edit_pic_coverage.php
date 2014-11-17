<?php # edit_pic_coverage.php
include('./includes/supersessionstart.php');
$came_from = $_SESSION['came_from'];

if (($came_from != 'view_pic_coverage') && ($came_from != 'edit_pic_coverage')){
	header ('Location: view_pic_coverage');
	}

include('./includes/allsessionvariables.php');

if (isset($_POST['from_view_pic'])){
	$_SESSION['pic_coverage_name'] = $_POST['first_name'];
	$_SESSION['pic_employee_number'] = $_POST['employee_number'];
	$_SESSION['pic_coverage_date'] = $_POST['date'];
	$_SESSION['pic_coverage_id'] = $_POST['pic_coverage_id'];
	header('Location:edit_pic_coverage');
	}
else{
	$first_name = $_SESSION['pic_coverage_name'];
	$employee_number = $_SESSION['pic_employee_number'];
	$date = $_SESSION['pic_coverage_date'];
	$pic_coverage_id = $_SESSION['pic_coverage_id'];
	}

$pic_poss = array();

$query = "SELECT employee_number, first_name, last_name FROM employees WHERE active='Active' and pic_status='Y'
	ORDER BY last_name";
$result = mysql_query($query);
if ($result){
	$num_rows = mysql_num_rows($result);
	if ($num_rows != 0) {
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$empno = $row['employee_number'];
			$fn = $row['first_name'];
			$ln = $row['last_name'];
			$pic_poss[$empno] = array($fn,$ln);
			}
		}
	}
else{
	$no_results = TRUE;
	}
		
	//Check if the form has been submitted.
if (isset($_POST['edited'])) {
	$new_pic = $_POST['pic_poss'];
	$pid = $_POST['pic_coverage_id'];
	$_SESSION['pic_coverage_name'] = $pic_poss[$new_pic][0];
		
	$query = "UPDATE pic_coverage SET employee_number='$new_pic' WHERE pic_coverage_id = '$pid'";
	$result = mysql_query($query) or die(mysql_error($dbc));
	if ($result) {//If it ran okay.
		$_SESSION['success'] = TRUE;
		header ('Location: view_pic_coverage');
		}
	}

$page_title = "Edit PIC Coverage" ;
include ('./includes/header.html');
include ('./includes/supersidebar.html');	
?>
<div id="edit_pic_coverage"><span class="date"><h1>Edit PIC Coverage</h1></span>
<div class="coverform">
	<form action="edit_pic_coverage" method="post">
		<div class="label">PIC Coverage Date:</div>
		<div class="cal"><b>
			<?php echo $date;?></b>
		</div><br/>
		<div class="label">PIC:</div>
		<select name="pic_poss">
			<option value="select" disabled="disabled" selected="selected">- Select -</option>
<?php
			foreach ($pic_poss as $pk=>$pv){
				echo '<option value="' . $pk . '" ';
				if ($employee_number == $pk){
					echo 'selected="selected"';
					}
				echo '>'.$pv[0].'</option>';
				}
?>
		</select>
		<p><input type="submit" name="submit" value="Save" /></p>
			<input type="hidden" name="edited" value="TRUE" />
			<input type="hidden" name="pic_coverage_id" value="<?php echo $pic_coverage_id;?>" />
	</form>
</div>
</div>
		
<?php
include ('./includes/footer.html');
?>