<?php
/**
 * EventBrite
 *
 * Copyright 2012 by Everett Griffiths <everett@craftsmancoding.com>
 *
 * EventBrite is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * EventBrite is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * eventbrite; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package eventbrite


@param	&eventTpl used to format each event.  Default: 'eventTpl'
@param	&ticketTpl use to format each ticket. Default: 'ticketTpl'
@param	&msg -- message used if there are no events.
 */

if (!isset($eventTpl)) {
	$eventTpl = 'eventTpl';
}
if (!isset($ticketTpl)) {
	$ticketTpl = 'ticketTpl';
}
if (!isset($msg)) {
	$msg = 'No events scheduled.';
}


// load the API Client library
require_once MODX_CORE_PATH.'components/eventbrite/Eventbrite.php'; 

// Initialize the API client
//  Eventbrite API / Application key (REQUIRED)
//   http://www.eventbrite.com/api/key/
//  Eventbrite user_key (OPTIONAL, only needed for reading/writing private user data)
//   http://www.eventbrite.com/userkeyapi

$user_key = $modx->getOption('eventbrite.api_user_key');

if(empty($user_key)) {
	$modx->log(xPDO::LOG_LEVEL_ERROR, 'Missing EventBrite API User Key.');
}

$authentication_tokens = array('app_key'  => 'W2ZB7B7IW7MPHFORQL',
                               'user_key' => $user_key);
                               
$eb_client = new Eventbrite( $authentication_tokens );

// For more information about the features that are available through the Eventbrite API, see http://developer.eventbrite.com/doc/
$events = $eb_client->user_list_events();

/*
$events comes back e.g.

Array
(
    [events] => Array
        (
            [0] => Array
                (
                    [event] => Array
                        (
                            [box_header_text_color] => 005580
                            [link_color] => EE6600
                            [box_background_color] => FFFFFF
                            [box_border_color] => D5D5D3
                            [timezone] => US/Pacific
                            [organizer] => Array
                                (
                                    [url] => http://www.eventbrite.com/org/2322042307
                                    [description] => 
                                    [long_description] => 
                                    [id] => 2322042307
                                    [name] => 
                                )

                            [background_color] => FFFFFF
                            [id] => 3536298163
                            [category] => 
                            [box_header_background_color] => EFEFEF
                            [capacity] => 0
                            [num_attendee_rows] => 0
                            [title] => Tasting the Desert
                            [start_date] => 2012-06-27 13:00:00
                            [status] => Live
                            [description] => <P>Testing events out in the desert.</P>
                            [end_date] => 2012-06-27 16:00:00
                            [tags] => 
                            [text_color] => 005580
                            [title_text_color] => 
                            [tickets] => Array
                                (
                                    [0] => Array
                                        (
                                            [ticket] => Array
                                                (
                                                    [description] => 
                                                    [end_date] => 2012-06-27 12:00:00
                                                    [min] => 0
                                                    [max] => 0
                                                    [price] => 0.00
                                                    [quantity_sold] => 0
                                                    [visible] => true
                                                    [currency] => USD
                                                    [quantity_available] => 1
                                                    [type] => 0
                                                    [id] => 14000371
                                                    [name] => Admit One
                                                )

                                        )

                                )

                            [created] => 2012-05-18 12:36:31
                            [url] => http://www.eventbrite.com/event/3536298163
                            [box_text_color] => 000000
                            [privacy] => Public
                            [venue] => Array
                                (
                                    [city] => Las Vegas
                                    [name] => The Beat
                                    [country] => United States
                                    [region] => NV
                                    [longitude] => -115.172816
                                    [postal_code] => 
                                    [address_2] => 
                                    [address] => 
                                    [latitude] => 36.114646
                                    [country_code] => US
                                    [id] => 2004637
                                    [Lat-Long] => 36.114646 / -115.172816
                                )

                            [modified] => 2012-05-18 12:38:17
                            [repeats] => no
                        )

                )

        )

)
*/

// die(print_r($events, true));

if(empty($events)) {
	return 'No events scheduled.';
}

$output = '';
foreach($events['events'] as $event) {
	//die(print_r($event, true));
	$e = $event['event'];  // compensate for weird structure
	//die(print_r($e, true));
	$tickets = '';
	
	if (is_array($e['tickets'])) {
		foreach ($e['tickets'] as $ticket) {
			$t = $ticket['ticket'];
			$tickets .= $modx->getChunk($ticketTpl, $t);		
		}
	}
	$e['tickets'] = $tickets; // overwrite
	foreach ($e['organizer'] as $ok => $ov) {
		$e['organizer.'.$ok] = $ov;
	}
	unset($e['organizer']);
	foreach ($e['venue'] as $vk => $vv) {
		$e['venue.'.$vk] = $vv;
	}
	unset($e['venue']);
	
	$placeholders = array_keys($e);
	foreach ($placeholders as &$p) {
		$p = '&#91;&#91;+'.$p.'&#93;&#93;';
	}	
	$e['help'] = 'Available placeholders: ' . implode(', ', $placeholders);

	$output .= $modx->getChunk($eventTpl, $e);
}

return $output;

/*EOF*/