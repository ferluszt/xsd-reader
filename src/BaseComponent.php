<?php  
namespace goetas\xml\xsd;
use goetas\xml\XMLDomElement;
abstract class BaseComponent extends Type {
	protected $elements = array();
	protected $attributes = array();
	
	public function __construct(Schema $xsd, XMLDomElement $node) {
		parent::__construct($xsd, $node->getAttribute("name"));
		$this->recurse($node);
	}
	public function getElements() {
		return $this->elements;
	}
	public function getAttributes() {
		return $this->attributes;
	}
	protected function recurse(XMLDomElement $node) {
		foreach ($node->query("xsd:*", array("xsd" => self::NS)) as $nd) {
			$this->parseElement($nd);
		}
	}
	protected function parseElement(XMLDomElement $node){
		switch ($node->localName){
			case "sequence";
				$this->recurse($node);
			break;
			case "complexContent";
				$this->recurse($node);
			break;
			case "extension";
				list($ns, $name, $prefix ) = Schema::findParts( $node,  $node->getAttribute("base"));
				if($ns == $this->xsd->getNs()){
					$nodes = $node->query("//xsd:schema/xsd:complexType[@name='$name']",array("xsd" => self::NS));
					
					$this->recurse($nodes->item(0));
					foreach ($node->childNodes as $n){
						if($n instanceof XMLDOMElement){
							$this->recurse($n);
						}
					}
					
				}else{
					
				}
				
			break;
			case "attributeGroup";
				list($ns, $name, $prefix ) = Schema::findParts( $node,  $node->getAttribute("ref"));
				$g = $this->findGroup($ns, $name);
				foreach ($g as $el){
					$this->elements[] = $el;
				}
			break;
			case "attributeGroup";
				list($ns, $name, $prefix ) = Schema::findParts( $node,  $node->getAttribute("ref"));
				$g = $this->findAttributeGroup($ns, $name);
				foreach ($g as $att){
					$this->attributes[] = $att;
				}
			break;
			
			case "element";
				if($node->hasAttribute("ref")){
					list($ns, $name, $prefix ) = Schema::findParts( $node,  $node->getAttribute("ref"));
					$this->elements[] = $this->xsd->findElement($ns, $name);
				}else{
					$min  = $node->hasAttribute("minOccurs")?$node->getAttribute("minOccurs"):0;
					$max  =  $node->hasAttribute("maxOccurs")?$node->getAttribute("maxOccurs"):1; 
					if($max=="unbounded"){
						$max = PHP_INT_MAX;
					}
					
					
					list($ns, $name, $prefix ) = Schema::findParts( $node,  $node->getAttribute("type"));
					
			
					
					$type = new Type($this->xsd->getNs()==$ns?$this->xsd:$this->xsd->getContainer()->getSchema($ns),$name);
							
					
					$this->elements[] = new ComplexElement($this->xsd, $type, $node->getAttribute("name"), $min, $max, $node->getAttribute("nillable")=="true");
				}	
					
			break;
			case "attribute";
				if($node->hasAttribute("ref")){
					$this->attributes[] = $this->xsd->findAttribute($node, $node->getAttribute("ref"));
				}else{
					$required = $node->hasAttribute("use")?$node->getAttribute("use")!='optional':false;
					
					list($ns, $name, $prefix ) = Schema::findParts( $node,  $node->getAttribute("type"));
					
					$type = new Type($this->xsd->getNs()==$ns?$this->xsd:$this->xsd->getContainer()->getSchema($ns),$name);
					
					$this->attributes[] = new Attribute($this->xsd, $type, $node->getAttribute("name"), $required, $node->getAttribute("default"));
				}
					
					
			break;
		}
	}
} 