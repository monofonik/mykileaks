<?php

namespace MykiLeaks;

class ZoneTwo
{
	/**
	 * Returns the line group name if stations are connected by zone two areas, false otherwise
	 */
	public static function areConnected($statA, $statB)
	{
		$statA = preg_replace("/\sStation$/", "", $statA);
		$statB = preg_replace("/\sStation$/", "", $statB);

		foreach (self::$stations as $group => $stations) {
			if (in_array($statA, $stations)) {
				if (in_array($statB, $stations))
					return $group;
				else
					return false;
			}
		}
		return false;
	}

	private static $stations = [
		"FRANKSTON / STONY POINT"=>[
			"Ormond",
			"McKinnon",
			"Bentleigh",
			"Patterson",
			"Moorabbin",
			"Highett",
			"Southland",
			"Cheltenham",
			"Mentone",
			"Parkdale",
			"Mordialloc",
			"Aspendale",
			"Edithvale",
			"Chelsea",
			"Bonbeach",
			"Carrum",
			"Seaford",
			"Kananook",
			"Frankston",
			"Leawarra",
			"Langwarrin",
			"Baxter",
			"Somerville",
			"Tyabb",
			"Hastings",
			"Bittern",
			"Morradoo",
			"Crib Point",
			"Stony Point",
		],
		"CRANBOURNE / PAK"=>[
			"Hughesdale",
			"Oakleigh",
			"Huntingdale",
			"Clayton",
			"Westall",
			"Springvale",
			"Sandown Park",
			"Noble Park",
			"Yarraman",
			"Dandenong",
			"General Motors",
			"Hallam",
			"Narre Warren",
			"Berwick",
			"Beaconsfield",
			"Officer",
			"Cardinia Road",
			"Pakenham",
			"Lynbrook",
			"Merinda Park",
			"Cranbourne",
		],
		"SANDRINGHAM"=>[
			"North Brighton",
			"Middle Brighton",
			"Brighton Beach",
			"Hampton",
			"Sandringham",
		],
		"UPFIELD"=>[
			"Batman",
			"Merlynston",
			"Fawkner",
			"Gowrie",
			"Campbellfield",
			"Upfield",
		],
		"WERIBEE"=>[
			"Altona",
			"Westona",
			"Laverton",
			"Aircraft",
			"Williams Landing",
			"Hoppers Crossing",
			"Werribee",
		],
		"CRAGIEBURN"=>[
			"Pascoe Vale",
			"Oak Park",
			"Glenroy",
			"Jacana",
			"Broadmeadows",
			"Coolaroo",
			"Roxburgh Park",
			"Craigieburn",
		],
		"SYDENHAM / MELTON"=>[
			"Sunshine",
			"Albion",
			"Ginifer",
			"St Albans",
			"Keilor Plains",
			"Watergardens",
			"Diggers Rest",
			"Sunbury",
			"Ardeer",
			"Deer Park",
			"Rockbank",
			"Melton",
		],
		"LILYDALE / BELGRAVE"=>[
			"Canterbury",
			"Chatham",
			"Surrey Hills",
			"Mont Albert",
			"Box Hill",
			"Laburnum",
			"Blackburn",
			"Nunawading",
			"Mitcham",
			"Heatherdale",
			"Ringwood",
			"Ringwood East",
			"Croydon",
			"Mooroolbark",
			"Lilydale",
			"Heathmont",
			"Bayswater",
			"Boronia",
			"Ferntree Gully",
			"Upper Ferntree Gully",
			"Upwey",
			"Tecoma",
			"Belgrave",
		],
		"GLEN WAVERLEY"=>[
			"Darling",
			"East Malvern",
			"Holmesglen",
			"Jordanville",
			"Mount Waverley",
			"Syndal",
			"Glen Waverley",
		],
		"SOUTH MORANG"=>[
			"Preston",
			"Regent",
			"Reservoir",
			"Ruthven",
			"Keon Park",
			"Thomastown",
			"Lalor",
			"Epping",
			"South Morang",
		],
		"HURSTBRIDGE"=>[
			"Ivanhoe",
			"Eaglemont",
			"Heidelberg",
			"Rosanna",
			"Macleod",
			"Watsonia",
			"Greensborough",
			"Montmorency",
			"Eltham",
			"Diamond Creek",
			"Wattle Glen",
			"Hurstbridge",
		]
	];
}