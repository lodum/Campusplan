<?php
// Query database and return JSON string
function sparql_get($query){
	$url = 'http://data.uni-muenster.de/sparql?query='.urlencode($query).'&format=json';
	$opts = array(
		'http'=>array(
			'header' => "Accept: application/sparql-results+json\r\n",
			'timeout' => 10
		)
	);
	$context = stream_context_create($opts);
	$response = file_get_contents($url, false, $context);
	if(json_decode($response)) { // check for validity
		return $response;
	}
	return false;
}

// Search database by starting letter
function searchByLetter($letter){
	
	$orgs = sparql_get("

prefix foaf: <http://xmlns.com/foaf/0.1/> 
prefix aiiso: <http://purl.org/vocab/aiiso/schema#>
prefix lodum: <http://vocab.lodum.de/helper/>
		
SELECT DISTINCT ?orga ?name WHERE { 
		
	Graph <http://data.uni-muenster.de/context/uniaz/> {
          ?orga a ?type ; 
	            foaf:name ?name .
	  BIND(lcase(?name) as ?lname) .
	  FILTER langMatches(lang(?name),'DE') .
	  FILTER (STRSTARTS(?name, '".$letter."')) .
	  FILTER (STRLEN(?name) > 0) .
	  FILTER regex(str(?orga),'uniaz') . 
    }
           
} ORDER BY ?lname
");
	
	return $orgs;
}

// Search database by whole word
function searchByWord($searchterm){

	$orgs = sparql_get("

prefix foaf: <http://xmlns.com/foaf/0.1/> 
prefix aiiso: <http://purl.org/vocab/aiiso/schema#>
prefix lodum: <http://vocab.lodum.de/helper/>
		
SELECT DISTINCT ?orga ?name WHERE { 
		
	Graph <http://data.uni-muenster.de/context/uniaz/> {
          ?orga a ?type ; 
	            foaf:name ?name .
	  
	  BIND(lcase(?name) as ?lname) .
	  FILTER langMatches(lang(?name),'DE') .
	  FILTER regex(?name, '".$searchterm."', 'i' ) .
	  FILTER (STRLEN(?name) > 0) .
	  FILTER regex(str(?orga),'uniaz') . 
    }
           
} ORDER BY ?lname
");
	
	return $orgs;
}

// single organization query
function getOrgDetails($identifier, $lang = "de"){
	$org = "http://data.uni-muenster.de/context/uniaz/".$identifier;
	$orga = sparql_get("

prefix foaf: <http://xmlns.com/foaf/0.1/> 
prefix geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> 
prefix vcard: <http://www.w3.org/2006/vcard/ns#>
prefix lodum: <http://vocab.lodum.de/helper/>
prefix ogc: <http://www.opengis.net/ont/OGC-GeoSPARQL/1.0/>
prefix xsd: <http://www.w3.org/2001/XMLSchema#> 

SELECT DISTINCT ?name ?homepage ?address ?street ?zip ?city ?buildingaddress ?lat ?long ?wkt WHERE {
  <".$org."> foaf:name ?name.
  OPTIONAL { <".$org."> foaf:homepage ?homepage . }  
  OPTIONAL { <".$org."> vcard:adr ?address . 
  	FILTER ( datatype(?address) = xsd:string )
  }
  OPTIONAL { <".$org."> lodum:building ?building . 
     OPTIONAL { ?building geo:lat ?lat ; 
                              geo:long ?long . }
     OPTIONAL { ?building vcard:adr ?buildingAddress . 
     			?buildingAddress vcard:street-address ?street ;
     			    vcard:postal-code ?zip ;
     			    vcard:region ?city .     			
     }          
     OPTIONAL { ?building ogc:hasGeometry ?geometry .
                          ?geometry ogc:asWKT ?wkt . } 
  }    
  FILTER langMatches(lang(?name),'".$lang."') . 
}
	");

	return $orga;
}