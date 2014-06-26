<?php

//echo pathinfo('abc.csv',PATHINFO_FILENAME);

//writes to csv
/*
$a = ['hey','buff'];

foreach ($a as $key=> $value) {
	$value = 'chandeg';
	if($key = 1) {
		$a[$key] = 'che';
	}
}*/

//print_r($a);

$abc = '15/02/02/05/2014';

	if(preg_match_all("/^([0-6][0-9])\/([0-1][0-9]|2[0-4])\/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/(\d\d\d\d)$/", $abc) == 0) {
		echo "no match";
			}
			else {
				$t = explode('/', $abc);
				print_r($t);
				if ($time = mktime($t[1], $t[0], 0, $t[3], $t[2], $t[4])) {
					echo $time;
				}
				else {
					echo "wrong time";
				}
			}

$list = array (
    array('aaa', 'bbb', 'ccc', 'dddd'),
    array('123', '456', '789'),
    array('"aaa"', '"bbb"')
);

$fp = fopen('file.csv', 'w');

foreach ($list as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);

/*
?>

<script src="http://localhost/store/backend/views/js/jquery-1.10.2.js"></script>
<script src="http://localhost/store/backend/views/js/ajax.js" type="text/javascript"></script>

<script type="text/javascript">
function send() {
	var user_details = {
			'confirm-password' : 'garvit',
			'user-email' : 'garvitdelhi@gmail.com',
			'user-username' : 'garvitdelhi'
		};
	$('#username').keyup(function(event){
		ajax('http://localhost/store/backend/assign_sub_admin/show',{'user':$('#username').val()}).done(function(data) {
    			$("#user-show").html(data); 
  		});
	});
}
</script>
<button onclick="send()">click</button>
<input></input?>*/
?>