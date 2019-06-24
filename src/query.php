<?php

class Query {
    
    var $DB;
    var $query;
    
    public function __construct(&$DB, $query) {
        $this->DB = &$DB;
        $this->query = $query;
    }
    
    function execute ($assoc=true) {
        $result = $this->DB->query($this->query);
        $rows = $this->DB->fetchAllRows($result,$assoc);
        $size = $this->DB->numRows($result);
        return new QueryResult($rows, $size);
    }
}