<?php
$conn = new mysqli("localhost", "root", "", "citizen_connect");
$res = $conn->query("DESC notifications");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
