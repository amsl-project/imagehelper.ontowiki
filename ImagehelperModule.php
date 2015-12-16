<?php

/**
 * OntoWiki image helper module
 *
 * Rewrites a given value of a resource to an image URL and displays this image in a module
 *
 * @category OntoWiki
 * @package Extensions_Map
 * @author Natanael Arndt <arndt@informatik.uni-leipzig.de>
 * @copyright Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class ImagehelperModule extends OntoWiki_Module
{
    public function getTitle()
    {
        return $this->_owApp->translate->_('Image Helper');
    }

    public function shouldShow()
    {
        $query = new Erfurt_Sparql_SimpleQuery();
        $query->setProloguePart('SELECT DISTINCT ?type')
            ->setWherePart(
                'WHERE {<' . (string)$this->_owApp->selectedResource . '> a ?type.}'
            );
        if ($results = $this->_owApp->selectedModel->sparqlQuery($query)) {
            foreach ($results as $result) {
                if (in_array('http://purl.org/ontology/bibo/Periodical', $result)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the map content
     */
    public function getContents()
    {
        if (!isset($this->_owApp->selectedResource)) {
            $this->_owApp->logger->debug(
                'ImagehelperModule/getContents: no selectedResource available.'
            );
            return;
        }

        $resourceUri = $this->_owApp->selectedResource;

        $properties = array();
        if (isset($this->_privateConfig->properties)) {
            $properties = $this->_privateConfig->properties;
        }

        $this->view->imageUrl = array();
        if (count($properties) > 0) {
            $sparql = 'SELECT ?property ?value WHERE {';
            $sparql .= ' <' . $resourceUri . '> ?property ?value .';
            $sparql .= ' FILTER(';
            foreach ($properties as $setup) {
                $sparql .= ' sameTerm(?property, <' . $setup->property . '>) ||';
            }
            $sparql = substr($sparql, 0, -2);
            $sparql .= ' )';
            $sparql .= '}';

            $results = $this->_owApp->selectedModel->sparqlQuery($sparql);

            $properties = $properties->toArray();
            $properties = $this->_array_index($properties, 'property');

            foreach ($results as $row) {
                $value = $row['value'];
                $property = $row['property'];
                $ruleSpec = $properties[$property];
                $this->view->imageUrl[] = preg_replace($ruleSpec['pattern'], $ruleSpec['replacement'], $value);
            }
        }

        return $this->render('imagehelper');
    }

    /**
     * This method takes a 2D array and puts the value of the given key of each sub array as key in the super array
     *
     * @param $array the array to process
     * @param $key a string giving the key of the sub array to use as key for the super array
     * @return array
     */
    private function _array_index($array, $key)
    {
        $out = array();
        foreach ($array as $subArray) {
            $out[$subArray[$key]] = $subArray;
        }

        return $out;
    }
}

