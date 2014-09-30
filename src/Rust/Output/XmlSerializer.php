<?php
namespace Rust\Output;

/**
 * NYTD_Util_XmlSerializer
 *
 * Turns PHP data structures into DOM nodes, or vise-versa
 *
 * @author Justin Sheckler <sheckler@nytimes.com>
 * @version $Id: XmlSerializer.class.php 32549 2009-06-27 17:34:20Z warren.chin $
 *
 * In the simplest case, this class takes a hash and a parent DOM node, and
 * creates a bunch of child nodes for each hash element, like so:
 *
 * $xml = new NYTD_Util_XmlSerializer();
 * $data = array( 'name'=>'Bob', 'age'=>23, 'sex'=>'male' );
 * $person_node = $doc->createElement('person');
 * $xml->serialize($data, $person_node);
 *
 * <person>
 *   <name>Bob</name>
 *   <age>23</age>
 *   <sex>male</sex>
 * </person>
 *
 * If you want "name" to be an attribute instead of a child node; you pass
 * the serializer an array that maps the parent node name to the attribute name,
 * like so:
 *
 * $xml->setAttrNamesTable(array( 'person'=>'name' ))
 *
 * <person name="Bob">
 *   <age>23</age>
 *   <sex>male</sex>
 * </person>
 *
 * You can also have more than one attibute on a node:
 *
 * $xml->setAttrNamesTable(array( 'person'=>array('name', 'age') ))
 *
 * Numeric arrays are a little more tricky because it's not legal to have XML
 * nodenames like <0>,<1>,<2>, etc., so we have to invent a new node name.
 * Here's the default behavior:
 *
 * $data = array( 'Bob', 'Mary', 'Seumas' );
 * $node = $doc->createElement('names');
 * $xml->serialize($data, $node);
 *
 * <names>
 *   <names_item>Bob</names_item>
 *   <names_item>Mary</names_item>
 *   <names_item>Seumas</names_item>
 * </names>
 *
 * If you set $xml->setArrayItemsNumeric(TRUE), you get the following:
 *
 * <names>
 *   <item_0>Bob</item_0>
 *   <item_1>Mary</item_1>
 *   <item_2>Seumas</item_2>
 * </names>
 *
 * Or, you can choose a new name for the child nodes:
 *
 * $xml->setArrayNamesTable(array( 'names' => 'friend' ));
 *
 * <names>
 *   <friend>Bob</friend>
 *   <friend>Mary</friend>
 *   <friend>Seumas</friend>
 * </names>
 *
 * Finally, you can rely on the fact that 'names' ends with an S and tell
 * the serializer to just strip that S off:
 *
 * $xml->setArrayItemsDepluralize(TRUE);
 *
 * <names>
 *   <name>Bob</name>
 *   <name>Mary</name>
 *   <name>Seumas</name>
 * </names>
 *
 * Boolean values will get turned into the strings 'Y' or 'N'; you can change
 * this by e.g.: setting $xml->setBooleanValuesTable(array( TRUE=>'on', FALSE=>'off' )).
 * 
 * Also: the serializer will detect the characters <, > and & in your data
 * and automatically wrap those strings in a CDATA section.  If you don't like
 * this behavior your can change it by setting $xml->setCDataRegex($regex).
 *
 * Updated: 2008-02-11
 *
 * This class can now deserialize as well as serialize.  All the rules for
 * array naming conventions are followed as w/ serialize above, with the
 * addition that any node that contains duplicately-named child nodes will
 * be treated as a numeric array.
 *
 */
class XmlSerializer {

    /**
     * Pass these options to __construct in an array
     * @var array
     */
    static $opt_params = array('array_names_table', 
                               'attr_names_table',
                               'array_items_numeric', 
                               'use_cdata_regex', 
                               'boolean_values_table',
                               'array_items_depluralize', 
                               'processValueNodes');

    /**
     * Maps parent node names to child nodes names for array data
     * @var array
     */
    protected $array_names_table = array();

    /**
     * Maps parent node names to attribute names for hash data
     * @var array
     */
    protected $attr_names_table = array();

    /**
     * If true, names array elements as "item_0", "item_1" &c.
     * @var boolean
     */
    protected $array_items_numeric = FALSE;

    /**
     * If true, strips a trailing 'S' from parent node names for arrays
     * @var boolean
     */
    protected $array_items_depluralize = FALSE;

    /**
     * String data that matches this regex will be wrapped in a CDATA section
     * @var string
     */
    protected $use_cdata_regex = '/[<>&]/';

    /**
     * Maps boolean values to plain strings
     * @var array
     */
    protected $boolean_values_table = array( TRUE=>'Y', FALSE=>'N' );

    /**
     * 
     * Converts the following structure:
     * 
     * [type] => Array
     *           (
     *              [value] => Articles
     *           )
     * into:
     *
     * <type>Articles</type>
     * 
     * if there are 
     * 
     */
    
    protected $processValueNodes = false;
    
    /**
     * Pass the following params in an array, all optional:
     * @param array_names_table array of node names for array serialization; default none
     * @param attr_names_table array of attribute names for hash serialization; default none
     * @param array_items_numeric name array elements as "item_#"; off by default
     * @param array_items_depluralize strip a trailing 'S' from array name to name elements; off by default
     * @param use_cdata_regex regex for detecting data that should be wrapped in CDATA; by default <, > and &
     * @param boolean_values_table maps boolean values to strings; by default Y and N
     */
    function __construct(array $params = array()) {
        if (!is_array($params)) {
            throw new \Exception('__construct expects $params to be an array');
        }
        $this->parseNamedParameters($params, self::$opt_params);
        $this->assertParameter(is_array($this->array_names_table), "array_names_table must be array");
        $this->assertParameter(is_array($this->attr_names_table), "attr_names_table must be array");
        $this->assertParameter(is_array($this->boolean_values_table), "boolean_values_table must be array");
        $this->assertParameter(is_string($this->use_cdata_regex), "use_cdata_regex must be string");
        $this->assertParameter(is_bool($this->array_items_numeric), "array_items_numeric must be bool");
        $this->assertParameter(is_bool($this->array_items_depluralize), "array_items_depluralize must be bool");
        $this->assertParameter(is_bool($this->processValueNodes), "processValueNodes must be bool");
    }
    
    /**
     * A utility to check params
     * @param bool
     * @param string
     */
    function assertParameter($pass, $message) {
        if (!$pass) {
            throw new \Exception($message);
        }
    }

    /**
     * Sets processValueNodes
     * @param bool
     */
    function setProcessValueNodes($value) {
        if (!empty($value) && $value === true)
            $this->processValueNodes = true;
        else
            $this->processValueNodes = false;
    }

    /**
     * Getter
     */
    function getProcessValueNodes() {
        return $this->processValueNodes;
    }
    
    /**
     * Sets array_names_table
     * @param array
     */
    function setArrayNamesTable( array $array_names_table ) {
        $this->array_names_table = $array_names_table;
    }

    /**
     * Sets attr_names_table
     * @param array
     */
    function setAttrNamesTable( array $attr_names_table ) {
        $this->attr_names_table = $attr_names_table;
    }

    /**
     * Sets use_cdata_regex
     * @param string
     */
    function setCDataRegex( $use_cdata_regex ) {
        $this->use_cdata_regex = $use_cdata_regex;
    }

    /**
     * Sets boolean_values_table
     * @param array
     */
    function setBooleanValuesTable( $boolean_values_table ) {
        $this->boolean_values_table = $boolean_values_table;
    }

    /**
     * Sets array_items_numeric
     * @param boolean
     */
    function setArrayItemsNumeric( $array_items_numeric ) {
        $this->array_items_numeric = $array_items_numeric == TRUE ? TRUE : FALSE;
    }

    /**
     * Sets array_items_depluralize
     * @param boolean
     */
    function setArrayItemsDepluralize( $array_items_depluralize ) {
        $this->array_items_depluralize = $array_items_depluralize == TRUE ? TRUE : FALSE;
    }

    /**
     * Serializes data
     * @param mixed the data to serialize
     * @param DOMNode the node to serialize into
     */
    function serialize($data, \DOMNode $parent) {
        switch (gettype($data)) {
        case 'array':
            $this->serializeArray($data, $parent);
            break;

        case 'integer':
        case 'double':
        case 'string':
            $this->serializeScalar($data, $parent);
            break;

        case 'boolean':
            $newdata = $data === TRUE ? $this->boolean_values_table[TRUE] :
            $this->boolean_values_table[FALSE];
            $this->serializeScalar($newdata, $parent);
            break;

        case 'object':
            $class = get_class($data);
            switch ($class) {
            case 'DOMNode':
            case 'DOMElement':
                $this->importForeignDomTree($data, $parent);
                break;
            default:
                throw new \Exception("can't serialize object of class $class");
            }
            break;

        default:
            if (is_null($data)) {
                $this->serializeScalar($data, $parent);
            } else {
                if (empty($type)) $type = 'UNKNOWN';
                throw new \Exception("can't serialize data of type $type");
            }
        }
    }

    /**
     * Serializes an array
     * @param array the data to serialize
     * @param DOMNode the node to serialize into
     */
    protected function serializeArray(array $data, \DOMElement $parent) {
        $doc = $parent->ownerDocument;
        $parent_name = $parent->nodeName;

        foreach ($data as $k => $v) {

            // non-associative arrays pose a problem as XML node names can't be
            // numbers:
            if (is_numeric($k)) {
                $name = $this->getNameForNumeric($parent_name, $k);
            } else {
                // assoc. arrays: just use key name
                $name = $k;
            }

            // check to see if this child should be an attribute instead of a node:
            if ($this->isAttributeItem($parent_name, $name)) {
                $child = $doc->createAttribute($name);
            } else {
                try {
                    $child = $doc->createElement($name);
                } catch (\Exception $e) {
                    fwrite(STDOUT, print_r($name, TRUE));
                }
            }

            if ('value' == $name && $this->processValueNodes) {
                // here's a bit tricky part
                // we have to check each element in the array to make sure they all are attributes 
                // if at least one element isn't an attribute we can't process <value> node as it will break the xml
                foreach ($data as $key => $value) {
                    if ($key != 'value' && !$this->isAttributeItem($parent_name, $key)) {
                        $parent->appendChild($child);
                        $this->serialize($v, $child);
                        continue 2;
                    }
                }

                $node = $doc->createTextNode($v);
                $parent->appendChild($node);
            }
            else {
                try {
                    
                    $parent->appendChild($child);
                } catch (\Exception $e) {
                    echo "FAILE at 362:";
                    print_r($e);
                    
                }
            }
            
            $this->serialize($v, $child);
        }
    }

    /**
     * Serializes a string or number
     * @param string the data to serialize
     * @param DOMNode the node to serialize into
     */
    protected function serializeScalar($data, \DOMNode $parent) {
        $doc     = $parent->ownerDocument;
        $is_attr = $parent instanceof DOMAttr;

        if (!$is_attr && $this->use_cdata_regex
            && preg_match($this->use_cdata_regex, $data)) {
            $child = $doc->createCDATASection($data);
        } else {
            $child = $doc->createTextNode($data);
        }

        $parent->appendChild($child);
    }

    /**
     * Imports foreign DOMNode objects into the current document
     * @param DOMNode the node to import
     * @param DOMNode the node to import into
     */
    protected function importForeignDomTree(\DOMNode $src, \DOMNode $dest) {
        $doc     = $dest->ownerDocument;
        $new_src = $doc->importNode($src, TRUE);
        $dest->appendChild($new_src);
    }


    /**
     * Returns true if the subnode should be an attribute
     * @param string parent node name
     * @param string child node name
     * @return boolean
     */
    protected function isAttributeItem($parent_name, $name) {
        if (!array_key_exists($parent_name, $this->attr_names_table)) {
            return FALSE;
        }
        if (is_array($this->attr_names_table[$parent_name])) {
            if (in_array($name, $this->attr_names_table[$parent_name])) {
                return TRUE;
            }
        } else {
            if ($name == $this->attr_names_table[$parent_name]) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Returns the child node name for a numeric index
     * @param string parent node name
     * @param integer array element index
     * @return string child node name
     */
    protected function getNameForNumeric($parent_name, $index) {
        
        if (array_key_exists($parent_name, $this->array_names_table)) {
            // use a lookup table to name nodes; parent_name => child_name            
            $name = $this->array_names_table[$parent_name];
        } elseif ($this->array_items_depluralize && (strtolower(substr($parent_name, -1, 1)) == 's')) {
            // strip trailing 'S'
            $name = substr($parent_name, 0, -1);
        } elseif ($this->array_items_numeric) {
            // name nodes item_0, item_1, etc...
            $name = "item_" . $index;
        } else {
            // fallback, use parent_name & append with _item
            $name = $parent_name . "_item";
        }
        return $name;
    }

    /**
     * Takes an XML fragment and converts it into a mixed data structure.  Uses
     * pass-by-reference instead of returning a value.
     * @param string xml data
     * @param mixed output will be written into this variable
     */
    public function deserializeString($xml, &$data) {
        $doc = new \DomDocument();
        $doc->loadXml(trim($xml));
        $node = $doc->documentElement;
        return $this->deserialize($node, $data);
    }

    /**
     * Takes a DOMNode object and converts it into a mixed data structure.  Uses
     * pass-by-reference instead of returning a value.
     * @param string a DOMNode
     * @param mixed output will be written into this variable
     */
    public function deserialize(\DOMNode $node, &$data) {
        if ($this->nodeIsScalar($node)) {
            $this->deserializeScalar($node, $data);
        } elseif ($this->nodeIsArray($node)) {
            $this->deserializeArray($node, $data);
        } else {
            $class = get_class($node);
            error_log($node->nodeName);
            error_log($node->nodeValue);
            throw new \Exception("don't know how to deserialize $class");
        }
    }

    /**
     * Takes a DOMNode object and converts it into a scalar value.  Uses
     * pass-by-reference instead of returning a value.  Called by deserialize().
     * @param string a DOMNode
     * @param mixed output will be written into this variable
     */
    protected function deserializeScalar(\DOMNode $node, &$data) {
        $value = trim($node->textContent);
        if ($value == $this->boolean_values_table[TRUE]) {
            $data = TRUE;
        } elseif ($value == $this->boolean_values_table[FALSE]) {
            $data = FALSE;
        } else {
            $data = $value;
        }
    }

    /**
     * Takes a DOMNode object and converts it into an array.  Uses
     * pass-by-reference instead of returning a value. Called by deserialize().
     * @param string a DOMNode
     * @param mixed output will be written into this variable
     */
    protected function deserializeArray(\DOMNode $node, &$data) {
        $parent_name  = $node->nodeName;
        $data         = array();
        $child_counts = array();

        // always treat attributes as hash elements
        if (!empty($node->attributes)) {
            foreach ($node->attributes as $name=>$child) {
                $this->deserializeScalar($child, $data[$name]);
            }
        }

        // count the names of child nodes and check each one against
        // our own rules for naming array items
        $map  = $this->getChildNodeMap($node);
        $text = '';

        foreach ($node->childNodes as $child) {
            if ($child instanceof DomText) {
                $text .= $child->nodeValue;
                continue;
            }

            $name     = $child->nodeName;
            $use_numeric  = isset($map[$name]) && $map[$name] === TRUE;
            $use_subarray = isset($map[$name]) && $map[$name] > 1;

            if ($use_numeric) {
                $this->deserialize($child, $data[]);
            } elseif ($use_subarray) {
                $this->deserialize($child, $data[$name.'s'][]);
            } else {
                $this->deserialize($child, $data[$name]);
            }
        }

        $text = trim($text);
        if (!empty($text)) $data['value'] = $text;
    }

    /**
     * Return TRUE if those node seems to represent an array structure.
     * @param DOMNode a DOMNode object
     * @return boolean
     */
    protected function nodeIsArray(\DOMNode $node) {
        if ($node->hasAttributes()) return TRUE;

        $kids = $node->childNodes;
        if (!$kids) return FALSE;

        foreach ($kids as $child) {
            if ($child instanceof \DOMNode) return TRUE;
        }

        return FALSE;
    }

    /**
     * Return TRUE if those node seems to represent a scalar value
     * @param DOMNode a DOMNode object
     * @return boolean
     */
    protected function nodeIsScalar(\DOMNode $node) {
        if ($node->hasAttributes()) {
            return FALSE;
        }

        $kids = $node->childNodes;

        if (empty($kids) || $kids->length == 0) {
            return TRUE; //empty node
        }

        if ($kids->length == 1) {
            if ($kids->item(0) instanceOf DomText) {
                return TRUE;
            } else {
                return FALSE;
            }
        }

        return FALSE;
    }

    /**
     * Return TRUE if those node seems to contain a numeric array (as opposed
     * to a hash-style associative array.)  Generally obeys the same rules  and
     * options for node naming as the serialize() routine.  Any node that
     * contains duplicately-named child nodes will be treated as an array, as
     * well.
     * @param DOMNode a DOMNode object
     * @return boolean
     */
    protected function getChildNodeMap($node) {
        $names       = array();
        $parent_name = $node->nodeName;
        $c       = 0;

        foreach ($node->childNodes as $child) {
            if ($child instanceof DomText) {
                continue;
            }
            $child_name = $child->nodeName;
            $suggested  = $this->getNameForNumeric($parent_name, $c);

            // use a subarray on duplicate nodes
            if (isset($names[$child_name])) {
                $names[$child_name]++;
            } else {
                $names[$child_name] = 1;
            }

            // follow our own rules for naming numeric arrays
            $names[$suggested] = TRUE;
            $c++;
        }
        return $names;
    }
    /**
     * Configures this object based upon an array of N=>V pairs.  Constructors
     * should call this method when named parameters are used.  Throws a
     * NYTD_BadParameterException when a required argument is missing
     * or when an argument name is not known.
     *
     * @param array $args the arguments passed to the constructor
     * @param array $optional a list of parameter names that are optional
     * @param array $required (optional) a list of parameter names that must be defined
     * @return void
     */
    protected function parseNamedParameters($args, $optional, $required = NULL) {
        // Check that required parameters are present: for our purposes, this
        // merely means that the array key exists (but value may be NULL, FALSE,
        // empty string, etc.)  If you need stricter value checking you should
        // call assertParameter in your constructor.
        if ($required) {
            foreach ($required as $n) {
                if (!array_key_exists($n, $args)) {
                    throw new \Exception("missing parameter name: $n");
                }
            }
        }

        // Go through every value of $args and check that the keyname is
        // optional.  Set member variables accordingly.
        foreach ($args as $n=>$v) {
            if (!in_array($n, $optional) && !in_array($n, $required)) {
                throw new \Exception("bad parameter name: $n");
            }

            $this->$n = $v;
        }
    }
}
