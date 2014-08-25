<?php
# This is a work in progress, is very rough and should NOT be used in 
# a production setting.  I am still working on it and tidying up code.

function rcomexpress_getConfigArray() {
	$configarray = array( 
	"FriendlyName" => array( "Type" => "System" , "Value" => "Rcomexpress" ) , 
	"applicationGuid" => array( "Type" => "text" , "Size" => "20" , 
	"Description" => "This is the unique key assigned by RxPortalExpress" ) , 
	"TestMode" => array( "Type" => "yesno" ) 
	);
	return ( $configarray );
}

function rcomexpress_curlCall($xml, $params) {
	if( $params["TestMode"] ) {
		$url = "https://staging-services.rxportalexpress.com/V1.0/";
		}
	else {
	$url = "https://services.rxportalexpress.com/V1.0/";
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$xml_data = curl_exec($ch);
	
	curl_close($ch);
	
	# add curl error handling here
	# /*if(curl_errno($ch)) {
	#	echo 'error:' . curl_error($ch);
	#	}*/
	
	$xmlasarray = array();
	$xmlresult = simplexml_load_string($xml_data);
	$json = json_encode($xmlresult);
	$xmlasarray = json_decode($json,TRUE);
	
	
	#$command = $xmlasarray["SERVICEREQUEST"]["COMMAND"];
	#logModuleCall("rcomexpress", $command, $xml, $xml_data);
	
	return ($xmlasarray);
}

function rcomexpress_GetNameservers($params) {
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainGet'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('page','1'));
	$domaintag = $xml->createElement('domains');
	$domaintag->appendChild($xml->createElement('domainName',$domain));
	$request->appendChild($domaintag);
	$root->appendChild($request);
	$xml=$xml->saveXML($root);
	
	$data = rcomexpress_curlCall($xml, $params);
	$data = $data['response']['domainGet']['domain']['nameServers']['nameServer'];
	$maxcount = count($data);
	$count = 0;
	
	while ($count<$maxcount) {
		$tmpcount=$count+1;
		$thisns="ns$tmpcount";
		$values[$thisns]=$data[$count]['nsName'];
		$count++;
		}
	
	if( empty( $values["ns1"] ) && empty( $values["ns2"] ) ) {
		$values["error"] = "Error: GetNameservers failed - no values returned. " . $domain ;
		}
	return ( $values );
	}
	
function rcomexpress_GetProductIdByDomain($domain, $params) {
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainGet'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('page','1'));
	$domaintag = $xml->createElement('domains');
	$domaintag->appendChild($xml->createElement('domainName',$domain));
	$request->appendChild($domaintag);
	$root->appendChild($request);
	$xml=$xml->saveXML($root);
 	$data = rcomexpress_curlcall($xml, $params);
	return ( $data["response"]["domainGet"]["domain"]["domainInfo"]["productId"]);
}

function rcomexpress_SaveNameservers($params) {
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	
	$product_id = rcomexpress_GetProductIdByDomain($domain, $params);
   	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainModify'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('productId',$product_id));
	
		$nameservers = $xml->createElement("nameservers");

		$ns1 = $xml->createElement("nameserver");
			$ns1->appendChild($xml->createElement("nsType","Primary"));
			$ns1->appendChild($xml->createElement("nsName",$params["ns1"]));
			$nameservers->appendChild($ns1);

		$ns2 = $xml->createElement("nameserver");
			$ns2->appendChild($xml->createElement("nsType","Secondary"));
			$ns2->appendChild($xml->createElement("nsName",$params["ns2"]));
			$nameservers->appendChild($ns2);

		if ($params["ns3"]) {
			$ns3 = $xml->createElement("nameserver");
			$ns3->appendChild($xml->createElement("nsType","Secondary"));
			$ns3->appendChild($xml->createElement("nsName",$params["ns3"]));
			$nameservers->appendChild($ns3);
			}

		if ($params["ns4"]) {
			$ns4 = $xml->createElement("nameserver");
			$ns4->appendChild($xml->createElement("nsType","Secondary"));
			$ns4->appendChild($xml->createElement("nsName",$params["ns4"]));
			$nameservers->appendChild($ns4);
			}
					
		if ($params["ns5"]) {
			$ns5 = $xml->createElement("nameserver");
			$ns5->appendChild($xml->createElement("nsType","Secondary"));
			$ns5->appendChild($xml->createElement("nsName",$params["ns5"]));
			$nameservers->appendChild($ns5);
			}


	$request->appendChild($nameservers);
	$root->appendChild($request);
	$xml=$xml->saveXML($root);	
	$data = rcomexpress_curlCall($xml, $params);
	$data = $data["response"]["productId"];

	if( $product_id != $data ) {
		$values["error"] = "The requested nameserver changes were NOT accepted by the registrar for the domain " . $domain;
		}
	return ( $values );
}

function rcomexpress_FormatPhoneNumber ($country,$phonenumber) {
	### Not sure this is good.  Test thoroughly before use on UK numbers.  
	require ROOTDIR . "/includes/countriescallingcodes.php";
	$result = "+" . $countrycallingcodes[$country] . "." . preg_replace("/[^0-9]/", "", $phonenumber);
	return ($result);
	}

function rcomexpress_RegisterDomain($params) {
########################################################################
# Register a domain name. 
########################################################################    
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	
	$RegistrantCountry = $params["country"];
	if( $RegistrantCountry == "US" ) {
		$state = rcomexpress_convert_us_state($params["state"]);
	}

	
	$AdminCountry = $params["admincountry"];
	if( $AdminCountry == "US" ) {
		$adminstate = rcomexpress_convert_us_state($params["adminstate"]);
		}

	$RegistrantPhone = rcomexpress_FormatPhoneNumber($params["country"], $params["phonenumber"]);
	$AdminPhone = rcomexpress_FormatPhoneNumber($params["admincountry"], $params["adminphonenumber"]);
	
	#Start Building XML
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);

	$root->appendChild($xml->createElement('command','domainAdd'));

	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);

	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('domainName',$domain));
	$request->appendChild($xml->createElement('term',$params["regperiod"]));
	
	$contacts = $xml->createElement("contacts");
	# Do Registrant Contact
		$contact = $xml->createElement("contact");      
		$contact->appendChild($xml->createElement('firstName',$params["firstname"]));
		$contact->appendChild($xml->createElement('lastName',$params["lastname"]));
		$contact->appendChild($xml->createElement('emailAddress',$params["email"]));
		$contact->appendChild($xml->createElement('telephoneNumber',$RegistrantPhone));
		$contact->appendChild($xml->createElement('addressLine1',$params["address1"]));
		$contact->appendChild($xml->createElement('addressLine2',$params["address2"]));
		$contact->appendChild($xml->createElement('city',$params["city"]));
		if( $RegistrantCountry == "US" ) {
			$contact->appendChild($xml->createElement('state',$state));
			}
		else {
			$contact->appendChild($xml->createElement('province',$params["state"]));
			}
		$contact->appendChild($xml->createElement('postalCode',$params["postcode"]));
		$contact->appendChild($xml->createElement('countryCode',$params["country"]));
		$contact->appendChild($xml->createElement('contactType','Registration'));

	# Do Admin Contact    
		$contact2 = $xml->createElement("contact");
		$contact2->appendChild($xml->createElement('firstName',$params["adminfirstname"]));
		$contact2->appendChild($xml->createElement('lastName',$params["adminlastname"]));
		$contact2->appendChild($xml->createElement('emailAddress',$params["adminemail"]));
		$contact2->appendChild($xml->createElement('telephoneNumber',$AdminPhone));
		$contact2->appendChild($xml->createElement('addressLine1',$params["adminaddress1"]));
		$contact2->appendChild($xml->createElement('addressLine2',$params["adminaddress2"]));
		$contact2->appendChild($xml->createElement('city',$params["admincity"]));
 		if( $AdminCountry == "US" ) {
			$contact2->appendChild($xml->createElement('state',$adminstate));
			}
		else {
			$contact2->appendChild($xml->createElement('province',$params["state"]));
			}
		$contact2->appendChild($xml->createElement('postalCode',$params["adminpostcode"]));
		$contact2->appendChild($xml->createElement('countryCode',$params["admincountry"]));
		$contact2->appendChild($xml->createElement('contactType','Administration'));
 
		$contacts->appendChild($contact);
		$contacts->appendChild($contact2);
		$request->appendChild($contacts);
		
	# Do NameServers
		$nameservers = $xml->createElement("nameservers");

		$ns1 = $xml->createElement("nameserver");
			$ns1->appendChild($xml->createElement("nsType","Primary"));
			$ns1->appendChild($xml->createElement("nsName",$params["ns1"]));
			$nameservers->appendChild($ns1);

		$ns2 = $xml->createElement("nameserver");
			$ns2->appendChild($xml->createElement("nsType","Secondary"));
			$ns2->appendChild($xml->createElement("nsName",$params["ns2"]));
			$nameservers->appendChild($ns2);

		if ($params["ns3"]) {
			$ns3 = $xml->createElement("nameserver");
			$ns3->appendChild($xml->createElement("nsType","Secondary"));
			$ns3->appendChild($xml->createElement("nsName",$params["ns3"]));
			$nameservers->appendChild($ns3);
			}

		if ($params["ns4"]) {
			$ns4 = $xml->createElement("nameserver");
			$ns4->appendChild($xml->createElement("nsType","Secondary"));
			$ns4->appendChild($xml->createElement("nsName",$params["ns4"]));
			$nameservers->appendChild($ns4);
			}
					
		if ($params["ns5"]) {
			$ns5 = $xml->createElement("nameserver");
			$ns5->appendChild($xml->createElement("nsType","Secondary"));
			$ns5->appendChild($xml->createElement("nsName",$params["ns5"]));
			$nameservers->appendChild($ns5);
			}


	$request->appendChild($nameservers);
	$root->appendChild($request);
	$xml_adddomain=$xml->saveXML($root);
	$data = rcomexpress_curlCall($xml_adddomain, $params);
	$thisdomainId = $data["response"]["productId"];
	$status = $data["status"]["statusCode"];
	
	if( $status != "1000" ) {
		$values["error"] = "Failed to register the domain, See log for details." . $domain;
		return ( $values );
		}

	#Additional Check  I don't trust the returned status in previous test.
	$domain_product_id = rcomexpress_GetProductIdByDomain($domain, $params);
	if( $thisdomainId != $domain_product_id ) {
		$values["error"] = "Failed to register the domain " . $domain;
		return ( $values );
		}
}

function rcomexpress_GetRegistrarLock($params) {
  	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainGet'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('page','1'));
	$domaintag = $xml->createElement('domains');
	$domaintag->appendChild($xml->createElement('domainName',$domain));
	$request->appendChild($domaintag);
	$root->appendChild($request);
	$xml=$xml->saveXML($root);
	
	$data = rcomexpress_curlCall($xml, $params);
	$lock = $data["response"]["domainGet"]["domain"]["domainInfo"]["registrarLock"];

	if( $lock == "On" ) {
		$lockstatus = "locked";
		} else {
		$lockstatus = "unlocked";
			}
	
	return ( $lockstatus );
}

function rcomexpress_SaveRegistrarLock($params) {
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	if( $params["lockenabled"] == "locked" ) {
		$lockstatus = "True";
		}
		else {
			$lockstatus = "False";
			}
			
	$product_id = rcomexpress_GetProductIdByDomain($domain, $params);
   	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainLock'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('productId',$product_id));
	$request->appendChild($xml->createElement('registrarLock',$lockstatus));
	$root->appendChild($request);
	$xml=$xml->saveXML($root);	
		
	$data = rcomexpress_curlCall($xml, $params);
	$thisproductId = $data["response"]["productId"];
	if( $product_id != $thisproductId ) {
		$values["error"] = "Cannot modify lock status for domain - " . $domain;
		}

	return ( $values );
	}

function rcomexpress_GetContactDetails($params) {
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainGet'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('page','1'));
	$domaintag = $xml->createElement('domains');
	$domaintag->appendChild($xml->createElement('domainName',$domain));
	$request->appendChild($domaintag);
	$root->appendChild($request);
	$xml=$xml->saveXML($root);
	
	$data = rcomexpress_curlCall($xml, $params);
	$data = $data['response']['domainGet']['domain']['contacts'];
	$contacttypes = array( "Registrant" , "Admin" , "Tech" );
	$i = 0;
	
	# Fill in the return values.  Where no return value is present fill with "" to prevent array() text.
	while( $i <= 2 ) {
	   $values[$contacttypes[$i]]["First Name"] = $data['contact'][$i]['firstName'];
		$values[$contacttypes[$i]]["Last Name"] = $data['contact'][$i]['lastName'];
		if(!is_array($data['contact'][$i]['companyName'])) {
				$values[$contacttypes[$i]]["Organisation Name"] = $data['contact'][$i]['companyName'];
				} else {
					$values[$contacttypes[$i]]["Organisation Name"] = "";
					}
		if(!is_array($data['contact'][$i]['companyPositionHeld'])){
				$values[$contacttypes[$i]]["Job Title"] = $data['contact'][$i]['companyPositionHeld'];
				} else {
					$values[$contacttypes[$i]]["Job Title"] = "";
					}
		$values[$contacttypes[$i]]["Email"] = $data['contact'][$i]['emailAddress'];
		$values[$contacttypes[$i]]["Address 1"] = $data['contact'][$i]['addressLine1'];
		if(!is_array($data['contact'][$i]['addressLine2'])){
				$values[$contacttypes[$i]]["Address 2"] = $data['contact'][$i]['addressLine2'];
				} else {
					$values[$contacttypes[$i]]["Address 2"] = "";
					}
		$values[$contacttypes[$i]]["City"] = $data['contact'][$i]['city'];
		if(!is_array($data['contact'][$i]['province'])){
				$values[$contacttypes[$i]]["State"] = $data['contact'][$i]['province'];
				} else {
					$values[$contacttypes[$i]]["State"] = "";
					}
		if(!is_array($data['contact'][$i]['state'])){
				$values[$contacttypes[$i]]["State"] = $data['contact'][$i]['state'];
				} 
		$values[$contacttypes[$i]]["Postcode"] = $data['contact'][$i]['postalCode'];
		$values[$contacttypes[$i]]["Country"] = $data['contact'][$i]['countryCode'];
		$values[$contacttypes[$i]]["Phone"] = $data['contact'][$i]['telephoneNumber'];
		if(!is_array($data['contact'][$i]['faxNumber'])){
				$values[$contacttypes[$i]]["Fax"] = $data['contact'][$i]['faxNumber'];
				} else {
					$values[$contacttypes[$i]]["Fax"] = "";
					}
		++$i;
	}
	return ( $values );
	}
	
function rcomexpress_SaveContactDetails($params) {
	require ROOTDIR . "/includes/countriescallingcodes.php";
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	$product_id = rcomexpress_GetProductIdByDomain($domain, $params);
	
	# Fix phone numbers
	$contacttypes = array( "Registration" , "Administration" , "Technical" );
    $i = 0;
    foreach ( $params["contactdetails"] as &$contactdetails ) {
		$contactdetails["Phone"] = "+" . $countrycallingcodes[$contactdetails["Country"]] . "." . ltrim(preg_replace("/[^0-9]/", "", $contactdetails["Phone"]), '0');
		$contactdetails["Contact Type"] = $contacttypes[$i];
		$i++;
		}

	# Build XML Base Document
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainModify'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('productId',$product_id));
	$contacts = $xml->createElement("contacts");

	#cycle through each contact.  	
	foreach ( $params["contactdetails"] as &$contactdetails ) {
		$contact = $xml->createElement("contact");      
		$contact->appendChild($xml->createElement('firstName',$contactdetails["First Name"]));
		$contact->appendChild($xml->createElement('lastName',$contactdetails["Last Name"]));
		$contact->appendChild($xml->createElement('emailAddress',$contactdetails["Email"]));
		$contact->appendChild($xml->createElement('telephoneNumber',$contactdetails["Phone"]));
		$contact->appendChild($xml->createElement('addressLine1',$contactdetails["Address 1"]));
		$contact->appendChild($xml->createElement('addressLine2',$contactdetails["Address 2"]));
		$contact->appendChild($xml->createElement('city',$contactdetails["City"]));
		if( $RegistrantCountry == "US" ) {
			$contact->appendChild($xml->createElement('state',$contactdetails["State"]));
			}
		else {
			$contact->appendChild($xml->createElement('province',$contactdetails["State"]));
			}
		$contact->appendChild($xml->createElement('postalCode',$contactdetails["Postcode"]));
		$contact->appendChild($xml->createElement('countryCode',$contactdetails["Country"]));
		$contact->appendChild($xml->createElement('contactType',$contactdetails["Contact Type"]));
		# Add this contact to xml
		$contacts->appendChild($contact);
		# unset this contact and loop
		unset($contact);
		
		}
	$request->appendChild($contacts);
	$root->appendChild($request);

	$xml_adddomain=$xml->saveXML($root);
	$data = rcomexpress_curlCall($xml_adddomain, $params);
	$domProductId = $data["response"]["productId"];
	$status = $data["status"]["statusCode"];
	
	if( $status != "1000" ) {
		$values["error"] = "Failed to update the domain, See log for details." . $domain;
		return ( $values );
		}

	$domain_product_id = rcomexpress_GetProductIdByDomain($domain, $params);
	if( $domProductId != $domain_product_id ) {
		$values["error"] = "Failed to update the domain " . $domain;
		return ( $values );
		}
	}
	
function rcomexpress_RenewDomain($params) {
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	$product_id = rcomexpress_GetProductIdByDomain($domain, $params);
	
	# Build XML Base Document
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainRenew'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('productId',$product_id));
	$request->appendChild($xml->createElement('term',$params["regperiod"]));
	$root->appendChild($request);

	$xml_adddomain=$xml->saveXML($root);
	$data = rcomexpress_curlCall($xml_adddomain, $params);
	$thisdomainId = $data["response"]["productId"];
	$status = $data["status"]["statusCode"];
	if( $status != "1000" ) {
		$values["error"] = "Failed to renew the domain.  " . $domain . " " . $data["status"]["statusDescription"];
		return ( $values );
		}
	}
	
function rcomexpress_GetEPPCode($params) {
 	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainGet'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('page','1'));
	$domaintag = $xml->createElement('domains');
	$domaintag->appendChild($xml->createElement('domainName',$domain));
	$request->appendChild($domaintag);
	$root->appendChild($request);
	$xml=$xml->saveXML($root);
	
	$data = rcomexpress_curlCall($xml, $params);
	$values['eppcode'] = $data['response']['domainGet']['domain']['domainInfo']['password'];
	logModuleCall("rcomexpress", "getEPP", $values, $data);
	$thisdomainId = $data["response"]['domainGet']['domain']["productId"];
	$status = $data["status"]["statusCode"];
		
	if( $status != "1000" ) {
		$values["error"] = "Failed to retrieve EPP code for domain:- " . $domain;
		}
	return ($values);
	}

function rcomexpress_TransferDomain($params) {
########################################################################
# Transfer in a domain name. 
########################################################################    
	require ROOTDIR . "/includes/countriescallingcodes.php";
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	
	$RegistrantCountry = $params["country"];
	if( $RegistrantCountry == "US" ) {
		$state = rcomexpress_convert_us_state($params["state"]);
	}
	
	$AdminCountry = $params["admincountry"];
	if( $AdminCountry == "US" ) {
		$adminstate = rcomexpress_convert_us_state($params["adminstate"]);
		}

	### Does this work in all cases?  Test before use on UK numbers,  think this needs a lot more work, smells bad.
	$RegistrantPhone = "+" . $countrycallingcodes[$params["country"]] . "." . preg_replace("/[^0-9]/", "", $params["phonenumber"]);
	$AdminPhone = "+" . $countrycallingcodes[$params["admincountry"]] . "." . preg_replace("/[^0-9]/", "", $params["adminphonenumber"]);
	####


	#Start Building XML
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);

	$root->appendChild($xml->createElement('command','domainTransferIn'));

	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);

	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('domainName',$domain));
	$request->appendChild($xml->createElement('authCode',$params["transfersecret"]));
	
	$contacts = $xml->createElement("contacts");
	# Do Registrant Contact
		$contact = $xml->createElement("contact");      
		$contact->appendChild($xml->createElement('firstName',$params["firstname"]));
		$contact->appendChild($xml->createElement('lastName',$params["lastname"]));
		$contact->appendChild($xml->createElement('emailAddress',$params["email"]));
		$contact->appendChild($xml->createElement('telephoneNumber',$RegistrantPhone));
		$contact->appendChild($xml->createElement('addressLine1',$params["address1"]));
		$contact->appendChild($xml->createElement('addressLine2',$params["address2"]));
		$contact->appendChild($xml->createElement('city',$params["city"]));
		if( $RegistrantCountry == "US" ) {
			$contact->appendChild($xml->createElement('state',$state));
			}
		else {
			$contact->appendChild($xml->createElement('province',$params["state"]));
			}
		$contact->appendChild($xml->createElement('postalCode',$params["postcode"]));
		$contact->appendChild($xml->createElement('countryCode',$params["country"]));
		$contact->appendChild($xml->createElement('contactType','Registration'));

	# Do Admin Contact    
		$contact2 = $xml->createElement("contact");
		$contact2->appendChild($xml->createElement('firstName',$params["adminfirstname"]));
		$contact2->appendChild($xml->createElement('lastName',$params["adminlastname"]));
		$contact2->appendChild($xml->createElement('emailAddress',$params["adminemail"]));
		$contact2->appendChild($xml->createElement('telephoneNumber',$AdminPhone));
		$contact2->appendChild($xml->createElement('addressLine1',$params["adminaddress1"]));
		$contact2->appendChild($xml->createElement('addressLine2',$params["adminaddress2"]));
		$contact2->appendChild($xml->createElement('city',$params["admincity"]));
 		if( $AdminCountry == "US" ) {
			$contact2->appendChild($xml->createElement('state',$adminstate));
			}
		else {
			$contact2->appendChild($xml->createElement('province',$params["state"]));
			}
		$contact2->appendChild($xml->createElement('postalCode',$params["adminpostcode"]));
		$contact2->appendChild($xml->createElement('countryCode',$params["admincountry"]));
		$contact2->appendChild($xml->createElement('contactType','Administration'));
 
		$contacts->appendChild($contact);
		$contacts->appendChild($contact2);
		$request->appendChild($contacts);
		
	$root->appendChild($request);
	$xml_adddomain=$xml->saveXML($root);
	$data = rcomexpress_curlCall($xml_adddomain, $params);
	$thisdomainId = $data["response"]["productId"];
	$status = $data["status"]["statusCode"];
	
	if( $status != "1000" ) {
		$values["error"] = "Domain Transfer Rejected, See log for details." . $domain;
		return ( $values );
		}

	#Additional Check  I don't trust the returned status in previous test.
	$domain_product_id = rcomexpress_GetProductIdByDomain($domain, $params);
	if( $thisdomainId != $domain_product_id ) {
		$values["error"] = "Failed to register the domain " . $domain;
		return ( $values );
		}
	}

function rcomexpress_TransferSync($params) {
	$tld = $params["tld"];
	$sld = $params["sld"];
	$domain = $sld . "." . $tld;
	$product_id = rcomexpress_GetProductIdByDomain($domain, $params);
	
	$xml = new DOMDocument();
	$root = $xml->createElement("serviceRequest");
	$xml->appendChild($root);
	$root->appendChild($xml->createElement('command','domainTransferGet'));
	$client = $xml->createElement("client");
	$client->appendChild($xml->createElement('applicationGuid',$params["applicationGuid"]));
	$client->appendChild($xml->createElement('clientRef',md5(date("YmdHis"))));
	$root->appendChild($client);
	$request = $xml->createElement("request");
	$request->appendChild($xml->createElement('page','1'));
	$request->appendChild($xml->createElement('productId',$product_id));
	$root->appendChild($request);
	$xml=$xml->saveXML($root);
	
	$data = rcomexpress_curlCall($xml, $params);
	#logModuleCall("rcomexpress", "TransferSync", $xml, $data);
	#print_r ($data);
	$status = $data['response']['domains']['domain']['status'];
	echo $status;	
	}

?>
