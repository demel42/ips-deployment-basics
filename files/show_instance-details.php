<?php

declare(strict_types=1);

require_once IPS_GetScriptFile(GetLocalConfig('GLOBAL_HELPER'));

function InstanceInfo(int $instID)
{
    $obj = IPS_GetObject($instID);
    // echo 'obj='.print_r($obj,true) . PHP_EOL;

    $inst = IPS_GetInstance($instID);
    // echo 'inst='.print_r($inst,true) . PHP_EOL;

    $modulID = $inst['ModuleInfo']['ModuleID'];
    $mod = IPS_GetModule($modulID);
    // echo 'mod='.print_r($mod, true) . PHP_EOL;

    $libID = $mod['LibraryID'];
    $lib = IPS_GetLibrary($libID);
    // echo 'lib='.print_r($lib, true) . PHP_EOL;

    $s = '';

    $s .= 'Modul "' . $mod['ModuleName'] . '"' . PHP_EOL;
    $s .= '  GUID   : ' . $mod['ModuleID'] . PHP_EOL;
    /*
        $s .= '  Communication-GUID\'s' . PHP_EOL;
        $r = '';
        foreach ($mod['Implemented'] as $guid) {
            $r .= ($r != '' ? ', ' : '') . $guid;
        }
        $s .= '    self implementiert : ' . $r . PHP_EOL;
        $r = '';
        foreach ($mod['ParentRequirements'] as $guid) {
            $r .= ($r != '' ? ', ' : '') . $guid;
        }
        $s .= '    parent requirements: ' . $r . PHP_EOL;
        $r = '';
        foreach ($mod['ChildRequirements'] as $guid) {
            $r .= ($r != '' ? ', ' : '') . $guid;
        }
        $s .= '    child requirements : ' . $r . PHP_EOL;
        $s .= PHP_EOL;
     */
    $s .= 'Library "' . $lib['Name'] . '"' . PHP_EOL;
    $s .= '  GUID   : ' . $lib['LibraryID'] . PHP_EOL;
    $s .= '  Version: ' . $lib['Version'] . PHP_EOL;
    if ($lib['Build'] > 0) {
        $s .= '  Build  : ' . $lib['Build'] . PHP_EOL;
    }
    $ts = $lib['Date'];
    $d = $ts > 0 ? date('d.m.Y H:i:s', $ts) : '';
    $s .= '  Date   : ' . $d . PHP_EOL;

    $src = '';
    $scID = GetLocalConfig('Store Control');
    $scList = SC_GetModuleInfoList($scID);
    foreach ($scList as $sc) {
        if ($sc['LibraryID'] == $lib['LibraryID']) {
            $src = ($src != '' ? ' + ' : '') . 'ModuleStore';
            switch ($sc['Channel']) {
                case 1:
                    $src .= '/Beta';
                    break;
                case 2:
                    $src .= '/Testing';
                    break;
                default:
                    break;
            }
            break;
        }
    }
    $mcID = GetLocalConfig('Module Control');
    $mcList = MC_GetModuleList($mcID);
    foreach ($mcList as $mc) {
        $g = MC_GetModule($mcID, $mc);
        if ($g['LibraryID'] == $lib['LibraryID']) {
            $r = MC_GetModuleRepositoryInfo($mcID, $mc);
            $url = $r['ModuleURL'];
            if (preg_match('/^([^:]*):\/\/[^@]*@(.*)$/', $url, $p)) {
                $url = $p[1] . '://' . $p[2];
            }
            $src = ($src != '' ? ' + ' : '') . $url;
            $branch = $r['ModuleBranch'];
            switch ($branch) {
                case 'master':
                case 'main':
                    break;
                default:
                    $src .= '/' . $branch;
                    break;
            }
            break;
        }
    }
    $s .= '  Source : ' . $src . PHP_EOL;

    return $s;
}

// echo InstanceInfo(xxxxx) . PHP_EOL;
