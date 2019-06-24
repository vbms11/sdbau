<?php

class QueryResult {
    
    var $results;
    var $size;
    
    function __construct($results, $size) {
        $this->results = $results;
        $this->size = $size;
    }
    
    function get ($index) {
        if ($index >= $this->size) {
            return null;
        }
        return $this->results[$index];
    }
    
    function asArray () {
        return $this->results;
    }
    
}