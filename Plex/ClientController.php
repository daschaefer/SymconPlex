<?

$socketID = PHT_GetSocketID(IPS_GetParent($_IPS['SELF']));
$parentID = IPS_GetParent($_IPS['SELF']);


if($_IPS['SENDER'] == "WebFront" && isset($_IPS['VALUE'])) { // Client Control via Webfront
    $socket = IPS_GetObject($socketID);
    $player_id = GetValue(IPS_GetObjectIDByIdent("PlayerID", $parentID));

    $command = "";
    switch (IPS_GetObject($_IPS['VARIABLE'])['ObjectIdent']) {
        case "Volume": // volume
            $command = '{"jsonrpc":"2.0","method":"Application.SetVolume","params":{"volume": '.$_IPS['VALUE'].'},"id":1}';
        break;
        case "PlayerControls": // player control
            switch($_IPS['VALUE']) {
                case 0: // prev
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.GoTo","params":{"playerid":'.$player_id.', "to":"previous"},"id":1}';
                    break;
                case 1: // stop
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.Stop","params":{"playerid":'.$player_id.'},"id":1}';
                    break;
                case 2: // pause
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.PlayPause","params":{"playerid":'.$player_id.'},"id":1}';
                    break;
                case 3: // play
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.PlayPause","params":{"playerid":'.$player_id.'},"id":1}';
                    break;
                case 4: // next
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.GoTo","params":{"playerid":'.$player_id.', "to":"next"},"id":1}';
                    break;
            }
        break;
        case "Controls": // movement player control
            switch($_IPS['VALUE']) {
                case 0: // up
                    $command = '{"jsonrpc":"2.0","method":"Input.Up","params":{},"id":1}';
                    break;
                case 1: // down
                    $command = '{"jsonrpc":"2.0","method":"Input.Down","params":{},"id":1}';
                    break;
                case 2: // left
                    $command = '{"jsonrpc":"2.0","method":"Input.Left","params":{},"id":1}';
                    break;
                case 3: // right
                    $command = '{"jsonrpc":"2.0","method":"Input.Right","params":{},"id":1}';
                    break;
                case 4: // select
                    $command = '{"jsonrpc":"2.0","method":"Input.Select","params":{},"id":1}';
                    break;
                case 5: // back
                    $command = '{"jsonrpc":"2.0","method":"Input.Back","params":{},"id":1}';
                    break;
            }
        break;
        case "RepeatControls": // repeat
            switch($_IPS['VALUE']) {
                case 0: // off
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.SetRepeat","params":{"playerid":'.$player_id.', "repeat":"off"},"id":1}';
                    break;
                case 1: // actual element
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.SetRepeat","params":{"playerid":'.$player_id.', "repeat":"one"},"id":1}';
                    break;
                case 2: // all
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.SetRepeat","params":{"playerid":'.$player_id.', "repeat":"all"},"id":1}';
                    break;
            }
        break;
        case unifyIdent("ShuffleControls"): // repeat
            switch($_IPS['VALUE']) {
                case 0: // off
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.SetShuffle","params":{"playerid":'.$player_id.', "shuffle":"toggle"},"id":1}';
                    break;
                case 1: // actual element
                    if($player_id >= 0)
                        $command = '{"jsonrpc":"2.0","method":"Player.SetShuffle","params":{"playerid":'.$player_id.', "shuffle":"toggle"},"id":1}';
                    break;
            }
            break;
        case "ClientPower": // repeat
            switch($_IPS['VALUE']) {
                case 0: // Shutdown the whole system
                        $command = '{"jsonrpc":"2.0","method":"System.Shutdown","params":{},"id":1}';
                    break;
                case 1: // Einschalten
							//wake($client_mac);
                    break;
            }
        break;
    }
    if(strlen($command) > 0) {
        PHT_Send($parentID, $command);

        SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
    }
}
?>
