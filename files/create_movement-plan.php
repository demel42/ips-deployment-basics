<?php

declare(strict_types=1);

require_once IPS_GetScriptFile(GetLocalConfig('GLOBAL_HELPER'));

function Make_Bewegungsplan($destID)
{
    $evnID = IPS_CreateEvent(EVENTTYPE_SCHEDULE);
    IPS_SetParent($evnID, $destID);
    IPS_SetName($evnID, 'Bewegungsplan');
    IPS_SetIcon($evnID, 'Database');
    IPS_SetHidden($evnID, true);

    $acName = ['Rolladen schliessen', 'Rolladen öffnen'];
    $varName = ['Position geschlossen', 'Position geöffnet'];
    $varValue = [0.0 /* Unten */, 1.0 /* Oben */];
    $evnColor = [0x0541AF, 0xFAFF5A];

    $varActionID = IPS_GetObjectIDByName('IPS.SetValue', GetLocalConfig('Aktions-Scripte'));

    for ($i = 0; $i < count($acName); $i++) {
        $varID = IPS_CreateVariable(VARIABLETYPE_FLOAT);
        IPS_SetParent($varID, $evnID);
        IPS_SetName($varID, $varName[$i]);
        SetValueFloat($varID, $varValue[$i]);
        IPS_SetVariableCustomProfile($varID, 'HM.ShutterPosition.Reversed');
        IPS_SetVariableCustomAction($varID, $varActionID);
        IPS_SetPosition($varID, $i + 1);
        $actionID = '{E9CC54F3-19B5-E3E2-B6BC-A02065C1FC85}';
        $actionParams = [
            'FAILURE_LOG' => 'Fehler: {NAME} ({LOCATION2}) kann nicht gesetzt werden',
            'NOTICE_BASE' => GetNoticeBase(),
            'SUCCEED_LOG' => '{NAME} ({LOCATION2}) => {VALUE}',
            'VARIABLE'    => $varID,
        ];
        IPS_SetEventScheduleActionEx($evnID, $i + 1, $acName[$i], $evnColor[$i], $actionID, $actionParams);
    }

    IPS_SetEventScheduleGroup($evnID, 0, 0b1111111);
    IPS_SetEventScheduleGroupPoint($evnID, 0, 0, 0, 0, 0, 1);
}

// Make_Bewegungsplan(<varID von "Soll-Rolladenhöhe");
