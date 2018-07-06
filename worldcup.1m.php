#!/usr/bin/php

<?php

/**
 * worldcup - BitBar WorldCup 2018 scores
 *
 * PHP version 7
 *
 * @author   Daniel Goldsmith <dgold@ascraeus.org>
 * @license  https://opensource.org/licenses/FPL-1.0.0 0BSD
 * @link     https://github.com/dg01d/bitbar-worldcup
 * @category Utility
 * @version  2.2
 * <bitbar.title>World Cup 2018</bitbar.title>
 * <bitbar.version>v2.2</bitbar.version>
 * <bitbar.author>Daniel Goldsmith, Matt Sephton</bitbar.author>
 * <bitbar.author.github>dg01d</bitbar.author.github>
 * <bitbar.desc>Shows current and daily scores from the 2018 World Cup. Needs Steve Edson's bitbar-php: https://github.com/SteveEdson/bitbar-php </bitbar.desc>
 * <bitbar.image>https://raw.githubusercontent.com/dg01d/bitbar-worldcup/master/bitbar-worldcup.png</bitbar.image>
 * <bitbar.dependencies>php,bitbar-php</bitbar.dependencies>
 * <bitbar.abouturl>https://github.com/dg01d/bitbar-worldcup</bitbar.abouturl>
 * Instructions: Install bitbar-php following the instructions on that project's github page.
 * Uses the wonderful World Cup API provided by http://worldcup.sfg.io
 */

require ".bitbar/vendor/autoload.php";

use SteveEdson\BitBar;

function array_msort($array, $cols)
{
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
    }
    $eval = substr($eval,0,-1).');';
    eval($eval);
    $ret = array();
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k,1);
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    return $ret;
}


$flagsrc = '{"PAN":"🇵🇦","TUN":"🇹🇳","ENG":"🏴󠁧󠁢󠁥󠁮󠁧󠁿","POL":"🇵🇱","JPN":"🇯🇵","COL":"🇨🇴","SEN":"🇸🇳","ARG":"🇦🇷","ISL":"🇮🇸","PER":"🇵🇪","DEN":"🇩🇰","CRO":"🇭🇷","NGA":"🇳🇬","RUS":"🇷🇺","KSA":"🇸🇦","EGY":"🇪🇬","URU":"🇺🇾","POR":"🇵🇹","ESP":"🇪🇸","MAR":"🇲🇦","IRN":"🇮🇷","FRA":"🇫🇷","AUS":"🇦🇺","BRA":"🇧🇷","SUI":"🇨🇭","CRC":"🇨🇷","SRB":"🇷🇸","GER":"🇩🇪","MEX":"🇲🇽","SWE":"🇸🇪","KOR":"🇰🇷","BEL":"🇧🇪"}';

$flags = json_decode($flagsrc, true);

// Create BitBar formatter
$bb = new BitBar();

$json = @file_get_contents("http://worldcup.sfg.io/matches/current");
if($json === FALSE) exit;
$data = json_decode($json, true);

if (!empty($data)) {
    $cnt = count($data);
    for ($n = 0; $n < $cnt; $n++) {
        $status = $data[$n]['status'];
        $homeTeam = $data[$n]['home_team']['code'];
        $homeTeamFlag= $flags[$homeTeam];
        $homeTeamScore = $data[$n]['home_team']['goals'];
        $awayTeam = $data[$n]['away_team']['code'];
        $awayTeamFlag = $flags[$awayTeam];
        $awayTeamScore = $data[$n]['away_team']['goals'];
        if ($status == 'in progress') $scoreLine = "$scoreLine$homeTeam $homeTeamScore"."-"."$awayTeamScore $awayTeam | dropdown=false\n";
    }
} else {
    $scoreLine = ":soccer:";    // :soccer:
};

$line = $bb->newLine();
$line
    ->setText($scoreLine)
    ->show();

$todayJson = file_get_contents("http://worldcup.sfg.io/matches/today");
$todayData = json_decode($todayJson, true);

if (!empty($todayData)) {
    $cnt = count($todayData);
    for ($n = 0; $n < $cnt; $n++) {
        $team1 = $todayData[$n]['home_team']['country'];
        $team1code =  $todayData[$n]['home_team']['code'];
        $team1flag = $flags[$team1code];
        $team1s = $todayData[$n]['home_team']['goals'];
        $team2 = $todayData[$n]['away_team']['country'];
        $team2code =  $todayData[$n]['away_team']['code'];
        $team2flag = $flags[$team2code];
        $team2s = $todayData[$n]['away_team']['goals'];
        $scores = "$team1code $team1s"."-"."$team2s $team2code | ansi=true font=\"SF Mono\"";
        $match = "https://www.fifa.com/worldcup/matches/match/" . $todayData[$n]['fifa_id'] . "/#match-summary";
        if (($todayData[$n]['status']) == "in progress") {
            $scores .= " | ansi=true font=\"SF Mono\"  href=$match\n";
        }
        if (($todayData[$n]['status'] == "completed") || ($todayData[$n]['status'] == "in progress")) {
            $line = $bb->newLine();

            $arrayEvents = array_merge($todayData[$n]['home_team_events'], $todayData[$n]['away_team_events']);
            $arraySortEvents = array_msort($arrayEvents, array('id'=>SORT_ASC));
            foreach ($arraySortEvents as $val) {
                if (in_array($val['type_of_event'], array('goal', "goal-own", "goal-penalty"))) {
                    $scores .= "\n";
                    $scores .= $val['player'] . " " . $val['time'];
                }
                if ($val['type_of_event'] == "goal-penalty") {
                    $scores .= " (P)";
                }
                if ($val['type_of_event'] == "goal-own") {
                    $scores .= " (OG)";
                }
                if (in_array($val['type_of_event'], array('red-card', "yellow-card"))) {
                    $scores .= "\n";
                    $scores .= $val['player'] . " " . $val['time'];
                }
                if ($val['type_of_event'] == "yellow-card") {
                    $scores .= " \033[1;33m◼";
                }
                if ($val['type_of_event'] == "red-card") {
                    $scores .= " \033[1;31m◼";
                }
                $scores .= " | size=11";
            }
            if (($todayData[$n]['status']) == "completed") {
                $time = $todayData[$n]['time'];
                $scores .= "\n FT";
            }
            if (($todayData[$n]['status']) == "in progress") {
                $time = $todayData[$n]['time'];
                $scores .= "\n :soccer: $time";
            }
            $scores .= " | size=11";
            $comGame = $line
                ->setText($scores)
                ->setDropdown(true);
            $comGame->show();
        } else {
            if (($todayData[$n]['status']) == "future") {
                $datetime = $todayData[$n]['datetime'];
                $now = gmdate("Y-m-j\TH:i:s\Z", time() + 3600*($timezone+date("I")));
                $until = dateDifference($datetime, $now);
                $scores .= "|font=\"SF Mono\"\n in $until | size=10";
            }

            $line = $bb->newLine();
            $line
                ->setText($scores)
                ->setDropdown(true)
                ->show();
        }
    }
}

function dateDifference($date_1 , $date_2 , $differenceFormat = '%H:%I' ) {
    $datetime1 = date_create($date_1);
    $datetime2 = date_create($date_2);
    
    $interval = date_diff($datetime1, $datetime2);
    
    return $interval->format($differenceFormat);  
}
