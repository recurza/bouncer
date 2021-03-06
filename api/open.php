<?php
require dirname(__FILE__) . '/../inc/common.php';
require dirname(__FILE__) . '/../inc/mailer.php';
require dirname(__FILE__) . '/../inc/db.php';


$rfid = $_POST['rfid'];
$password = $_POST['password'];
$password2 = $link->escapeString($password);

// Register MAC address
$mac = find_mac();
if ($mac)
	$mac2 = ", mac = '".sha1('salT'.$mac)."'";
else
	$mac2 = '';
if ($rfid)
        $rfid2 = ", rfid = '".$link->escapeString($rfid)."'";
else
        $rfid2 = '';

// Give new members the benefit of the doubt (trust, but verify):
$query = "UPDATE Users SET count = count + 1$mac2$rfid2, last_seen = DATETIME('now') WHERE DATE('now') <= MAX(IFNULL(paid,0),IFNULL(paid_verified,0)) AND password = '$password2'";
if (!$link->exec($query))
{
	// Failed; but might be because of mac address or rfid
	if ($rfid)
		$link->exec("UPDATE Users SET rfid = NULL WHERE rfid = '".$link->escapeString($rfid)."'");
	$link->exec("UPDATE Users SET count = count + 1$rfid2, last_seen = DATETIME('now') WHERE DATE('now') <= MAX(IFNULL(paid,0),IFNULL(paid_verified,0)) AND password = '$password2'")
		or mail_and_die('link->exec UPDATE error', $query."\n\n-- ".__FILE__);
}

if ($link->changes() != 1)
{
	header('Location: ../accessdenied.html', true, 303);
}
else
{
        ignore_user_abort(true);
        header('Location: ../welcomeback.html', true, 303);
        header("Connection: close");
        header("Content-Length: 0");
        ob_end_flush();
        flush();
	open_door();
}
$link->close();
unset($link);

