<?php


class phpZoteroEntries {
  
  public $dom;
  public $xpath;
  public $data;
  
  /**
   * 
   * Enter description here ...
   * @param string $xmlString The Atom returned by a request to the Zotero API
   * @param boolean $parse Whether to parse the string into a data array
   */
  
  public function __construct($xmlString = null, $parse = true) {
    
    if($xmlString !== null) {
      $this->resetXML($xml, $parse);
    }
  }

  public function setDataFromTemplate($template) {    
    $data = array('content' => json_decode($template, true) );
    $this->data[] = $data;    
  }
  
  public function resetXML($xml, $parse = true ) {
    $this->dom = new DomDocument();    
    $this->dom->loadXML($xml);
    $this->xpath = new DOMXPath($this->dom);
    $this->xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
    $this->xpath->registerNamespace('zapi', 'http://zotero.org/ns/api');      
            
    if($parse) {
      $this->data = array( );
      $entries = $this->xpath->query("//atom:entry");        
      foreach($entries as $entry) {
        $this->parseEntry($entry, TRUE);
      }        
    }    
  }
  
  
  /**
   * 
   * Returns the number of entry elements
   */
  
  public function entryCount() {
    return $this->xpath->evaluate("count(//atom:entry)");
  }
  
  /**
   * 
   * Return an entry element
   * 
   * @param int $index 0-based index of the entry.
   */
  
  public function getEntryByIndex($index) {
    //since this method works with xpaths, with starting index 1,
    //let people expect to use 0-based indexes for everything
    $index++;
    return $this->xpath->query("//atom:entry[$index]")->item(0);    
  }
  
  /**
   * 
   * Returns the data for a field in an element (e.g., 'published', 'id', etc.)
   * Namespaces are (mostly) are sorted out for you
   * The commonly-used 'etag' can be passed directly as the element
   * 
   * @param string $el The element inside the entry (xpaths should work, too)
   * @param int $index The 0-based index of the entry
   */
  
  public function getEntryElement($el, $index = 0) {
    //since this method works with xpaths, with starting index 1,
    //let people expect to use 0-based indexes for everything
    $index++;
    
    $zapiEls = array(
      'key',
      'itemType',
      'creatorSummary',
      'numChildren',
      'numTags'
    );
    if( in_array($el, $zapiEls)) {
      $el = "zapi:$el";
    } else {
      $el = "atom:$el";
    }
    
    if( $el == 'atom:etag' ) {
      $el = "atom:content/@etag";
    }
    return $this->xpath->query("//atom:entry[$index]//$el")->item(0)->textContent;
  }
  
  /**
   * 
   * Returns the parsed data as JSON
   * @throws Exception
   */
  public function getDataAsJson() {
    if(!isset($this->data)) {
      throw new Exception("Entries must be parsed first.");
    }    
    return json_encode($this->data);
  }
  
  /**
   * 
   * Returns the content for an entry as JSON
   * @param int $index The 0-based index of the entry
   * @throws Exception
   */
  public function getEntryContentAsJson($index = 0) {
    if(!isset($this->data)) {
      throw new Exception("Entries must be parsed first.");
    }
    return json_encode($this->data[$index]['content']);
  }
  
  public function setEntryProperty($prop, $value, $index = 0) {
    if(isset($this->data[$index]['content'][$prop])) {
      $this->data[$index]['content'][$prop] = $value;  
    } else {
      throw new Exception("$prop is not a valid field for item type " . $this->data[$index]['content']['itemType'] );
    }
    
  }
  
  public function getEntryContent($index = 0) {
    return $this->data[$index]['content'];
  }

  /**
   * 
   * Returns the data as a JSON object of items, suitable for passing to
   * the Zotero API create method
   * @throws Exception
   */
  public function getDataAsItemsJson() {
    if(!isset($this->data)) {
      throw new Exception("Entries must be parsed first.");
    }    
    $items = array("items" => array() );
    foreach($this->data as $index=>$entryData) {
      $items["items"][] = $entryData['content'];
    }
    return json_encode($items);
  }
  /**
   * 
   * Parses an <entry> element into a data array (mostly) hashed by tag name
   * <link>s are moved to a hash based on their @rel value
   * @param DOMElement $entry
   * @param boolean $addToData Whether to add the data to the instances data property
   * @return array 
   */
  public function parseEntry($entry, $addToData = FALSE) {
    $data = array();
    $els = $entry->getElementsByTagName('*');
        
    foreach ($els as $el) {
      switch ($el->tagName) {
        case 'link' :
        //ignore links except up and enclosure
          if ($el->getAttribute('rel') == 'up') {
            $href = $el->getAttribute('href');
            $key = array_pop(explode('/', $href));
            $data['up'] = array(
              'href' => $href,
              'zapi:key' => $key            
            );
          }
        break; 
        case 'author':
          //TODO: handle the nested author info
          $data['author'] = array(
            'name' => $this->xpath->query("atom:name" , $el)->item(0)->textContent,
            'uri' => $this->xpath->query("atom:uri" , $el)->item(0)->textContent
          );
        break;
        case 'content':
          $data['etag'] = $el->getAttribute('etag');
          //see if the content is json
          if($el->getAttribute('type') == 'application/json') {
            $data['content'] = json_decode($el->textContent, true);
          } else {
            $data['content'] = $el->textContent;
          }          
          
        break;
        
        default:
          $data["$el->tagName"] = $el->textContent;
        break;
      }
      
    }
    if($addToData) {
      $this->data[] = $data;  
    }
    
    return $data;
  }
  
}