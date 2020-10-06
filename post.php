
<?php

	require_once("./db_config.php");

	
	if(!isset($_REQUEST["action"])) {
		echo json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
		exit;
	}

	$action = $_REQUEST["action"];

	if($action == "test") {
		$sql = 'select Bill.Bill_ID, Bill.Dollar, Bill.Date, Bill.Thing, Bill.Returned, Bill.Return_Date, B.Name Borrower, L.Name Lender
		from ' . $BILL_TABLE . ' Bill
		inner join Users B on Bill.Borrower= B.USER_ID
		inner join Users L on Bill.Lender= L.USER_ID
		where Bill.Lender = ?;';

		$stmt = $mysqli->prepare($sql);
		
		$id = $_REQUEST["id"];
		$stmt->bind_param("i", $id);
		$stmt->execute();

		$result = $stmt->get_result();
		$rows = $result->fetch_all(MYSQLI_ASSOC);
		//var_dump ($rows);
		$stmt->close();
		echo json_encode($rows, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "load_users")
	{
		
		$sql = 'SELECT * FROM ' . $USER_TABLE;
		$stmt = $mysqli->prepare($sql);
		$stmt->execute();

		$result = $stmt->get_result();
		$rows = $result->fetch_all(MYSQLI_NUM);
		$arr = Array();
		foreach($rows as $row) {
			$arr[$row[0]] = $row[1];
		}
		//var_dump ($rows);
		$stmt->close();
		echo json_encode($arr, JSON_UNESCAPED_UNICODE);

		/*$user_array = Array();
		$sql = "SELECT * FROM `$USER_TABLE`";
		$result = mysqli_query($mysqli, $sql);
		while($row = mysqli_fetch_array($result))
		{
			array_push($user_array, Array($row["Name"], $row["User_ID"]));
		}
		echo json_encode($user_array, JSON_UNESCAPED_UNICODE);*/
	}
	
	else if($action == "add_bill")
	{
		
		$borrower = @$_REQUEST["borrower"];
		$lender = @$_REQUEST["lender"];
		$dollar = @$_REQUEST["dollar"];
		$thing = @$_REQUEST["thing"];
		$date = @$_REQUEST["date"];
		
		$sql = "INSERT INTO `$BILL_TABLE` (`Borrower`, `Lender`, `Dollar`, `Date`, `Thing`) VALUES (?, ?, ?, ?, ?);";
		
		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param("iiiss", $borrower, $lender, $dollar, $date, $thing);

		$result = $stmt->execute();
		$stmt->close();

		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "load_detail")
	{
		//$users = Array("JOHN", "Cliff");
		$load_settled = $_REQUEST["load_settled"];
		$users = $_REQUEST["users"];

		$ret = Array();

		if(count($users) == 1)
		{
			$sql = "SELECT * FROM `$BILL_TABLE` WHERE (`Borrower` = ? OR `Lender` = ?) AND `Returned` = ? ORDER BY `Bill_ID` DESC";
	
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param("iii", $users[0], $users[0], $load_settled);
			$stmt->execute();
	
			$result = $stmt->get_result();
			$rows = $result->fetch_all(MYSQLI_ASSOC);
			//var_dump ($rows);
			$stmt->close();
			//echo json_encode($rows, JSON_UNESCAPED_UNICODE);
		}
		else if(count($users) == 2)
		{
			$sql = "SELECT * FROM $BILL_TABLE 
			WHERE ((`Borrower` = ? AND `Lender` = ?)
				OR (`Borrower` = ? AND `Lender` = ?))
				AND `Returned` = ?
				ORDER BY `Bill_ID` DESC";

			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param("iiiii", $users[0], $users[1], $users[1], $users[0],$load_settled);
			$stmt->execute();

			$result = $stmt->get_result();
			$rows = $result->fetch_all(MYSQLI_ASSOC);
			//var_dump ($rows);
			$stmt->close();
			//echo json_encode($rows, JSON_UNESCAPED_UNICODE);
	
			/*if($one_to_many_user != "")
			{
				$delete_index = array_search($one_to_many_user, $users);
				//echo $delete_index;
				array_splice($users, $delete_index, 1);
				$users_text =  "('" . join("', '", $users) . "')";
				$sql = "SELECT * FROM `$BILL_TABLE` WHERE ((`Borrower` LIKE '$one_to_many_user' AND `Lender` IN $users_text) OR (`Borrower` IN $users_text AND `Lender` LIKE '$one_to_many_user'))";

			}
			else
			{
				$users_text =  "('" . join("', '", $users) . "')";
				$sql = "SELECT * FROM `$BILL_TABLE` WHERE (`Borrower` IN $users_text AND `Lender` IN $users_text)";
			}*/
			
		}
		else {
			$cnt = count($users);
			$param = implode(',', array_fill(0, $cnt, '?'));
			$sql = "SELECT * FROM `$BILL_TABLE` WHERE `Borrower` IN ($param) AND `Lender` IN ($param) AND `Returned` = ? ORDER BY `Bill_ID` DESC";
			
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param(str_repeat("i", $cnt*2+1), ...$users, ...$users, ...[$load_settled]);
			// php don't allow positional argument after argument unpacking
			$stmt->execute();

			$result = $stmt->get_result();
			$rows = $result->fetch_all(MYSQLI_ASSOC);
			//var_dump ($rows);
			$stmt->close();
			//echo json_encode($rows, JSON_UNESCAPED_UNICODE);
		}

		/*if($_REQUEST["query_type"]=="all")
			$new_sql = 	$sql . " AND `Returned` = 1 ORDER BY `$BILL_TABLE`.`Date` DESC";
		else
			$new_sql = "SELECT NULL";
		$sql = $sql . " AND `Returned` = 0 ORDER BY `$BILL_TABLE`.`Date` DESC";
		$detail = Array("unsettle" => Array(), "settled" => Array(), "length_unsettle" => 0);
		
		$result = mysqli_query($mysqli, $sql);
		while($row = mysqli_fetch_array($result))
		{
			array_push($detail["unsettle"], $row);
		}
		
		$result = mysqli_query($mysqli, $new_sql);
		while($row = mysqli_fetch_array($result))
		{
			if(is_null($row[0]))
				break;
			array_push($detail["settled"], $row);
		}
		$detail["length_unsettle"] = count($detail["unsettle"]);
		echo json_encode($detail, JSON_UNESCAPED_UNICODE);*/

		echo json_encode($rows, JSON_UNESCAPED_UNICODE);
	}

	else if($action == "return")
	{
		
		$ids = $_REQUEST["ids"];
		//$return_id_settled = $_REQUEST["return_id_settled"];
		
		$date = date("Y-m-d");
		$cnt = count($ids);
		$param = implode(',', array_fill(0, $cnt, '?'));
		$sql = "UPDATE  `$BILL_TABLE`
			SET Returned = 1, Return_Date = ?
			WHERE `Bill_ID` IN ($param)";

		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param("s".str_repeat("i", $cnt), $date, ...$ids);
		$result = $stmt->execute();
		
		$stmt->close();
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "unreturn") {
		$ids = $_REQUEST["ids"];
		//$return_id_settled = $_REQUEST["return_id_settled"];
		
		$date = date("Y-m-d");
		$cnt = count($ids);
		$param = implode(',', array_fill(0, $cnt, '?'));
		$sql = "UPDATE  `$BILL_TABLE`
			SET Returned = 0, Return_Date = NULL
			WHERE `Bill_ID` IN ($param)";

		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param(str_repeat("i", $cnt), ...$ids);
		$result = $stmt->execute();
		
		$stmt->close();
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "delete")
	{
		$ids = $_REQUEST["ids"];
		//$return_id_settled = $_REQUEST["return_id_settled"];
		
		$cnt = count($ids);
		$param = implode(',', array_fill(0, $cnt, '?'));
		$sql = "DELETE FROM `$BILL_TABLE`
			WHERE `Bill_ID` IN ($param)";

		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param(str_repeat("i", $cnt), ...$ids);
		$result = $stmt->execute();
		
		$stmt->close();
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
		/*$delete_id = $_REQUEST["delete_id"];
		
		$length = count($delete_id);
		for($i = 0; $i < $length; $i++)
		{
			$sql =  "DELETE FROM `$BILL_TABLE` WHERE `$BILL_TABLE`.`Bill_ID` = '$delete_id[$i]'";
			$result = mysqli_query($mysqli, $sql);
		}
		echo json_encode($result, JSON_UNESCAPED_UNICODE);*/
	}
	else if($action == "edit_bill")
	{
		$edit_item_id = $_REQUEST["edit_item_id"];
		$borrower = @$_REQUEST["borrower"];
		$lender = @$_REQUEST["lender"];
		$dollar = @$_REQUEST["dollar"];
		$thing = @$_REQUEST["thing"];
		$date = @$_REQUEST["date"];

		$sql = "UPDATE `$BILL_TABLE` 
			SET `Borrower` = ?, 
			`Lender` = ?, 
			`Dollar` = ?, 
			`Date` = ?, 
			`Thing` = ?
			WHERE `$BILL_TABLE`.`Bill_ID` = ?;";
		
		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param("iiissi", $borrower, $lender, $dollar, $date, $thing, $edit_item_id);
		$result = $stmt->execute();
		
		$stmt->close();
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	/*else if($action == "edit_s")
	{
		switch(@$_REQUEST["item"]) {
			case "1":
				$item = "Thing";
				break;
			case "2":
				$item = "Date";
				break;
			default:
				echo "false";
				exit;
		}
		$new_value = $_REQUEST["new_value"];
		$edit_item_id = $_REQUEST["edit_item_id"];
		
		$sql =  "UPDATE `$BILL_TABLE` 
			SET `$item` = ?
			WHERE `$BILL_TABLE`.`Bill_ID` = ?";
		
		$stmt = $mysqli->prepare($sql);

		$stmt->bind_param("si", $new_value, $edit_item_id);
		$result = $stmt->execute();
		
		$stmt->close();
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "edit_i")
	{
		switch(@$_REQUEST["item"]) {
			case "1":
				$item = "Borrower";
				break;
			case "2":
				$item = "Lender";
				break;
			case "3":
				$item = "Dollar";
				break;
			default:
				echo "false";
				exit;
		}
		$new_value = $_REQUEST["new_value"];
		$edit_item_id = $_REQUEST["edit_item_id"];
		
		$sql =  "UPDATE `$BILL_TABLE` 
			SET `$item` = ?
			WHERE `$BILL_TABLE`.`Bill_ID` = ?";
		
		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param("ii", $new_value, $edit_item_id);
		$result = $stmt->execute();
		
		$stmt->close();
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}*/

	/*
	else if($action == "swap_bill_user")
	{
		$new_borrower = $_REQUEST["new_borrower"];
		$new_lender = $_REQUEST["new_lender"];
		$edit_item_id = $_REQUEST["edit_item_id"];
		
		$sql =  "UPDATE `$BILL_TABLE` SET `Borrower` = '$new_borrower', `Lender` = '$new_lender' WHERE `$BILL_TABLE`.`Bill_ID` = $edit_item_id;";
		$result = mysqli_query($mysqli, $sql);
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "add_comment")
	{
		$user = $_REQUEST["user"];
		$comment = $_REQUEST["comment"];
		$comment_sticky = $_REQUEST["comment_sticky"];
		$now_date = date('Y-m-d');
		$sql =  "INSERT INTO `$COMMENT_TABLE` (`Comment_ID`, `User`, `Comment`, `Date`, `Top`) VALUES (NULL, '$user', '$comment', '$now_date', '$comment_sticky')";
		$result = mysqli_query($mysqli, $sql);
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "load_comment")
	{
		$comment_array = Array();
		$sql = "SELECT * FROM `$COMMENT_TABLE` ORDER BY `$COMMENT_TABLE`.`Date` DESC, `$COMMENT_TABLE`.`Comment_ID` DESC";
		$result = mysqli_query($mysqli, $sql);
		while($row = mysqli_fetch_array($result))
		{
			array_push($comment_array, Array($row["User"], $row["Comment"], $row["Comment_ID"], $row["Date"], $row["Top"]));
		}
		
		echo json_encode($comment_array, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "delete_comment")
	{
		$delete_id = $_REQUEST["delete_id"];
		
		$length = count($delete_id);
		for($i = 0; $i < $length; $i++)
		{
			$sql =  "DELETE FROM `$COMMENT_TABLE` WHERE `$COMMENT_TABLE`.`Comment_ID` = '$delete_id[$i]'";
			$result = mysqli_query($mysqli, $sql);
		}
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "edit_comment")
	{
		$new_value = $_REQUEST["new_value"];
		$edit_id = $_REQUEST["edit_id"];
		$edit_item = $_REQUEST["edit_item"];
		
		$sql =  "UPDATE `$COMMENT_TABLE` SET `$edit_item` = '$new_value' WHERE `$COMMENT_TABLE`.`Comment_ID` = '$edit_id'";
		$result = mysqli_query($mysqli, $sql);
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	else if($action == "stick_comment")
	{
		$id_sticky = $_REQUEST["id_sticky"];
		$id_non_sticky = $_REQUEST["id_non_sticky"];
		
		$length = count($id_sticky);
		for($i = 0; $i < $length; $i++)
		{
			$sql = "UPDATE `$COMMENT_TABLE` SET `Top` = '0' WHERE `$COMMENT_TABLE`.`Comment_ID` = $id_sticky[$i]";
			$result = mysqli_query($mysqli, $sql);
		}
		
		$length = count($id_non_sticky);
		for($i = 0; $i < $length; $i++)
		{
			$sql = "UPDATE `$COMMENT_TABLE` SET `Top` = '1' WHERE `$COMMENT_TABLE`.`Comment_ID` = $id_non_sticky[$i]";
			$result = mysqli_query($mysqli, $sql);
		}
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	}*/

?>