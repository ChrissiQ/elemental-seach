<?php

/**
 * Created by Nivanka Fonseka (nivanka@silverstripers.com).
 * User: nivankafonseka
 * Date: 9/7/18
 * Time: 11:36 AM
 * To change this template use File | Settings | File Templates.
 */

namespace SilverStripers\ElementalSearch\Model;

use SilverStripe\Control\Director;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\SSViewer;

class SearchDocument extends DataObject
{

    private static $db = [
        'Type' => 'Varchar(300)',
        'OriginID' => 'Int',
        'Title' => 'Text',
        'Content' => 'Text',
    ];

    private static $searchable_fields = [
        'Title',
        'Content'
    ];

    private static $table_name = 'SearchDocument';

    private static $search_x_path;

    /**
     * @return DataObject
     */
    public function Origin()
    {
        return DataList::create($this->Type)->byID($this->OriginID);
    }

    public function makeSearchContent()
    {
        $origin = $this->Origin();
        $searchLink = $origin->getGenerateSearchLink();

        $mode = Versioned::get_reading_mode();
        $themes = SSViewer::get_themes();
        SSViewer::set_themes(SSViewer::config()->uninherited('themes'));
        Versioned::set_reading_mode('Stage.Stage');

        $response = Director::test($searchLink);
        $body = $response->getBody();

        $x_path = $origin->config()->get('search_x_path');
        if(!$x_path) {
            $x_path = self::config()->get('search_x_path');
        }

        if($x_path) {
            $domDoc = new \DOMDocument();
            @$domDoc->loadHTML($body);

            $finder = new \DOMXPath($domDoc);
            $nodes = $finder->query("//*[contains(@class, '$x_path')]");
            $nodeValues = [];
            if($nodes->length) {
                foreach ($nodes as $node) {
                    $nodeValues[] = $node->nodeValue;
                }
            }
            $contents = implode("\n\n", $nodeValues);
        }
        else {
            $contents = strip_tags($body);
        }

        $this->Title = $origin->getTitle();
        if($contents) {
			$this->Content = $contents;
		}
        $this->write();
        SSViewer::set_themes($themes);
        Versioned::set_reading_mode($mode);

    }

    function removeEmptyLines($string)
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
    }

}