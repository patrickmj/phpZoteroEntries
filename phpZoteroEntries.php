<?php

class phpZoteroEntries {
  
  public $dom;
  public $xpath;
  public $data;
  
  
  public function __construct($xmlString = null, $parse = false) {
    
    if($xmlString !== null) {
      $this->dom = new DomDocument();    
      $this->dom->loadXML($xmlString);
      $this->xpath = new DOMXPath($this->dom);
      $this->xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
      $this->xpath->registerNamespace('zapi', 'http://zotero.org/ns/api');      
      $this->data = array( );
      
      if($parse) {
        $entries = $this->xpath->query("//atom:entry");
        
        foreach($entries as $entry) {
          $this->data[] = $this->parseEntry($entry);
        }        
      }      
    }
  }
    
  public function entryCount() {
    return $this->xpath->evaluate("count(//atom:entry)");
  }
  
  public function getEntryElement($el, $index = 1) {
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
    return $this->xpath->query("//atom:entry[$index]//$el")->item(0)->textContent;
  }
  
  public function getDataAsJson() {
    return json_encode($this->data);
  }
  
  public function parseEntry($entry) {
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
          $data['content'] = json_decode($el->textContent, true);
        break;
        
        default:
          $data["$el->tagName"] = $el->textContent;
        break;
      }
      
    }
    return $data;
  }
}