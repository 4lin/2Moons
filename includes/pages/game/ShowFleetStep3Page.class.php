<?php

/**
 *  2Moons
 *  Copyright (C) 2012 Jan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package 2Moons
 * @author Jan <info@2moons.cc>
 * @copyright 2006 Perberos <ugamela@perberos.com.ar> (UGamela)
 * @copyright 2008 Chlorel (XNova)
 * @copyright 2009 Lucky (XGProyecto)
 * @copyright 2012 Jan <info@2moons.cc> (2Moons)
 * @license http://www.gnu.org/licenses/gpl.html GNU GPLv3 License
 * @version 1.7.0 (2012-05-31)
 * @info $Id$
 * @link http://code.google.com/p/2moons/
 */

class ShowFleetStep3Page extends AbstractPage
{
	public static $requireModule = MODULE_FLEET_TABLE;

	function __construct() 
	{
		parent::__construct();
	}
	
	public function show()
	{
		global $USER, $PLANET, $resource, $uniConfig, $gameConfig, $LNG, $UNI;
			
		if (IsVacationMode($USER)) {
			FleetUtil::GotoFleetPage(0);
		}
		
		$targetMission 			= HTTP::_GP('mission', 3);
		$TransportMetal			= max(0, round(HTTP::_GP('metal', 0.0)));
		$TransportCrystal		= max(0, round(HTTP::_GP('crystal', 0.0)));
		$TransportDeuterium		= max(0, round(HTTP::_GP('deuterium', 0.0)));
		$stayTime 				= HTTP::_GP('staytime', 0);
		$token					= HTTP::_GP('token', '');
		
		if (!isset($_SESSION['fleet'][$token])) {
			FleetUtil::GotoFleetPage();
		}
			
		if ($_SESSION['fleet'][$token]['time'] < TIMESTAMP - 600) {
			unset($_SESSION['fleet'][$token]);
			FleetUtil::GotoFleetPage();
		}
		
		$maxFleetSpeed	= $_SESSION['fleet'][$token]['speed'];
		$distance		= $_SESSION['fleet'][$token]['distance'];
		$targetGalaxy	= $_SESSION['fleet'][$token]['targetGalaxy'];
		$targetSystem	= $_SESSION['fleet'][$token]['targetSystem'];
		$targetPlanet	= $_SESSION['fleet'][$token]['targetPlanet'];
		$targetType		= $_SESSION['fleet'][$token]['targetType'];
		$fleetGroup		= $_SESSION['fleet'][$token]['fleetGroup'];
		$fleetArray  	= $_SESSION['fleet'][$token]['fleet'];
		$fleetStorage	= $_SESSION['fleet'][$token]['fleetRoom'];
		$fleetSpeed		= $_SESSION['fleet'][$token]['fleetSpeed'];
		unset($_SESSION['fleet'][$token]);
			
		if ($PLANET['galaxy'] == $targetGalaxy && $PLANET['system'] == $targetSystem && $PLANET['planet'] == $targetPlanet && $PLANET['planet_type'] == $targetType)
		{
			$this->printMessage($LNG['fl_error_same_planet']);
		}

		if ($targetGalaxy < 1 || $targetGalaxy > $uniConfig['planetMaxGalaxy'] || 
			$targetSystem < 1 || $targetSystem > $uniConfig['planetMaxSystem'] || 
			$targetPlanet < 1 || $targetPlanet > $uniConfig['planetMaxPosition'] + 1 ||
			($targetType !== 1 && $targetType !== 2 && $targetType !== 3)
		) {
			$this->printMessage($LNG['fl_invalid_target']);
		}

		if ($targetMission == 3 && $TransportMetal + $TransportCrystal + $TransportDeuterium < 1)
		{
			$this->printMessage($LNG['fl_no_noresource']);
		}
		
		$ActualFleets		= FleetUtil::GetCurrentFleets($USER['id']);
		
		if (FleetUtil::GetMaxFleetSlots($USER) <= $ActualFleets)
		{
			$this->printMessage($LNG['fl_no_slots']);
		}
		
		$ACSTime = 0;
		
		if(!empty($fleetGroup) && $targetMission == 2)
		{
			$ACSTime = $GLOBALS['DATABASE']->countquery("SELECT ankunft
			FROM ".USERS_ACS." 
			INNER JOIN ".AKS." ON id = acsID
			WHERE acsID = ".$fleetGroup."
			AND ".$CONF['max_fleets_per_acs']." > (SELECT COUNT(*) FROM ".FLEETS." WHERE fleet_group = ".$fleetGroup.");");
			
			if (empty($ACSTime)) {
				$fleetGroup	= 0;
				$targetMission	= 1;
			}
		}
				
		$ActualFleets 		= FleetUtil::GetCurrentFleets($USER['id']);
		
		$targetPlanetData  	= $GLOBALS['DATABASE']->getFirstRow("SELECT id, id_owner, der_metal, der_crystal, destruyed, ally_deposit FROM ".PLANETS." WHERE universe = ".$UNI." AND galaxy = ".$targetGalaxy." AND system = ".$targetSystem." AND planet = ".$targetPlanet." AND planet_type = '".($targetType == 2 ? 1 : $targetType)."';");

		if ($targetMission == 15 || $targetMission == 7)
		{
			$targetPlanetData	= array('id' => 0, 'id_owner' => 0, 'planettype' => 1);
		}
		else
		{
			if ($targetPlanetData["destruyed"] != 0)
			{
				$this->printMessage($LNG['fl_no_target']);
			}
				
			if (!isset($targetPlanetData))
			{
				$this->printMessage($LNG['fl_no_target']);
			}
		}
		
		foreach ($fleetArray as $Ship => $Count)
		{
			if ($Count > $PLANET[$GLOBALS['VARS']['ELEMENT'][$Ship]['name']])
			{
				$this->printMessage($LNG['fl_not_all_ship_avalible']);
			}
		}
		
		if ($targetMission == 11)
		{
			$activeExpedition	= FleetUtil::GetCurrentFleets($USER['id'], 11);
			$maxExpedition		= FleetUtil::getDMMissionLimit($USER);

			if ($activeExpedition >= $maxExpedition) {
				$this->printMessage($LNG['fl_no_expedition_slot']);
			}
		}
		elseif ($targetMission == 15)
		{		
			$activeExpedition	= FleetUtil::GetCurrentFleets($USER['id'], 15);
			$maxExpedition		= FleetUtil::getExpeditionLimit($USER);
			
			if ($activeExpedition >= $maxExpedition) {
				$this->printMessage($LNG['fl_no_expedition_slot']);
			}
		}

		$usedPlanet	= isset($targetPlanetData['id_owner']);
		$myPlanet	= $usedPlanet && $targetPlanetData['id_owner'] == $USER['id'];
		
		if($targetMission == 7 || $targetMission == 15) {
			$targetPlayerData	= array(
				'id'				=> 0,
				'onlinetime'		=> TIMESTAMP,
				'ally_id'			=> 0,
				'urlaubs_modus'		=> 0,
				'authattack'		=> 0,
				'total_points'		=> 0,
			);
		} elseif($myPlanet) {
			$targetPlayerData	= $USER;
		} elseif(!empty($targetPlanetData['id_owner'])) {
			$targetPlayerData	= $GLOBALS['DATABASE']->getFirstRow("SELECT 
			user.id, user.onlinetime, user.ally_id, user.urlaubs_modus, user.banaday, user.authattack, 
			stat.total_points
			FROM ".USERS." as user 
			LEFT JOIN ".STATPOINTS." as stat ON stat.id_owner = user.id AND stat.stat_type = '1' 
			WHERE user.id = ".$targetPlanetData['id_owner'].";");
		} else {
			$this->printMessage($LNG['fl_empty_target']);
		}
		
		$MisInfo		     	= array();		
		$MisInfo['galaxy']     	= $targetGalaxy;		
		$MisInfo['system'] 	  	= $targetSystem;	
		$MisInfo['planet'] 	  	= $targetPlanet;		
		$MisInfo['planettype'] 	= $targetType;	
		$MisInfo['IsAKS']		= $fleetGroup;
		$MisInfo['Ship'] 		= $fleetArray;		
		
		$avalibleMissions		= FleetUtil::GetFleetMissions($USER, $MisInfo, $targetPlanetData);
		
		if (!in_array($targetMission, $avalibleMissions['MissionSelector'])) {
			$this->printMessage($LNG['fl_invalid_mission']);
		}
		
		if ($targetMission == 7)
		{
			if (isset($targetPlanetData))
			{
				$this->printMessage($LNG['fl_target_exists']);
			}
			
			if ($targetType != 1)
			{
				$this->printMessage($LNG['fl_only_planets_colonizable']);
			}
			
			$techLevel	= PlayerUtil::allowPlanetPosition($USER, $targetPlanet);
			
			if($techLevel > $USER[$GLOBALS['VARS']['ELEMENT'][124]['name']])
			{
				$this->printMessage(sprintf($LNG['fl_tech_for_position_required'], $LNG['tech'][124], $techLevel));
			}
		}
		
		if ($targetMission != 8 && IsVacationMode($targetPlayerData))
		{
			$this->printMessage($LNG['fl_in_vacation_player']);
		}
		
		if($targetMission == 1 || $targetMission == 2 || $targetMission == 9)
		{
			if(FleetUtil::CheckBash($targetPlanetData['id']))
			{
				$this->printMessage($LNG['fl_bash_protection']);
			}
		}
		
		if($targetMission == 1 || $targetMission == 2 || $targetMission == 5 || $targetMission == 6 || $targetMission == 9)
		{
			if($gameConfig['adminProtection'] == 1 && $usedPlanet['authattack'] > $USER['authlevel'])
			{
				$this->printMessage($LNG['fl_admin_attack']);
			}
		
			$IsNoobProtec	= CheckNoobProtec($USER, $targetPlayerData, $targetPlayerData);
			
			if ($IsNoobProtec['NoobPlayer'])
			{
				$this->printMessage($LNG['fl_player_is_noob']);
			}
			
			if ($IsNoobProtec['StrongPlayer'])
			{
				$this->printMessage($LNG['fl_player_is_strong']);
			}
		}

		if ($targetMission == 5) {
			if ($targetPlanetData['ally_deposit'] < 1)
			{
				$this->printMessage($LNG['fl_no_hold_depot']);
			}
					
			if($targetPlayerData['ally_id'] != $USER['ally_id']) {
				$buddy	= $GLOBALS['DATABASE']->countquery("
				SELECT COUNT(*) FROM ".BUDDY." 
				WHERE id NOT IN (SELECT id FROM ".BUDDY_REQUEST." WHERE ".BUDDY_REQUEST.".id = ".BUDDY.".id) AND 
				(owner = ".$targetPlayerData['id']." AND sender = ".$USER['id'].") OR
				(owner = ".$USER['id']." AND sender = ".$targetPlayerData['id'].");");
				
				if($buddy == 0)
				{
					$this->printMessage($LNG['fl_no_same_alliance']);
				}
			}
		}

		$fleetMaxSpeed 	= FleetUtil::GetFleetMaxSpeed($fleetArray, $USER);
		$SpeedFactor    = FleetUtil::GetGameSpeedFactor();
		$duration      	= FleetUtil::GetMissionDuration($fleetSpeed, $fleetMaxSpeed, $distance, $SpeedFactor, $USER);
		$consumption   	= FleetUtil::GetFleetConsumption($fleetArray, $duration, $distance, $fleetMaxSpeed, $USER, $SpeedFactor);
		$duration		= $duration * (1 - $USER['factor']['FlyTime']);
		
		$StayDuration    = 0;
		
		if($targetMission == 5 || $targetMission == 11 || $targetMission == 15)
		{
			if(!isset($avalibleMissions['StayBlock'][$stayTime])) {
				FleetUtil::GotoFleetPage(2);
			}
			
			$StayDuration    = round($avalibleMissions['StayBlock'][$stayTime] * 3600, 0);
		}
		
		$fleetStorage		-= $consumption;
				
		$fleetRessource	= array(
			901	=> min($TransportMetal, floor($PLANET[$GLOBALS['VARS']['ELEMENT'][901]['name']])),
			902	=> min($TransportCrystal, floor($PLANET[$GLOBALS['VARS']['ELEMENT'][902]['name']])),
			903	=> min($TransportDeuterium, floor($PLANET[$GLOBALS['VARS']['ELEMENT'][903]['name']] - $consumption)),
		);
		
		$StorageNeeded		= array_sum($fleetRessource);
	
		if ($PLANET[$GLOBALS['VARS']['ELEMENT'][903]['name']] < $consumption)
		{
			$this->printMessage($LNG['fl_not_enough_deuterium']);
		}
		
		if ($StorageNeeded > $fleetStorage)
		{
			$this->printMessage($LNG['fl_not_enough_space']);
		}
		
		$PLANET[$GLOBALS['VARS']['ELEMENT'][901]['name']]	-= $fleetRessource[901];
		$PLANET[$GLOBALS['VARS']['ELEMENT'][902]['name']]	-= $fleetRessource[902];
		$PLANET[$GLOBALS['VARS']['ELEMENT'][903]['name']]	-= $fleetRessource[903] + $consumption;

		if(connection_aborted())
			exit;

		$fleetStartTime		= $duration + TIMESTAMP;
		$fleetStayTime		= $fleetStartTime + $StayDuration;
		$fleetEndTime		= $fleetStayTime + $duration;
		$timeDifference		= max(0, $fleetStartTime - $ACSTime);
		
		if($fleetGroup != 0 && $timeDifference != 0) {
			FleetUtil::setACSTime($timeDifference, $fleetGroup);
		}
		
		FleetUtil::sendFleet($fleetArray, $targetMission, $USER['id'], $PLANET['id'], $PLANET['galaxy'], $PLANET['system'], $PLANET['planet'], $PLANET['planet_type'], $targetPlanetData['id_owner'], $targetPlanetData['id'], $targetGalaxy, $targetSystem, $targetPlanet, $targetType, $fleetRessource, $fleetStartTime, $fleetStayTime, $fleetEndTime, $fleetGroup);
		
		foreach ($fleetArray as $Ship => $Count)
		{
			$fleetList[$LNG['tech'][$Ship]]	= $Count;
		}
	
		$this->loadscript('flotten.js');
		$this->gotoside('game.php?page=fleetTable');
		$this->assign(array(
			'targetMission'		=> $targetMission,
			'distance'			=> $distance,
			'consumption'		=> $consumption,
			'from'				=> $PLANET['galaxy'] .":". $PLANET['system']. ":". $PLANET['planet'],
			'destination'		=> $targetGalaxy .":". $targetSystem .":". $targetPlanet,
			'fleetStartTime'	=> _date($LNG['php_tdformat'], $fleetStartTime, $USER['timezone']),
			'fleetEndTime'		=> _date($LNG['php_tdformat'], $fleetEndTime, $USER['timezone']),
			'MaxFleetSpeed'		=> $fleetMaxSpeed,
			'FleetList'			=> $fleetArray,
		));
		
		$this->render('page.fleetStep3.default.tpl');
	}
}
