<?php

$ifsfields = array(
	"Contact Info" => array(
		"MiddleName" => "Middle Name",
		"Nickname" => "Nickname",
		"Suffix" => "Suffix",
		"ContactNotes" => "Contact Notes"
	),

	"Optional Address" => array(
		"Address3Street1" => "Optional Street Address",
		"Address3Street2" => "Optional Street Address 2",
		"City3" => "Optional City",
		"State3" => "Optional State",
		"Country3" => "Optional Country",
		"PostalCode3" => "Optional Postal Code",
		"ZipFour3" => "Optional ZipFour"
	),

	"Other Info" => array(
		"Anniversary" => "Anniversary",
		"Birthday" => "Birthday",
		"AssistantName" => "Assistant Name",
		"AssistantPhone" => "Assistant Phone",
		"EmailAddress2" => "Email Address 2",
		"EmailAddress3" => "Email Address 3",
		"Phone2" => "Phone 2",
		"Phone3" => "Phone 3",
		"Fax1" => "Fax",
		"Fax2" => "Fax 2",
		"JobTitle" => "Job Title",
		"SpouseName" => "Spouse Name",
		"Title" => "Title",
		"Website" => "Website",
	)
);

$ifs_allowed = array(
		"1",
		"2",
		"3",
		"4",
		"5",
		"6",
		"7",
		"8",
		"9",
		"10",
		"11",
		"12",
		"13",
		"14",
		"15",
		"16",
		"17",
		"18",
		"19",
		"20",
		"21"
	);


// GET IFS CONTACT CUSTOM FIELDS
$custfields = $iwpro->app->dsFind("DataFormField", 200,0, "FormId", -1, array("Name","Label","DataType"));
if(is_array($custfields) && count($custfields) > 0) {
	foreach($custfields as $custfield) {
		if(in_array($custfield["DataType"], $ifs_allowed))
			$ifsfields["Contact Custom Fields"]["_" . $custfield["Name"]] = $custfield["Label"];
	}
}

// GET IFS ORDER CUSTOM FIELDS
$custfields = $iwpro->app->dsFind("DataFormField", 200,0, "FormId", -9, array("Name","Label","DataType"));
if(is_array($custfields) && count($custfields) > 0) {
	foreach($custfields as $custfield) {
		if(in_array($custfield["DataType"], $ifs_allowed))
			$ifsfields["Order Custom Fields"]["Order:_" . $custfield["Name"]] = $custfield["Label"];
	}
}