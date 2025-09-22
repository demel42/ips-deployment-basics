<?php

declare(strict_types=1);

// ********* allgemeine Hilfsfunktionen *********

// Ausgabe einer bool
function bool2str($bval)
{
    if (is_bool($bval)) {
        return $bval ? 'true' : 'false';
    }
    return $bval;
}

// Sekunden in Menschen-lesbares Format umwandeln
function seconds2duration($sec)
{
    $duration = '';
    if ($sec > 3600) {
        $duration .= sprintf('%dh', floor($sec / 3600));
        $sec = $sec % 3600;
    }
    if ($sec > 60) {
        $duration .= sprintf('%dm', floor($sec / 60));
        $sec = $sec % 60;
    }
    if ($sec > 0) {
        $duration .= sprintf('%ds', $sec);
        $sec = floor($sec);
    }

    return $duration;
}

// damit kann man prüfen, ob eine IP-Adresse im eigegen Netz ist
function Util_IpInNet($net, $netmask, $ip)
{
    $bcast = ip2long($net);
    $smask = ip2long($netmask);
    $ipadr = ip2long($ip);
    return (($ipadr & $smask) == (($bcast & $smask) & $smask)) ? true : false;
}

// ********* IPS-Hilfsfunktionen *********

// Ermitteln des Geräte-Typs
function Util_Gerate2Typ($deviceID)
{
    $obj = IPS_GetObject($deviceID);
    $typeName = $obj['ObjectInfo'];
    if ($typeName != '') {
        return $typeName;
    }
    $typIDs = IPS_GetChildrenIDs(GetLocalConfig('Geräte-Typen'));
    foreach ($typIDs as $typID) {
        $chldIDs = IPS_GetChildrenIDs($typID);
        foreach ($chldIDs as $chldID) {
            $obj = IPS_GetObject($chldID);
            if ($obj['ObjectType'] != OBJECTTYPE_LINK) {
                continue;
            }
            $lnk = IPS_GetLink($chldID);
            if ($lnk['TargetID'] == $deviceID) {
                $typeName = IPS_GetName($typID);
                break;
            }
            if ($typeName != '') {
                break;
            }
        }
    }
    return $typeName;
}

// ********* HomeMatic-Hilfsfunktionen *********

function HM_GetInstanceForChannel($instID, $channel)
{
    $addr = IPS_GetProperty($instID, 'Address');
    $addr = preg_replace('/:[0-9]*$/', '', $addr);
    $search = $addr . ':' . $channel;
    $ret = false;
    $instIDs = IPS_GetInstanceListByModuleID('{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}');  // HomeMatic
    foreach ($instIDs as $instID) {
        $a = IPS_GetProperty($instID, 'Address');
        if (IPS_GetProperty($instID, 'Address') == $search) {
            $ret = $instID;
            break;
        }
    }
    return $instID;
}

function HM_PrintTarget($instID)
{
    return $instID . '(' . IPS_GetName(IPS_GetParent($instID)) . '\\' . IPS_GetName($instID) . ')';
}

function HM_PauseWrites()
{
    $n = 5;
    IPS_Sleep($n * 1000);
    return $n;
}

// ********* Schaltaktor (HM-LC-Sw1-FM, HM-LC-Sw2-FM, HM-LC-Sw2PBU-FM, HmIP-BS2, HmIP-BSM, HmIP-FSI16, HmIP-FSM, HmIP-FSM16, HmIP-PCBS, HmIP-PCBS-BAT, HmIP-PS, HmIP-PSM, HmIP-PSM-2)

function HM_SwitchPowerOn($instID)
{
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('STATE', $instID), true);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($instID) . '\\STATE, true) failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_SwitchPowerOff($instID)
{
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('STATE', $instID), false);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($instID) . '\\STATE, false) failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_SwitchPowerToggle($instID)
{
    $state = GetValueBoolean(IPS_GetObjectIDByIdent('STATE', $instID));
    $new_state = !$state;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('STATE', $instID), $new_state);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($instID) . '\\STATE, ' . bool2str($new_state) . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_SwitchPowerStatus($instID)
{
    $state = GetValueBoolean(IPS_GetObjectIDByIdent('STATE', $instID));
    return $state;
}

function HM_SwitchPowerPulse($instID, $delay)
{
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('STATE', $instID), true);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($instID) . '\\STATE, true) failed after ' . $duration . 's, paused ' . $n . 's');
    }
    IPS_Sleep($delay);
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('STATE', $instID), false);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($instID) . '\\STATE, false) failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

// ********* Dimmer (HM-LC-Dim1TPBU-FM, HmIP-BDT, HmIP-FDT)

function HM_Dimmer_InstID4Level($instID)
{
    $type = Util_Gerate2Typ($instID);
    switch ($type) {
        case 'HM-LC-Dim1TPBU-FM':
            $chan = HM_GetInstanceForChannel($instID, '1');
            break;
        default:
            $chan = HM_GetInstanceForChannel($instID, '2');
            break;
    }
    return $chan;
}

function HM_DimmerOn($instID)
{
    $chan = HM_Dimmer_InstID4Level($instID);
    $level = 0.7;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_DimmerOff($instID)
{
    $chan = HM_Dimmer_InstID4Level($instID);
    $level = 0;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_DimmerBrighter($instID)
{
    $chan = HM_Dimmer_InstID4Level($instID);
    $level = GetValueFloat(IPS_GetObjectIDByIdent('LEVEL', $chan));
    $level = round($level, 1) + 0.1;
    if ($level > 1) {
        $level = 1;
    }
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_DimmerDarker($instID)
{
    $chan = HM_Dimmer_InstID4Level($instID);
    $level = GetValueFloat(IPS_GetObjectIDByIdent('LEVEL', $chan));
    $level = round($level, 1) - 0.1;
    if ($level < 0) {
        $level = 0;
    }
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_DimmerSetLevel($instID, $level)
{
    $chan = HM_Dimmer_InstID4Level($instID);
    $level = round($level / 100, 2);
    if ($level < 0) {
        $level = 0;
    }
    if ($level > 1) {
        $level = 1;
    }
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

// ********* Thermostat (HM-CC-RT-DN, HmIP-BWTH, HmIP-eTRV-2, HmIP-eTRV-E, HmIP-WTH-1, HmIP-WTH-2)

// Darstellung des Thermostat-Betriebsmodus
function HM_ThermoMode2Str($mode)
{
    switch ($mode) {
        case '0':
            $mode = 'auto';
            break;
        case '1':
            $mode = 'manu';
            break;
        case '2':
            $mode = 'party';
            break;
        case '3':
            $mode = 'boost';
            break;
        default:
            $mode = '';
            break;
    }
    return $mode;
}

// Setzen des Betriebsmodus
function HM_ThermoSetMode($instID, $mode)
{
    $r = false;
    $type = Util_Gerate2Typ($instID);
    switch ($type) {
        case 'HM-CC-RT-DN':
            $chan1 = HM_GetInstanceForChannel($instID, '1');
            switch ($mode) {
                case 'auto':
                    $time_start = microtime(true);
                    $r = @RequestAction(IPS_GetObjectIDByIdent('AUTO_MODE', $chan1), true);
                    $duration = round(microtime(true) - $time_start, 2);
                    if ($r == false) {
                        echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                        $n = HM_PauseWrites();
                        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\AUTO_MODE, true) failed after ' . $duration . 's, paused ' . $n . 's');
                    }
                    break;
                case 'manu':
                    $f = GetValueFloat(IPS_GetObjectIDByIdent('SET_TEMPERATURE', $chan1));
                    $time_start = microtime(true);
                    $r = @RequestAction(IPS_GetObjectIDByIdent('MANU_MODE', $chan1), $f);
                    $duration = round(microtime(true) - $time_start, 2);
                    if ($r == false) {
                        echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                        $n = HM_PauseWrites();
                        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\MANU_MODE, ' . $f . ') failed after ' . $duration . 's, paused ' . $n . 's');
                    }
                    break;
                case 'boost':
                    $time_start = microtime(true);
                    $r = @RequestAction(IPS_GetObjectIDByIdent('BOOST_MODE', $chan1), true);
                    $duration = round(microtime(true) - $time_start, 2);
                    if ($r == false) {
                        echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                        $n = HM_PauseWrites();
                        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\BOOST_MODE, true) failed after ' . $duration . 's, paused ' . $n . 's');
                    }
                    break;
                default:
                    echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . '): unsupported mode \'' . $mode . '\'' . PHP_EOL;
                    break;
            }
            break;
        case 'HmIP-BWTH':
        case 'HmIP-eTRV-2':
        case 'HmIP-eTRV-E':
        case 'HmIP-WTH-1':
        case 'HmIP-WTH-2':
            $chan1 = HM_GetInstanceForChannel($instID, '1');
            switch ($mode) {
                case 'auto':
                    $time_start = microtime(true);
                    $r = @RequestAction(IPS_GetObjectIDByIdent('SET_POINT_MODE', $chan1), 0);
                    $duration = round(microtime(true) - $time_start, 2);
                    if ($r == false) {
                        echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                        $n = HM_PauseWrites();
                        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\SET_POINT_MODE, 0) failed after ' . $duration . 's, paused ' . $n . 's');
                    }
                    break;
                case 'manu':
                    $time_start = microtime(true);
                    $r = @RequestAction(IPS_GetObjectIDByIdent('SET_POINT_MODE', $chan1), 1);
                    $duration = round(microtime(true) - $time_start, 2);
                    if ($r == false) {
                        echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                        $n = HM_PauseWrites();
                        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\SET_POINT_MODE, 1) failed after ' . $duration . 's, paused ' . $n . 's');
                    }
                    break;
                default:
                    echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . '): unsupported mode \'' . $mode . '\'' . PHP_EOL;
                    break;
            }
            break;
        default:
            echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . '): unsupported type \'' . $type . '\'' . PHP_EOL;
            break;
    }
    return $r;
}

// Ermitteln des Betriebsmodus
function HM_ThermoGetMode($instID)
{
    $type = Util_Gerate2Typ($instID);
    switch ($type) {
        case 'HM-CC-RT-DN':
            $chan1 = HM_GetInstanceForChannel($instID, '1');
            $mode = GetValueInteger(IPS_GetObjectIDByIdent('SET_POINT_MODE', $chan1));
            $mode = HM_ThermoMode2Str($mode);
            break;
        case 'HmIP-BWTH':
        case 'HmIP-eTRV-2':
        case 'HmIP-eTRV-E':
        case 'HmIP-WTH-1':
        case 'HmIP-WTH-2':
            $chan1 = HM_GetInstanceForChannel($instID, '1');
            $mode = GetValueInteger(IPS_GetObjectIDByIdent('SET_POINT_MODE', $chan1));
            $mode = HM_ThermoMode2Str($mode);
            break;
        default:
            echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . '): unsupported type \'' . $type . '\'' . PHP_EOL;
            $mode = '';
            break;
    }
    return $mode;
}

// Temperatur setzen
function HM_ThermoSetTemperature($instID, $value)
{
    $r = false;
    $type = Util_Gerate2Typ($instID);
    switch ($type) {
        case 'HM-CC-RT-DN':
            $chan1 = HM_GetInstanceForChannel($instID, '1');
            $time_start = microtime(true);
            $r = @RequestAction(IPS_GetObjectIDByIdent('MANU_MODE', $chan1), $value);
            $duration = round(microtime(true) - $time_start, 2);
            if ($r == false) {
                echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                $n = HM_PauseWrites();
                IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\MANU_MODE, ' . $value . ') failed after ' . $duration . 's, paused ' . $n . 's');
            }
            break;
        case 'HmIP-BWTH':
        case 'HmIP-eTRV-2':
        case 'HmIP-eTRV-E':
        case 'HmIP-WTH-1':
        case 'HmIP-WTH-2':
            $chan1 = HM_GetInstanceForChannel($instID, '1');
            $time_start = microtime(true);
            $r = @RequestAction(IPS_GetObjectIDByIdent('SET_POINT_TEMPERATURE', $chan1), $value);
            $duration = round(microtime(true) - $time_start, 2);
            if ($r == false) {
                echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                $n = HM_PauseWrites();
                IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\SET_POINT_TEMPERATURE, ' . $value . ') failed after ' . $duration . 's, paused ' . $n . 's');
            }
            $time_start = microtime(true);
            $r = @RequestAction(IPS_GetObjectIDByIdent('SET_POINT_MODE', $chan1), 1);
            $duration = round(microtime(true) - $time_start, 2);
            if ($r == false) {
                echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                $n = HM_PauseWrites();
                IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\SET_POINT_MODE, 1) failed after ' . $duration . 's, paused ' . $n . 's');
            }
            break;
        default:
            echo __FUNCTION__ . '(' . HM_PrintTarget($instID) . '): unsupported type \'' . $type . '\'' . PHP_EOL;
            break;
    }
    return $r;
}

// Sicherstellen, das nur "manu" oder "boost" eingestellt ist
function HM_ThermoAdjustMode($instID)
{
    $mode = HM_ThermoGetMode($instID);
    if ($mode != 'manu' && $mode != 'boost') {
        $new_mode = 'manu';
        HM_ThermoSetMode($instID, $new_mode);
        IPS_LogMessage(__FUNCTION__ . '(' . $_IPS['SELF'] . ')', 'instID=' . $instID . ', mode=' . $mode . ' => ' . $new_mode);
    }
}

// Umschalten zwischen "boost" und "manu"
function HM_ThermoToggleBoost($instID)
{
    $chan1 = HM_GetInstanceForChannel($instID, '1');
    $r = false;
    $type = Util_Gerate2Typ($chan1);
    switch ($type) {
        case 'HM-CC-RT-DN':
            $mode = HM_ThermoGetMode($chan1);
            $new_mode = $mode == 'boost' ? 'manu' : 'boost';
            $r = HM_ThermoSetMode($chan1, $new_mode);
            break;
        case 'HmIP-BWTH':
        case 'HmIP-eTRV-2':
        case 'HmIP-eTRV-E':
        case 'HmIP-WTH-1':
        case 'HmIP-WTH-2':
            $boost = GetValueBoolean(IPS_GetObjectIDByIdent('BOOST_MODE', $chan1));
            $new_boost = !$boost;
            $time_start = microtime(true);
            $r = @RequestAction(IPS_GetObjectIDByIdent('BOOST_MODE', $chan1), $new_boost);
            $duration = round(microtime(true) - $time_start, 2);
            if ($r == false) {
                echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                $n = HM_PauseWrites();
                IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan1) . '\\BOOST_MODE, ' . bool2str($new_boost) . ') failed after ' . $duration . 's, paused ' . $n . 's');
            }
            break;
        default:
            echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . '): unsupported type \'' . $type . '\'' . PHP_EOL;
            break;
    }
    return $r;
}

// Betriebsmodus setzen
function HM_ThermoSetControlMode($instID, $mode)
{
    $chan1 = HM_GetInstanceForChannel($instID, '1');
    $r = false;
    $type = Util_Gerate2Typ($chan1);
    switch ($type) {
        case 'HmIP-BWTH':
        case 'HmIP-eTRV-2':
        case 'HmIP-eTRV-E':
        case 'HmIP-WTH-1':
        case 'HmIP-WTH-2':
            $time_start = microtime(true);
            $r = @HM_WriteValueInteger($chan1, 'CONTROL_MODE', $mode);
            $duration = round(microtime(true) - $time_start, 2);
            if ($r == false) {
                echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                $n = HM_PauseWrites();
                IPS_LogMessage(__FUNCTION__, 'HM_WriteValueInteger(' . HM_PrintTarget($chan1) . '\\CONTROL_MODE, ' . $mode . ') failed after ' . $duration . 's, paused ' . $n . 's');
            }
            break;
        default:
            echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . '): unsupported type \'' . $type . '\'' . PHP_EOL;
            break;
    }
    return $r;
}

// Fenster-Kontakt setzen
function HM_ThermoSetWindowState($instID, $state)
{
    $chan1 = HM_GetInstanceForChannel($instID, '1');
    $r = false;
    $type = Util_Gerate2Typ($chan1);
    switch ($type) {
        case 'HmIP-BWTH':
        case 'HmIP-eTRV-2':
        case 'HmIP-eTRV-E':
        case 'HmIP-WTH-1':
        case 'HmIP-WTH-2':
            $time_start = microtime(true);
            $r = @HM_WriteValueInteger($target, 'WINDOW_STATE', $state);
            $duration = round(microtime(true) - $time_start, 2);
            if ($r == false) {
                echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . ') failed after ' . $duration . 's' . PHP_EOL;
                $n = HM_PauseWrites();
                IPS_LogMessage(__FUNCTION__, 'HM_WriteValueInteger(' . HM_PrintTarget($chan1) . '\\WINDOW_STATE, $state) failed after ' . $duration . 's, paused ' . $n . 's');
            }
            break;
        default:
            echo __FUNCTION__ . '(' . HM_PrintTarget($chan1) . '): unsupported type \'' . $type . '\'' . PHP_EOL;
            break;
    }
    return $r;
}

// ********* Rolladenaktor (HmIP-BROLL, HmIP-BROLL-2, HmIP-FROLL)

function HM_ShutterMoveUp($instID)
{
    $chan4 = HM_GetInstanceForChannel($instID, '4');
    $level = 1;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan4), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan4) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan4) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_ShutterMoveDown($instID)
{
    $chan4 = HM_GetInstanceForChannel($instID, '4');
    $level = 0;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan4), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan4) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan4) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_ShutterStop($instID)
{
    $chan4 = HM_GetInstanceForChannel($instID, '4');
    $time_start = microtime(true);
    $r = @HM_WriteValueBoolean($chan4, 'STOP', true);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan4) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'HM_WriteValueBoolean(' . HM_PrintTarget($chan4) . '\\STOP, true) failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_ShutterMove($instID, $level)
{
    $chan4 = HM_GetInstanceForChannel($instID, '4');
    if ($level < 0) {
        $level = 0;
    } elseif ($level > 100) {
        $level = 100;
    }
    $c_level = $level * 0.01;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan4), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan4) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan4) . '\\LEVEL, ' . $c_level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_ShutterStepUp($instID)
{
    $chan3 = HM_GetInstanceForChannel($instID, '3');
    $chan4 = HM_GetInstanceForChannel($instID, '4');
    $level = GetValueFloat(IPS_GetObjectIDByIdent('LEVEL', $chan3));
    $level = $level >= 0.05 ? $level - 0.05 : 0;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan4), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan4) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan4) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_ShutterStepDown($instID)
{
    $chan3 = HM_GetInstanceForChannel($instID, '3');
    $chan4 = HM_GetInstanceForChannel($instID, '4');
    $level = GetValueFloat(IPS_GetObjectIDByIdent('LEVEL', $chan3));
    $level = $level <= 0.95 ? $level + 0.05 : 1.00;
    $time_start = microtime(true);
    $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan4), $level);
    $duration = round(microtime(true) - $time_start, 2);
    if ($r == false) {
        echo __FUNCTION__ . '(' . HM_PrintTarget($chan4) . ') failed after ' . $duration . 's' . PHP_EOL;
        $n = HM_PauseWrites();
        IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan4) . '\\LEVEL, ' . $level . ') failed after ' . $duration . 's, paused ' . $n . 's');
    }
    return $r;
}

function HM_ShutterSyncLevel($instID)
{
    $chan3 = HM_GetInstanceForChannel($instID, '3');
    $chan4 = HM_GetInstanceForChannel($instID, '4');
    $activity = GetValueInteger(IPS_GetObjectIDByIdent('ACTIVITY_STATE', $chan3));
    for ($i = 0; $activity != 3 /* steht */ && $i < 10; $i++) {
        IPS_Sleep(500);
        $activity = GetValueInteger(IPS_GetObjectIDByIdent('ACTIVITY_STATE', $chan3));
    }
    if ($activity == 3 /* steht */) {
        IPS_Sleep(500);
        $actual_level = GetValueFloat(IPS_GetObjectIDByIdent('LEVEL', $chan3));
        $target_level = GetValueFloat(IPS_GetObjectIDByIdent('LEVEL', $chan4));
        if ($target_level != $actual_level) {
            IPS_LogMessage(__FUNCTION__, 'actual_level=' . $actual_level . ', target_level=' . $target_level);
            $time_start = microtime(true);
            $r = @RequestAction(IPS_GetObjectIDByIdent('LEVEL', $chan4), $actual_level);
            $duration = round(microtime(true) - $time_start, 2);
            if ($r == false) {
                echo __FUNCTION__ . '(' . HM_PrintTarget($chan4) . ') failed after ' . $duration . 's' . PHP_EOL;
                $n = HM_PauseWrites();
                IPS_LogMessage(__FUNCTION__, 'RequestAction(' . HM_PrintTarget($chan4) . '\\LEVEL, ' . $actual_level . ') failed after ' . $duration . 's, paused ' . $n . 's');
            }
        }
    }
}

function HM_ShutterCreateExtraVariables($instID)
{
    $chan4 = HM_GetInstanceForChannel($instID, '4');

    $action = @IPS_GetObjectIDByName('IPS.SetValue', GetLocalConfig('Aktions-Scripte'));

    $varID = @IPS_GetObjectIDByIdent('MINIMUM_LEVEL', $chan4);
    if ($varID == false) {
        $varID = IPS_CreateVariable(VARIABLETYPE_FLOAT);
        IPS_SetName($varID, 'minimale Rolladenhöhe');
        IPS_SetParent($varID, $chan4);
        IPS_SetIdent($varID, 'MINIMUM_LEVEL');
        IPS_SetVariableCustomProfile($varID, 'HM.ShutterPosition.Reversed');
        IPS_SetVariableCustomAction($varID, $action);
    }

    $varID = @IPS_GetObjectIDByIdent('MINIMUM_LEVEL_ACTIVE', $chan4);
    if ($varID == false) {
        $varID = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        IPS_SetName($varID, 'minimale Rolladenhöhe beachten');
        IPS_SetParent($varID, $chan4);
        IPS_SetIdent($varID, 'MINIMUM_LEVEL_ACTIVE');
        IPS_SetVariableCustomProfile($varID, 'Local.JaNein');
        IPS_SetVariableCustomAction($varID, $action);
    }
}

function HM_ShutterGetMinimumLevel($instID)
{
    $chan4 = HM_GetInstanceForChannel($instID, '4');

    $limit = 0;
    $varID = @IPS_GetObjectIDByIdent('MINIMUM_LEVEL_ACTIVE', $chan4);
    if ($varID != false && GetValueBoolean($varID) == true) {
        $varID = @IPS_GetObjectIDByIdent('MINIMUM_LEVEL', $chan4);
        if ($varID != false) {
            $limit = GetValueFloat($varID);
        }
    }
    return $limit;
}

// HmIP-QR dekodieren

function HM_DecodeQr($qr)
{
    $result = ['QR' => $qr];

    if (preg_match('/^SG(\w{24})$/', $qr, $r) && count($r) == 2) {
        $sgtin_qr = $r[1];
        $key_qr = '';
    } elseif (preg_match('/^EQ01SG(\w{24})DLK(\w{32})$/', $qr, $r) && count($r) == 3) {
        $sgtin_qr = $r[1];
        $key_qr = $r[2];
    } else {
        $sgtin_qr = '';
        $key_qr = '';
    }

    if (strlen($sgtin_qr) == 24) {
        for ($c = [], $i = 0; $i < 6; $i++) {
            $c[] = substr($sgtin_qr, $i * 4, 4);
        }
        $result['SGTIN'] = implode('-', $c);
        $result['SERIAL'] = substr($sgtin_qr, 10, 14);
    }

    if (strlen($key_qr) == 32) {
        for ($c = [], $j = 0; $j < 32; $j++) {
            $a = hexdec($key_qr[$j]);
            $i = 0;
            do {
                $v = ($i < count($c) ? $c[$i] : 0) * 16 + $a;
                $c[$i] = $v % 32;
                $a = (int) ($v / 32);
                $i++;
            } while ($a || $i < count($c));
        }
        for ($key = '', $i = 0; $i < count($c); $i++) {
            $key = '0123456789ABCEFGHJKLMNPQRSTUWXYZ'[$c[$i]] . $key;
            if (in_array($i, [5, 10, 15, 20])) {
                $key = '-' . $key;
            }
        }
        $result['KEY'] = $key;
    }

    return $result;
}

// ********* allgemeine Geräte-Hilfsfunktionen *********

function DeviceRequest(int $objID, string $value, string &$msg)
{
    $msg .= ' => ';

    $check_ok = true;
    $action_ok = true;

    $value = strtolower($value);

    $obj = IPS_GetObject($objID);
    if ($obj['ObjectType'] == OBJECTTYPE_VARIABLE) {
        $var = IPS_GetVariable($objID);
        switch ($var['VariableType']) {
            case VARIABLETYPE_BOOLEAN:
                switch ($value) {
                    case 'on':
                        $action_ok = @SetValueBoolean($objID, true);
                        $msg .= 'ein';
                        break;
                    case 'off':
                        $action_ok = @SetValueBoolean($objID, false);
                        $msg .= 'aus';
                        break;
                    default:
                        $msg .= 'Fehler (Wert=' . $value . ')';
                        $check_ok = false;
                        break;
                }
                break;
            case VARIABLETYPE_INTEGER:
                if (is_numeric($value) && $value > 0) {
                    $action_ok = @SetValueInteger($objID, (int) $value);
                    $msg .= $value;
                } else {
                    $msg .= 'Fehler (Wert=' . $value . ')';
                    $check_ok = false;
                }
                break;
            case VARIABLETYPE_FLOAT:
                if (is_numeric($value) && $value > 0) {
                    SetValueFloat($objID, (float) $value);
                    $msg .= $value;
                } else {
                    $msg .= 'Fehler (Wert=' . $value . ')';
                    $check_ok = false;
                }
                break;
            case VARIABLETYPE_STRING:
                $action_ok = @SetValue($value);
                $msg .= '\'' . $value . '\'';
        }
    } else {
        $type = Util_Gerate2Typ($objID);
        if ($type == false) {
            $inst = IPS_GetInstance($objID);
            $type = $inst['ModuleInfo']['ModuleID'];
        }

        switch ($type) {
            case 'HM-LC-Sw1-FM':
            case 'HM-LC-Sw2-FM':
            case 'HM-LC-Sw2PBU-FM':
            case 'HmIP-BS2':
            case 'HmIP-BSM':
            case 'HmIP-FSI16':
            case 'HmIP-FSM':
            case 'HmIP-FSM16':
            case 'HmIP-PCBS':
            case 'HmIP-PCBS-BAT':
            case 'HmIP-PS':
            case 'HmIP-PSM':
            case 'HmIP-PSM-2':
                switch ($value) {
                    case 'on':
                        $action_ok = HM_SwitchPowerOn($objID);
                        $msg .= 'ein';
                        break;
                    case 'off':
                        $action_ok = HM_SwitchPowerOff($objID);
                        $msg .= 'aus';
                        break;
                    default:
                        $msg .= 'Fehler (Wert=' . $value . ')';
                        $check_ok = false;
                        break;
                }
                break;
            case 'HM-LC-Dim1TPBU-FM':
            case 'HmIP-BDT':
            case 'HmIP-FDT':
                switch ($value) {
                    case 'on':
                        HM_DimmerOn($objID);
                        $msg .= 'ein';
                        break;
                    case 'off':
                        HM_DimmerOff($objID);
                        $msg .= 'aus';
                        break;
                    case 'dimup':
                        HM_DimmerBrighter($objID);
                        $msg .= 'heller';
                        break;
                    case 'dimdown':
                        HM_DimmerDarker($objID);
                        $msg .= 'dunkler';
                        break;
                    default:
                        if (is_numeric($value)) {
                            HM_DimmerSetLevel($objID, $value);
                            $msg .= $value . '%';
                        } else {
                            $msg .= 'Fehler (Wert=' . $value . ')';
                            $check_ok = false;
                        }
                        break;
                }
                break;
            case 'HM-CC-RT-DN':
            case 'HmIP-BWTH':
            case 'HmIP-eTRV-2':
            case 'HmIP-eTRV-E':
            case 'HmIP-WTH-1':
            case 'HmIP-WTH-2':
                if (is_numeric($value) && $value > 0) {
                    $action_ok = HM_ThermoSetTemperature($objID, $value);
                    $msg .= $value . '°';
                } else {
                    $msg .= 'Fehler (Wert=' . $value . ')';
                    $check_ok = false;
                }
                break;
            case 'HmIP-BROLL':
            case 'HmIP-BROLL-2':
            case 'HmIP-FROLL':
                switch ($value) {
                    case 'moveup':
                        $action_ok = HM_ShutterMoveUp($objID);
                        $msg .= 'hoch';
                        break;
                    case 'movedown':
                        $action_ok = HM_ShutterMoveDown($objID);
                        $msg .= 'runter';
                        break;
                    case 'stepup':
                        $action_ok = HM_ShutterStepUp($objID);
                        $msg .= 'Schritt hoch';
                        break;
                    case 'stepdown':
                        $action_ok = HM_ShutterStepDown($objID);
                        $msg .= 'Schritt runter';
                        break;
                    case 'stop':
                        $action_ok = HM_ShutterStop($objID);
                        $msg .= 'anhalten';
                        break;
                    default:
                        if (is_numeric($value)) {
                            HM_ShutterMove($objID, $value);
                            $msg .= $value . '%';
                        } else {
                            $msg .= 'Fehler (Wert=' . $value . ')';
                            $check_ok = false;
                        }
                        break;
                }
                break;
            case '{06D589CF-7789-44B1-A0EC-6F51428352E6}': // NetatmoSecurityCamera
                switch ($value) {
                    case 'light.on':
                        NetatmoSecurity_SwitchLight($objID, true);
                        $msg .= 'Licht ein';
                        break;
                    case 'light.off':
                        NetatmoSecurity_SwitchLight($objID, false);
                        $msg .= 'Licht aus';
                        break;
                    case 'camera.on':
                        NetatmoSecurity_SwitchCamera($objID, true);
                        $msg .= 'Kamera ein';
                        break;
                    case 'camera.off':
                        NetatmoSecurity_SwitchCamera($objID, false);
                        $msg .= 'Kamera aus';
                        break;
                }
                break;
            default:
                $msg .= 'Fehler (unbekannter Gerätetyp=' . $type . ')';
                $check_ok = false;
                break;
        }
    }

    if ($action_ok == false) {
        $msg .= ' (fehlgeschlagen)';
    }

    return $check_ok && $action_ok;
}

function GetScheduledActionName($eventID, $actionID)
{
    $r = IPS_GetEvent($eventID);
    if ($r != false) {
        foreach ($r['ScheduleActions'] as $ac) {
            if ($ac['ID'] == $actionID) {
                return $ac['Name'];
            }
        }
    }
    return false;
}

function GetNoticeBase()
{
    return GetLocalConfig('NoticeBase');
}

function ExecuteDeviceRequest($devID, $value)
{
    if (is_int($value) && IPS_ObjectExists($value)) {
        $value = GetValue($value);
    }

    $msg = IPS_GetName(IPS_GetParent($devID)) . '.' . IPS_GetName($devID);
    $r = DeviceRequest($devID, (string) $value, $msg);
    Notice_Log(GetNoticeBase(), $msg, $r ? 'info' : 'warn', []);
}

function ExecuteScheduledAction($devID, $varID, $env)
{
    $msg = IPS_GetName(IPS_GetParent($devID)) . '.' . IPS_GetName($devID);
    $r = DeviceRequest($devID, (string) GetValue($varID), $msg);
    Notice_Log(GetNoticeBase(), $msg, $r ? 'info' : 'warn', []);

    $eventInfo = IPS_GetName(IPS_GetParent($env['EVENT'])) . '\\' . IPS_GetName($env['EVENT']);
    $actionName = GetScheduledActionName($env['EVENT'], $env['ACTION']);
    IPS_LogMessage($env['EVENT'], $eventInfo . ': ' . $actionName . ' => ' . ($r ? 'ok' : 'fail'));
}

function CreateVariable(int $parent, string $ident, string $name, int $varType, string $varProf, int $action, int $pos)
{
    if ($ident == '') {
        $varID = @IPS_GetObjectIDByName($name, $parent);
    } else {
        $varID = @IPS_GetObjectIDByIdent($ident, $parent);
    }
    if ($varID == false) {
        $varID = IPS_CreateVariable($varType);
        IPS_SetName($varID, $name);
        IPS_SetParent($varID, $parent);
        if ($ident != '') {
            IPS_SetIdent($varID, $ident);
        }
        if ($varProf != '') {
            IPS_SetVariableCustomProfile($varID, $varProf);
        }
        if ($action > 0) {
            IPS_SetVariableCustomAction($varID, $action);
        }
        IPS_SetPosition($varID, $pos);
    }
    return $varID;
}
