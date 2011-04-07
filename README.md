# phpZoteroEntries

A simple class for parsing entries returned by the Zotero API, manipulating the result, and constructing JSON to pass back to the Zotero API

See phpZotero (http://github.com/clioweb/phpZotero) for a PHP API to work with the Zotero API itself.


## Examples
$xmlString in these examples is either a single <atom:entry> element, or an <atom:feed>, as appropriate for the Zotero API request

$zEntries = new phpZoteroEntries($xmlString, true); //true parses the entries into a hashed array, $zEntries->data

//return all the data as JSON
echo $zEntries->getDataAsJson();

//change the content title for 2nd entry

$zEntries->data[1]['content']['title'] = "Revised Title"; 

//get the new content for the 2nd entry as JSON

echo $zEntries->getEntryContentAsJson(1); // the default index is 0 

// get the data as JSON suitable for writing to Zotero.  
// NB -- Zotero write API is not open at the time of this commit

$items = $zEntries->getDataAsItemsJson();

// get a single item, modify it, and update it in Zotero using phpZotero.  
// NB -- Zotero write API is not open at the time of this commit
// NB -- phpZotero is also under rapid development and change


$key = "xxxxxxxxxxxxxxxxxx"; //your Zotero API key
$id = #######; //your Zotero id
$itemkey = "ABCD123"; // the Zotero key (id) for the item

$z = new phpZotero($key);
$xmlString = $z->getItem($id, $itemkey, array('content'=>'json') );

$zEntry = new phpZoteroEntries($xmlString, true);
$zEntry->data[0]['content']['title'] = "Revised Title";

$update = $zEntry->getEntryContentAsJson();
$etag = $zEntry->getEntryElement('etag');

$z->updateItem($id, $update, $itemkey, $etag);




