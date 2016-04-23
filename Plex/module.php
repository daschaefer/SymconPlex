<?

if (!defined("PAUSED"))
    define("PAUSED", "Pausiert");
if (!defined("PLAY"))
    define("PLAY", "Spielt");
if (!defined("STOPPED"))
    define("STOPPED", "");
if (!defined("EPISODE"))
    define("EPISODE", "Serie");
if (!defined("MOVIE"))
    define("MOVIE", "Film");
if (!defined("PICTURE"))
    define("PICTURE", "Foto");
if (!defined("VOLUME"))
    define("VOLUME", "Lautstärke");
if (!defined("STATUS"))
    define("STATUS", "Status");
if (!defined("COVER"))
    define("COVER", "Cover");
if (!defined("TITLE"))
    define("TITLE", "Titel");
if (!defined("SONG"))
    define("SONG", "Song");

class Plex extends IPSModule
{
        
    public function Create()
    {
        parent::Create();
        
        // Public properties
        $this->RegisterPropertyString("ClientIP", "");
        $this->RegisterPropertyInteger("ClientPort", 3005);
        $this->RegisterPropertyString("ClientMAC", "");
        $this->RegisterPropertyString("ServerIP", "");
        $this->RegisterPropertyInteger("ServerPort", 32400);
        $this->RegisterPropertyInteger("ClientSocket", 0);
        $this->RegisterPropertyString("XPlexToken", "");

        // Private properties
        $this->RegisterPropertyInteger("ItemID", 0);
        $this->RegisterPropertyInteger("PlayerID", 0);
        $this->RegisterPropertyInteger("PageRptCount", 3);

        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
    }
    
    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Start create profiles
        $this->RegisterProfileIntegerEx("PLEX.Controls", "Move", "", "",         Array(
                                                                                            Array(-1, "Zurück", "", -1),
                                                                                            Array(0, "Hoch", "", -1),
                                                                                            Array(1, "Runter", "", -1),
                                                                                            Array(2, "Links", "", -1),
                                                                                            Array(3, "Rechts", "", -1),
                                                                                            Array(4, "Seite +", "", -1),
                                                                                            Array(5, "Seite -", "", -1),
                                                                                            Array(10, "Auswahl", "", -1),
                                                                                            ));

        $this->RegisterProfileIntegerEx("PLEX.PlayerControls", "Script", "", "",   Array(  
                                                                                            Array(0, "Prev", "", -1),
                                                                                            Array(1, "Stop", "", 0xFF0000),
                                                                                            Array(2, "Pause", "", 0xFFCC00),
                                                                                            Array(3, "Play", "", 0x99CC00),
                                                                                            Array(4, "Next", "", -1)
                                                                                            ));

        $this->RegisterProfileIntegerEx("PLEX.RepeatControls", "Repeat", "", "",   Array(  
                                                                                            Array(0, "Aus", "", -1),
                                                                                            Array(1, "Aktuelles", "", 0x99CC00),
                                                                                            Array(2, "Alle", "", 0x99CC00)
                                                                                            ));

        $this->RegisterProfileBooleanEx("PLEX.ClientStatus", "Information", "", "",     Array(  
                                                                                            Array(false, "Inaktiv", "", -1),
                                                                                            Array(true, "Aktiv", "", -1)
                                                                                            ));

        $this->RegisterProfileBooleanEx("PLEX.ClientPower", "Power", "", "",      Array(  
                                                                                            Array(false, "Ausschalten", "", 0xFF0000),
                                                                                            Array(true, "Einschalten", "", 0x99CC00)
                                                                                            ));

        $this->RegisterProfileInteger("PLEX.Volume", "Speaker", "", " %", 0, 100, 1);

        // Create SocketController script
        $socketControllerScriptID = @$this->GetIDForIdent("SocketController");
        if($socketControllerScriptID === false) {
            $socketControllerScriptID = $this->RegisterScript("SocketController", "SocketController", file_get_contents(__DIR__ . "/SocketController.php"), 100);
        } else {
            IPS_SetScriptContent($socketControllerScriptID, file_get_contents(__DIR__ . "/SocketController.php"));
        }
        IPS_SetHidden($socketControllerScriptID, true);
        
        $socketCheck = @IPS_GetEventIDByName("SocketCheck", $socketControllerScriptID);
        if(!$socketCheck) {
            $socketCheck = IPS_CreateEvent(1);
            IPS_SetParent($socketCheck, $socketControllerScriptID);
            IPS_SetName($socketCheck, "SocketCheck");
            IPS_SetEventCyclic($socketCheck, 0, 0, 0, 2, 1, 1);
            IPS_SetEventActive($socketCheck, true);
        }

        // Create variables
        $coverID = @$this->GetIDForIdent("Cover");
        
        if($coverID != false && IPS_GetObject($coverID)['ObjectType'] != 5) { // migrate from variable to media
            if(IPS_DeleteVariable($coverID))
                $coverID = false;
        }
        if($coverID == false) {
            $coverID = IPS_CreateMedia(1);
            IPS_SetIdent($coverID, "Cover");
            IPS_SetName($coverID, "Cover");
            IPS_SetIcon($coverID, "Image");
            IPS_SetParent($coverID, $this->InstanceID);
            IPS_SetMediaFile($coverID, "Transparent.png", false);
            IPS_SetMediaContent($coverID, "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==");
            IPS_SendMediaEvent($coverID);
        }
        
        $HTMLID = $this->RegisterVariableString("HTML", "HTML");
        IPS_SetVariableCustomProfile($HTMLID, "~HTMLBox");
        
        $volumeID = $this->RegisterVariableInteger("Volume", "Lautstärke", "PLEX.Volume");
        SetValue($volumeID, 100);
        $this->EnableAction("Volume");
        
        $clientStatusID = $this->RegisterVariableBoolean("ClientStatus", "Client Status", "PLEX.ClientStatus");
        IPS_SetHidden($clientStatusID, true);
        
        $statusID = $this->RegisterVariableString("Status", "Status");
        IPS_SetIcon($statusID, "Information");
        
        $controlsID = $this->RegisterVariableInteger("Controls", "Steuerung", "PLEX.Controls");
        $this->EnableAction("Controls");
        SetValue($controlsID, -1);
        
        $titleID = $this->RegisterVariableString("Title", "Titel");
        IPS_SetIcon($titleID, "Information");
        
        $playerControlsID = $this->RegisterVariableInteger("PlayerControls", "Wiedergabe Steuerung", "PLEX.PlayerControls");
        SetValue($playerControlsID, 1);
        $this->EnableAction("PlayerControls");
        
        $repeatControlsID = $this->RegisterVariableInteger("RepeatControls", "Wiederholung", "PLEX.RepeatControls");
        $this->EnableAction("RepeatControls");
        
        $clientPowerID = $this->RegisterVariableBoolean("ClientPower", "Power", "PLEX.ClientPower");
        $this->EnableAction("ClientPower");

        IPS_SetProperty($this->InstanceID, "ClientSocket", @$this->GetParent());

        // // Set Client Socket properties
        if(strlen(IPS_GetProperty($this->InstanceID, "ClientIP")) > 0) {
            $clientSocketID = @$this->GetParent();

            if($clientSocketID > 0) {
                @IPS_SetProperty($clientSocketID, "Host", IPS_GetProperty($this->InstanceID, "ClientIP"));
                @IPS_SetProperty($clientSocketID, "Port", 9090);

                @IPS_SetName($clientSocketID, "Plex Home Theater Client Socket (".IPS_GetProperty($this->InstanceID, "ClientIP").")");
                @IPS_ApplyChanges($clientSocketID);
            }
        } else {
            $clientSocketID = @$this->GetParent();
            
            if($clientSocketID > 0) {
                @IPS_SetProperty($clientSocketID, "Host", "");
                @IPS_SetProperty($clientSocketID, "Port", 0);

                @IPS_SetName($clientSocketID, "Plex Home Theater Client Socket (unkonfiguriert)");
                @IPS_ApplyChanges($clientSocketID);
            }
        }
    }

    public function RequestAction($Ident, $Value) 
    { 
        switch ($Ident) 
        {
            case "Volume": // volume
                if(IPS_GetProperty($this->GetParent(), "Open")) 
                    $this->SetVolume($Value);
            break; 
            case "PlayerControls": // player control 
                switch($Value) { 
                    case 0: // prev
                        if(IPS_GetProperty($this->GetParent(), "Open"))
                            $this->Prev();     
                        break; 
                    case 1: // stop
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Stop(); 
                        break; 
                    case 2: // pause
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Pause(); 
                        break; 
                    case 3: // play
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Play(); 
                        break; 
                    case 4: // next
                        if(IPS_GetProperty($this->GetParent(), "Open"))  
                            $this->Next(); 
                        break; 
                } 
            break; 
            case "Controls": // movement player control 
                switch($Value) { 
                    case -1: // Back
                        if(IPS_GetProperty($this->GetParent(), "Open"))
                            $this->Back(); 
                        break;
                    case 0: // up
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Up(); 
                        break; 
                    case 1: // down
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Down(); 
                        break; 
                    case 2: // left
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Left(); 
                        break; 
                    case 3: // right
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Right(); 
                        break; 
                    case 4: // page up
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->PgUp(); 
                        break;
                    case 5: // page down
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->PgDown(); 
                        break; 
                    case 10: // select
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->Select(); 
                        break; 
                } 
            break; 
            case "RepeatControls": // repeat 
                switch($Value) { 
                    case 0: // off
                        if(IPS_GetProperty($this->GetParent(), "Open"))
                            $this->RepeatOff();     
                        break; 
                    case 1: // actual element
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->RepeatActualElement();         
                        break; 
                    case 2: // all
                        if(IPS_GetProperty($this->GetParent(), "Open")) 
                            $this->RepeatAll();     
                        break; 
                } 
            break; 
            case "ClientPower": // repeat 
                switch($Value) { 
                    case false: // Shutdown the whole system
                        if(IPS_GetProperty($this->GetParent(), "Open"))
                            $this->PowerOff(); 
                        break; 
                    case true: // Power on the system
                        $this->PowerOn(); 
                        break; 
                } 
            break; 
        }
    }

    public function ReceiveData($JSONString) {
        $JSON = json_decode($JSONString);
        $JSON = json_decode(utf8_decode($JSON->Buffer));

        // react on incoming JSON
        if(isset($JSON->method)) {
            switch ($JSON->method) {
                case 'Player.OnPause':
                    SetValue($this->GetIDForIdent("Status"), PAUSED);
                    SetValue($this->GetIDForIdent("PlayerControls"), 2);
                    break;
                case 'Player.OnPlay':
                    SetValue($this->GetIDForIdent("Status"), PLAY);
                    SetValue($this->GetIDForIdent("PlayerControls"), 3);

                    if(isset($JSON->params->data->item)) {
                        $item = $JSON->params->data->item;
                        $player_id = 0;
                        $item_id = $item->id;
                        switch ($item->type) {
                            case 'episode':
                                $properties = '["title", "rating", "year", "genre", "duration", "thumbnail", "season", "episode", "plot", "cast", "showtitle", "streamdetails"]';
                                $player_id = 1;
                                break;
                            case 'movie':
                                $properties = '["title", "rating", "year", "genre", "duration", "thumbnail", "plot", "cast", "streamdetails"]';
                                $player_id = 1;
                                break;
                            case 'picture':
                                $properties = '["title", "year", "thumbnail"]';
                                $player_id = 1;
                                break;
                            case 'song':
                                $properties = '["title", "artist", "albumartist", "year", "genre", "album", "track", "duration", "thumbnail", "disc"]';
                                $player_id = 0;
                                break;
                            default:
                                $player_id = 0;
                                break;
                        }

                        IPS_SetProperty($this->InstanceID, "ItemID", $item_id);
                        IPS_SetProperty($this->InstanceID, "PlayerID", $player_id);
                        if($player_id >= 0)
                            $this->Send('{"jsonrpc":"2.0","method":"Player.GetItem","params":{"playerid":'.$player_id.', "properties": '.$properties.'},"id":1}');
                    }
                    break;
                case 'Player.OnStop':
                    SetValue($this->GetIDForIdent("Title"), "");
                    SetValue($this->GetIDForIdent("Status"), STOPPED);
                    SetValue($this->GetIDForIdent("PlayerControls"), 1);
                    
                    IPS_SetMediaFile($this->GetIDForIdent("Cover"), "Transparent.png", false);
                    IPS_SetMediaContent($this->GetIDForIdent("Cover"), "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==");
                    IPS_SendMediaEvent($this->GetIDForIdent("Cover"));
                    
                    IPS_SetProperty($this->InstanceID, "ItemID", -1);
                    IPS_SetProperty($this->InstanceID, "PlayerID", 0);
                    break;
                case 'Application.OnVolumeChanged':
                    SetValue($this->GetIDForIdent("Volume"), $JSON->params->data->volume);
                    break;
                case 'Player.OnPropertyChanged':
                    if(isset($JSON->params->data->property)) {
                        if(isset($JSON->params->data->property->repeat)) {
                            $value = $JSON->params->data->property->repeat;
                            if($value == "off")
                                $value = 0;
                            else if($value == "one")
                                $value = 1;
                            else if($value == "all")
                                $value = 2;
                            SetValue($this->GetIDForIdent("RepeatControls"), $value);
                        }
                        // else if(isset($JSON->params->data->property->shuffled)) {
                        //     $value = $JSON->params->data->property->shuffled;
                        //     if($value == 0)
                        //         $value = 0;
                        //     else if($value == 1)
                        //         $value = 1;
                        //     SetValue($this->GetIDForIdent("ShuffleControls"), $value);
                        // }
                    }
                    break;
                case 'System.OnQuit':
                    SetValue($this->GetIDForIdent("Title"), "");
                    SetValue($this->GetIDForIdent("Status"), STOPPED);
                    SetValue($this->GetIDForIdent("ClientStatus"), false);
                    SetValue($this->GetIDForIdent("ClientPower"), false);
                    SetValue($this->GetIDForIdent("PlayerControls"), 1);
                    
                    IPS_SetMediaFile($this->GetIDForIdent("Cover"), "Transparent.png", false);
                    IPS_SetMediaContent($this->GetIDForIdent("Cover"), "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==");
                    IPS_SendMediaEvent($this->GetIDForIdent("Cover"));
                    
                    IPS_SetProperty($this->InstanceID, "ItemID", -1);
                    IPS_SetProperty($this->InstanceID, "PlayerID", -1);
                default:
                    break;
            }
        }
        if(isset($JSON->result)) {
            $result = $JSON->result;

            if(isset($result->item)) {
                $item = $result->item;
                $title = "";
                $player_id = @IPS_GetProperty($this->InstanceID, "PlayerID");
                $item_id = @IPS_GetProperty($this->InstanceID, "ItemID");

                // cover
                if(isset($item->thumbnail) && strlen($item->thumbnail) > 0 && strlen($this->ReadPropertyString("ServerIP")) > 0) {
                    $tmp = explode("url=", urldecode(urldecode($item->thumbnail)));
                    $url = $tmp[1];
                    $url = str_replace("127.0.0.1", $this->ReadPropertyString("ServerIP"), $url);
                    if(strlen(IPS_GetProperty($this->InstanceID, "XPlexToken")) > 0) {
                        $url .= "?X-Plex-Token=".IPS_GetProperty($this->InstanceID, "XPlexToken");
                    }
                    
                    $coverHTML = "<img class='plex_cover' src='".$url."'>";
                    
                    $imageBindata =  base64_encode(file_get_contents($url));
                    IPS_SetMediaContent($this->GetIDForIdent("Cover"), $imageBindata);
                    IPS_SendMediaEvent($this->GetIDForIdent("Cover"));
                } else {
                    IPS_SetMediaFile($this->GetIDForIdent("Cover"), "Transparent.png", false);
                    IPS_SetMediaContent($this->GetIDForIdent("Cover"), "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==");
                    IPS_SendMediaEvent($this->GetIDForIdent("Cover"));
                }

                // titel zusammenbauen
                if(isset($item->artist) && count($item->artist) > 0) {
                    $title = $item->artist[0]." - ";
                }
                if(isset($item->showtitle) && strlen($item->showtitle) > 0) {
                    $title = utf8_decode($item->showtitle." - ");
                }
                if(isset($item->label)) {
                    $title .= $item->label;
                }
                if(isset($item->album)) {
                    $title .= " [".$item->album."]";
                }
                if(isset($item->season) && isset($item->episode)) {
                    if($item->season > -1 && $item->episode > -1)
                        $title .= " [S".$item->season."E".$item->episode."]";
                }

                SetValue($this->GetIDForIdent("Title"), $title);
            }
        }
    }

    // PUBLIC ACCESSIBLE FUNCTIONS
    public function Send($JSONString)
    {
        $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $JSONString)));
    }

    public function SendMessage($title, $message) {
        $command = urlencode('{"jsonrpc":"2.0","method":"GUI.ShowNotification","params":{"title":"'.$title.'","message":"'.$message.'"},"id":1}');
        file_get_contents("http://".IPS_GetProperty($this->InstanceID, "ClientIP").":".IPS_GetProperty($this->InstanceID, "ClientPort")."/jsonrpc?request=".$command);
    }

    public function GetSocketID() {
        return $this->GetParent();
    }

    public function GetPlayerID() {
        return IPS_GetProperty($this->InstanceID, "PlayerID");
    }

    // Play Controls 
    public function Play() { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.PlayPause","params":{"playerid":'.$player_id.'},"id":1}'; 
            $this->Send($command); 
        } 
    } 

    public function Pause() { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.PlayPause","params":{"playerid":'.$player_id.'},"id":1}'; 
            $this->Send($command); 
        } 
    } 

    public function Stop() { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.Stop","params":{"playerid":'.$player_id.'},"id":1}'; 
            $this->Send($command); 
        } 
    } 

    public function Next() { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.GoTo","params":{"playerid":'.$player_id.', "to":"next"},"id":1}'; 
            $this->Send($command); 
        } 
    } 
     
    public function Prev() { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.GoTo","params":{"playerid":'.$player_id.', "to":"previous"},"id":1}'; 
            $this->Send($command); 
        } 
    } 

    // Controls 
    public function Up() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Input.Up","params":{},"id":1}'; 
            $this->Send($command); 
        } 
    } 
    
    public function PgUp() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Input.ExecuteAction","params":{"action":"pageup"},"id":1}'; 
            $this->Send($command); 
        } 
    }
     
    public function Down() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Input.Down","params":{},"id":1}'; 
            $this->Send($command); 
        } 
    } 
    
    public function PgDown() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) {         
            $command = '{"jsonrpc":"2.0","method":"Input.ExecuteAction","params":{"action":"pagedown"},"id":1}'; 
            $this->Send($command);
        } 
    } 
     
    public function Left() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Input.Left","params":{},"id":1}'; 
            $this->Send($command); 
        } 
    } 
     
    public function Right() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Input.Right","params":{},"id":1}'; 
            $this->Send($command); 
        } 
    } 
     
    public function Select() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Input.Select","params":{},"id":1}'; 
            $this->Send($command); 
        } 
    } 
     
    public function Back() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Input.Back","params":{},"id":1}'; 
            $this->Send($command); 
        } 
    } 
     
    // Repeat 
    public function RepeatOff() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.SetRepeat","params":{"playerid":'.$player_id.', "repeat":"off"},"id":1}'; 
            $this->Send($command);
        } 
    } 
     
    public function RepeatActualElement()
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.SetRepeat","params":{"playerid":'.$player_id.', "repeat":"one"},"id":1}'; 
            $this->Send($command); 
        } 
    }
     
    public function RepeatAll() 
    { 
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Player.SetRepeat","params":{"playerid":'.$player_id.', "repeat":"all"},"id":1}';
            $this->Send($command); 
        } 
    } 
     
    // Volume
    public function SetVolume($level) {
        $currentValue = GetValue($this->GetIDForIdent("Volume"));
        $player_id = $this->GetPlayerID(); 
        if($player_id >= 0) { 
            $command = '{"jsonrpc":"2.0","method":"Application.SetVolume","params":{"volume": '.$level.'},"id":1}'; 
            $this->Send($command); 
        }
        else
            SetValue($this->GetIDForIdent("Volume"), $currentValue);
    } 

    // Power
    public function PowerOn() {
        $ip = "";
        $ip_arr = explode(".", gethostbyname(IPS_GetProperty($this->InstanceID, "ClientIP")));
        for ($i=0; $i < count($ip_arr)-1; $i++) { 
            $ip .= $ip_arr[$i].".";
        }
        $ip .= "255";
        
        $mac = IPS_GetProperty($this->InstanceID, "ClientMAC");
        if(strlen($mac) > 0) {
            $this->wake($ip, $mac);
        }
    } 

    public function PowerOff() { 
        $command = '{"jsonrpc":"2.0","method":"System.Shutdown","params":{},"id":1}';
        $this->Send($command); 
    } 

    // SOCKET FUNCTIONS TBD

    // HELPER FUNCTIONS
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 0)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);  
    }
    
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

 
    protected function wake($ip, $mac)
    {
        if(strstr($mac, "-") !== false)
            $addr_byte = explode('-', $mac);
        else if(strstr($mac, ":") !== false)
            $addr_byte = explode(':', $mac); 
        
        $hw_addr = '';
        
        for ($a=0; $a < 6; $a++) $hw_addr .= chr(hexdec($addr_byte[$a]));
        
        $msg = chr(255).chr(255).chr(255).chr(255).chr(255).chr(255);
        
        for ($a = 1; $a <= 16; $a++) $msg .= $hw_addr;
        
        $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($s != false)
        {
            $opt_ret = socket_set_option($s, 1, 6, TRUE);
            $e = socket_sendto($s, $msg, strlen($msg), 0, $ip, 2050);
            socket_close($s);
        }
    }
}

?>
