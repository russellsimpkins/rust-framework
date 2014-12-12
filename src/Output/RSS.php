<?php
namespace Rust\Output;

/**
 * Turns a Response object into RSS. 
 *
 * @author Joseph Do <joseph.do@nytimes.com>
 */
class RSS
{
    /**
     * @var \DOMDocument $dom
     */
    private $dom;

    /**
     * Writes the contents of an API response packet as RSS.
     *
     * @param \mixed $data - the response data
     */
    public function write($data)
    {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->makeMeta($data);
        return $this->dom->saveXML();
    }

    /**
     * Create a DOM Node that represents an attribute with a specific name and value.
     *
     * @param string $attribute_name     Name of the node to create
     * @param string $attribute_value    The desired content of the node
     */
    private function createAttributeNode($attribute_name, $attribute_value)
    {
        $attr_node = $this->dom->createAttribute($attribute_name);
        $text_node = $this->dom->createTextNode($attribute_value);
        $attr_node->appendChild($text_node);
        return $attr_node;
    }

    /**
     * Helper function to make meta data node.
     *
     * @param array $data - the response packet
     */
    private function makeMeta(array $data)
    {
        //channel meta-data
        $url       = empty($_SERVER['SCRIPT_URL']) ? 'unit/test/code/here' : $_SERVER['SCRIPT_URL'];
        $cmd       = basename($url, '.rss');
        $url       = explode('/', $url);
        $name      = $url[2];
        $copyright = empty($data['copyright']) ? 'missing copyright' : $data['copyright'];
        $language  = 'en-us';
        $hub       = (empty($data['hub'])) ? false : $data['hub'];

        // sets title from data or dynamically
        if (empty($data['title'])) {
            $title = 'NYT > ' .strtoupper($name) . ' API ' . strtoupper($cmd);
        } else {
            $title = $data['title'];
        }

        $link          = empty($_SERVER['SCRIPT_URI']) ? 'missing/script/unit/test' : $_SERVER['SCRIPT_URI'];
        $lastBuildDate = gmdate('D, j M Y H:i:s') . ' GMT';

        // rss attribute nodes / append rss meta-data
        $rss = $this->dom->createElement('rss');
        $rss->appendChild($this->createAttributeNode('xmlns:dc', 'http://purl.org/dc/elements/1.1/'));
        $rss->appendChild($this->createAttributeNode('xmlns:atom', 'http://www.w3.org/2005/Atom'));
        $rss->appendChild($this->createAttributeNode('xmlns:nyt', 'http://www.nytimes.com/namespaces/rss/2.0'));
        $rss->appendChild($this->createAttributeNode('xmlns:media', 'http://search.yahoo.com/mrss/'));
        $rss->appendChild($this->createAttributeNode('version', '2.0'));
        $this->dom->appendChild($rss);

        $channel = $this->dom->createElement('channel');
        $rss->appendChild($channel);

        // append channel meta-data
        $channel->appendChild($this->dom->createElement('title', $title));
        $channel->appendChild($this->dom->createElement('link', $link));
        $channel->appendChild($this->dom->createElement('language', $language));
        $channel->appendChild($this->dom->createElement('copyright', $copyright));
        $channel->appendChild($this->dom->createElement('lastBuildDate', $lastBuildDate));

        // append pubsubhubbub meta-data to channel if exists
        if ($hub) {
            $hub_node = $this->dom->createElement('atom:link');
            $hub_node->appendChild($this->createAttributeNode('rel', 'hub'));
            $hub_node->appendChild($this->createAttributeNode('href', $hub));
            $channel->appendChild($hub_node);

            $self_node = $this->dom->createElement('atom:link');
            $self_node->appendChild($this->createAttributeNode('rel', 'self'));
            $self_node->appendChild($this->createAttributeNode('self', $link));
            $channel->appendChild($self_node);
        }


        $image = $this->dom->createElement('image');
        $image->appendChild($this->dom->createElement('title', $title));
        $image->appendChild($this->dom->createElement(
            'url',
            'http://graphics.nytimes.com/images/section/NytSectionHeader.gif'
        ));
        $image->appendChild($this->dom->createElement('link', 'http://www.nytimes.com/pages/index.html?partner=rss'));
        $channel->appendChild($image);

        // append item nodes to channel
        $this->formItems($data, $channel);
    }

    /**
     * Take an item and a list of possible keys and return a new DOM Node that
     * contains the first valid value found or null if none of the keys 
     * have valid values.
     *
     * @param \mixed $item - The item to search for values
     * @param string $node_name - The DOM Node name to return
     * @param \array $key_list - The list of keys to check for values
     * @return \mixed - null or domelement
     */
    private function extractNode(&$item, $node_name, array $key_list)
    {
        foreach ($key_list as $key) {
            if (isset($item[$key])) {
                return $this->dom->createElement($node_name, htmlspecialchars($item[$key]));
            }
        }
        return null;
    }

    /**
     * Helper function to make item data nodes.
     *
     * @param \mixed $data - the response packet
     * @param \string $channel - the channel
     * @return null
     */
    private function formItems(&$data, &$channel)
    {
        foreach ($data['results'] as $item) {
            // convert created date to RFC 2822
            $time            = empty($item['created']) ? time() : strtotime($item['created']);
            $item['created'] = gmdate('D, j M Y H:i:s', $time) . ' GMT';
            
            if ((!empty($item['title']) || !empty($item['headline'])) && !empty($item['url'])) {
                $item_node = $this->dom->createElement('item');

                // create item nodes from data
                $title = $this->extractNode($item, 'title', array('title', 'headline'));
                $link  = $this->extractNode($item, 'link', array('url'));
                $guid  = $this->extractNode($item, 'guid', array('guid', 'url'));
                $guid->appendChild($this->createAttributeNode('isPermaLink', 'false'));

                $description = $this->extractNode($item, 'description', array('description', 'summary', 'abstract'));
                $author      = $this->extractNode($item, 'dc:creator', array('author', 'byline'));
                $pubdate     = $this->extractNode($item, 'pubDate', array('published_date', 'date', 'created'));

                // append our generated nodes back up to the item
                foreach (array($title, $link, $description, $author, $pubdate, $guid) as $node) {
                    if ($node) {
                        $item_node->appendChild($node);
                    }
                }

                $category_item = empty($item['category']) ? array() : $item['category'];

                if (!is_array($category_item)) {
                    $category_item = array($category_item);
                }

                foreach ($category_item as $c) {
                    $item_node->appendChild($this->dom->createElement('category', $c));
                }

                // if media exists, attach to item
                if (!empty($item['media'])) {
                    $this->formMM($item['media'], $item_node);
                }

                $channel->appendChild($item_node);
            }
        }
    }

    /**
     * Helper function to make MM nodes to append to item nodes
     *
     * @param \mixed $data - the response packet
     * @param \DomElement $item_node - a DomElement to operate on
     * @return null
     */
    private function formMM($images, &$item_node)
    {
        foreach ($images as $image) {
            $mContent     = $this->dom->createElement('media:content');
            $mDescription = $this->dom->createElement('media:description');
            $mCredit      = $this->dom->createElement('media:credit');

            if (!empty($image['media-metadata'])) {
                foreach ($image['media-metadata'] as $meta) {
                    if ($meta['format'] == 'Standard Thumbnail') {
                        $mContent->appendChild($this->createAttributeNode('url', $meta['url']));
                        $mContent->appendChild($this->createAttributeNode('medium', 'image'));
                        $mContent->appendChild($this->createAttributeNode('height', $meta['height']));
                        $mContent->appendChild($this->createAttributeNode('width', $meta['width']));
                    }
                }
                $mDescription->appendChild($this->dom->createTextNode($image['caption']));
                $mCredit->appendChild($this->dom->createTextNode($image['copyright']));
            }
            $mContent->appendChild($mDescription);
            $mContent->appendChild($mCredit);
            $item_node->appendChild($mContent);
        }
    }
}
