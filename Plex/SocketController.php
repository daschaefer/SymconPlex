<?

$socket_id = @IPS_GetProperty(IPS_GetParent($_IPS['SELF']), "ClientSocket");
$client_ip = @IPS_GetProperty(IPS_GetParent($_IPS['SELF']), "ClientIP");
$client_port = @IPS_GetProperty(IPS_GetParent($_IPS['SELF']), "ClientPort");

if($_IPS['SENDER'] == "TimerEvent") {
	if($socket_id > 0) {
		$socket = IPS_GetInstance($socket_id);

		if(checkMediaClient($client_ip, $client_port) && $socket['InstanceStatus'] == 104) {
            // Open socket
            open($socket_id);
        } else if(!checkMediaClient($client_ip, $client_port) && ($socket['InstanceStatus'] == 102 || $socket['InstanceStatus'] >= 200)) {
            // Close socket
            close($socket_id);
        }
	}
}


// checks availability of plex media client
function checkMediaClient($ip, $port=3005) {
    $status = @fsockopen ($ip, $port, $errno, $errstr, 1);
    if(!$status){
        return false;
    } else {
        return true;
    }
}

// activates socket and set variables visible
function open($socket_id) {
    SetValue(IPS_GetObjectIDByIdent("ClientStatus", IPS_GetParent($_IPS['SELF'])), true);
    SetValue(IPS_GetObjectIDByIdent("ClientPower", IPS_GetParent($_IPS['SELF'])), true);
    SetValue(IPS_GetObjectIDByIdent("PlayerControls", IPS_GetParent($_IPS['SELF'])), 1);
    SetValue(IPS_GetObjectIDByIdent("PlayerID", IPS_GetParent($_IPS['SELF'])), 1);

    IPS_SetProperty($socket_id, "Open", true);
    IPS_ApplyChanges($socket_id);
}

// deactivates socket and set variables invisible
function close($socket_id) {
    SetValue(IPS_GetObjectIDByIdent("ClientStatus", IPS_GetParent($_IPS['SELF'])), false);
    SetValue(IPS_GetObjectIDByIdent("ClientPower", IPS_GetParent($_IPS['SELF'])), false);
    
    IPS_SetProperty($socket_id, "Open", false);
    IPS_ApplyChanges($socket_id);

    SetValue(IPS_GetObjectIDByIdent("Title", IPS_GetParent($_IPS['SELF'])), "");
    SetValue(IPS_GetObjectIDByIdent("Status", IPS_GetParent($_IPS['SELF'])), "");
    
    IPS_SetMediaFile(IPS_GetObjectIDByIdent("Cover", IPS_GetParent($_IPS['SELF'])), "Transparent.png", false);
    IPS_SetMediaContent(IPS_GetObjectIDByIdent("Cover", IPS_GetParent($_IPS['SELF'])), "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==");
    IPS_SendMediaEvent(IPS_GetObjectIDByIdent("Cover", IPS_GetParent($_IPS['SELF'])));
    
    // SetValue(IPS_GetObjectIDByIdent("ShuffleControls", $_IPS['SELF']), 0);
    
    SetValue(IPS_GetObjectIDByIdent("PlayerID", IPS_GetParent($_IPS['SELF'])), -1);
}

?>