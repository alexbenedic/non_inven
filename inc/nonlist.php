
<?php
    $servername = "localhost";
$username = "root";
$password = "root";
$dbname = "ocsweb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "SELECT SQL_CALC_FOUND_ROWS  * from (select inv.RSX as ID,
                                     
                                          non_ident.c as 'NON_INVENTORIE'
                                                                 
                          from (SELECT COUNT(DISTINCT hardware_id) as c,'IPDISCOVER' as TYPE,tvalue as RSX
                                        FROM devices
                                        WHERE name='IPDISCOVER' GROUP BY tvalue)
                                ipdiscover right join
                                   (SELECT count(distinct(hardware_id)) as c,'INVENTORIE' as TYPE,ipsubnet as RSX
                                        FROM networks left join subnet on networks.ipsubnet=subnet.netid
                                        WHERE status='Up' GROUP BY ipsubnet)
                                inv on ipdiscover.RSX=inv.RSX left join
                                        (SELECT COUNT(DISTINCT mac) as c,'IDENTIFIE' as TYPE,netid as RSX
                                        FROM netmap
                                        WHERE mac IN (SELECT DISTINCT(macaddr) FROM network_devices)
                                                GROUP BY netid)
                                ident on ipdiscover.RSX=ident.RSX left join
                                        (SELECT COUNT(DISTINCT mac) as c,'NON IDENTIFIE' as TYPE,netid as RSX
                                        FROM netmap n
                                        LEFT JOIN networks ns ON ns.macaddr=n.mac
                                        WHERE n.mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices)
                                                and (ns.macaddr IS NULL)
                                                GROUP BY netid)
                                non_ident on non_ident.RSX=inv.RSX
                                ) toto order by ID asc
;";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $x=0;
    // output data of each row
    while($row = $result->fetch_assoc()) {
        $ipnet[$x] = $row["ID"];
        $x++;
//        echo $row["ID"]."<br>";
    }
} else {
    echo "0 results";
}
$filename = "NON_INVENTORIE.csv";

 
  header('Content-Type: text/csv; charset=utf-8');

  header('Content-Disposition: attachment; filename='.$filename);
  $output = fopen("php://output","w");
  fputcsv($output, array('#','IP','MAC','MASK','DATE','DNS Name','MAC Name'));

for ($i=0; $i<$x; $i++)
{
    $sql = " SELECT SQL_CALC_FOUND_ROWS  ip, mac, mask, date, name FROM netmap n
                            LEFT JOIN networks ns ON ns.macaddr=n.mac
                            WHERE n.netid='".$ipnet[$i]."'
                            AND (ns.macaddr IS NULL)
                            AND mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices) order by INET_ATON(ip) asc ";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
// fputcsv($output,  array($total,$row['ip'],$row['mac'],$row['mask'],$row['date'],$row['name']));
      $ipadd[] = $row["ip"];
        $mac[] = $row["mac"];
        $mask[] = $row["mask"];
        $date[] = $row["date"];
        $name[] = $row["name"];
        $maccadd[] = substr($row["mac"], 0, -9);
      
    }
}
     
}
//print_r ($maccadd);
$conn->close();
$all = sizeof($mac);
//echo $all;
$dbname = "glpi";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
for ($x=0; $x<$all; $x++)
{
 $sql = "SELECT *
FROM `glpi_plugin_fusioninventory_ouis`
WHERE `mac` = '".$maccadd[$x]."'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
//      echo $row["mac"]."<br>";
//      echo $row["mac"]."<br>";
       $namemac[] = $row["name"];
    }
} else {
   $namemac[] = "";
}   
}
//print_r ($namemac);
for ($q=0; $q<$all; $q++)
{
  fputcsv($output,  array($q, $ipadd[$q], $mac[$q], $mask[$q], $date[$q], $name[$q], $namemac[$q]));  
}
fclose($output);
$conn->close();
    ?>